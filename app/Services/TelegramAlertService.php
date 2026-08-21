<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemErrorNotification;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Notifications\Notification;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class TelegramAlertService
{
    /**
     * Exception classes that should not trigger Telegram notifications.
     *
     * @var list<class-string<Throwable>>
     */
    protected static array $ignoredExceptions = [
        NotFoundHttpException::class,
        ValidationException::class,
        AuthenticationException::class,
        AuthorizationException::class,
        TokenMismatchException::class,
    ];

    /**
     * In-memory cache fallback for throttling when Redis or Cache driver is down.
     *
     * @var array<string, int>
     */
    protected static array $inMemoryThrottle = [];

    /**
     * Report an unhandled exception to Telegram with deduplication/throttling.
     */
    public static function reportException(Throwable $e): void
    {
        try {
            if (app()->environment('testing') || app()->runningUnitTests()) {
                return;
            }

            if (! config('telegraph.notifications.errors.enabled', true)) {
                return;
            }

            foreach (static::$ignoredExceptions as $ignored) {
                if ($e instanceof $ignored) {
                    return;
                }
            }

            // If it's a 4xx HTTP exception, do not alert
            if ($e instanceof HttpException && $e->getStatusCode() < 500) {
                return;
            }

            $throttleMinutes = (int) config('telegraph.notifications.errors.throttle_minutes', 15);
            $exceptionClass = get_class($e);
            $file = $e->getFile();
            $line = $e->getLine();
            $message = $e->getMessage();

            $hashKey = 'tg_err_'.md5("{$exceptionClass}|{$file}|{$line}|{$message}");
            $countKey = "{$hashKey}_count";
            $occurrences = 1;

            if ($throttleMinutes > 0) {
                $throttled = false;

                try {
                    if (Cache::has($hashKey)) {
                        Cache::increment($countKey);
                        $throttled = true;
                    } else {
                        Cache::put($hashKey, true, now()->addMinutes($throttleMinutes));
                        Cache::put($countKey, 1, now()->addMinutes($throttleMinutes));
                    }
                    $occurrences = (int) (Cache::get($countKey, 1) ?: 1);
                } catch (Throwable) {
                    // Fallback to in-memory throttle if Cache store (e.g. Redis) is unavailable
                    $lastSeen = static::$inMemoryThrottle[$hashKey] ?? 0;
                    $now = time();
                    if ($now - $lastSeen < ($throttleMinutes * 60)) {
                        $throttled = true;
                    } else {
                        static::$inMemoryThrottle[$hashKey] = $now;
                    }
                }

                if ($throttled) {
                    return;
                }
            }

            // Gather context
            $isCli = app()->runningInConsole();
            $contextUrl = null;
            $method = null;
            $ip = null;
            $userId = null;

            if ($isCli) {
                $argv = $_SERVER['argv'] ?? [];
                $contextUrl = ! empty($argv) ? 'CLI: '.implode(' ', $argv) : 'CLI command';
            } else {
                try {
                    $req = request();
                    if ($req) {
                        $contextUrl = $req->fullUrl();
                        $method = $req->method();
                        $ip = $req->ip();
                        $userId = auth()->id();
                    }
                } catch (Throwable) {
                    // Ignore context extraction failure
                }
            }

            $notification = new SystemErrorNotification(
                exceptionClass: $exceptionClass,
                errorMessage: $message,
                file: $file,
                line: $line,
                contextUrl: $contextUrl,
                method: $method,
                ip: $ip,
                userId: $userId,
                occurrences: $occurrences,
            );

            static::dispatchNotification($notification);
        } catch (Throwable $outer) {
            // Absolute safeguard: error reporting must never crash the application
            error_log('TelegramAlertService::reportException failed: '.$outer->getMessage());
        }
    }

    /**
     * Dispatch notification to admins with automatic direct fallback if the database fails.
     */
    public static function dispatchNotification(Notification $notification): void
    {
        $chatId = config('telegraph.chat_id') ?? env('TELEGRAM_CHAT_ID');

        try {
            $admins = User::where('role', 'admin')->get();

            if ($admins->isNotEmpty()) {
                NotificationFacade::send($admins, $notification);

                return;
            }
        } catch (Throwable $dbException) {
            Log::warning('Failed to query admins for Telegram notification: '.$dbException->getMessage());
        }

        // Direct fallback if no admin or database error occurred
        if (! empty($chatId) && method_exists($notification, 'toTelegraph')) {
            try {
                $telegraphMessage = $notification->toTelegraph((object) []);
                if ($telegraphMessage instanceof TelegraphMessage) {
                    $telegraphMessage->toTelegraph($chatId)->send();
                }
            } catch (Throwable $directException) {
                Log::error('Direct Telegram notification delivery failed: '.$directException->getMessage());
            }
        }
    }

    /**
     * Send a raw HTML message directly to Telegram.
     */
    public static function sendDirectMessage(string $htmlMessage, ?string $chatId = null): bool
    {
        $targetChatId = $chatId ?: (config('telegraph.chat_id') ?? env('TELEGRAM_CHAT_ID'));

        if (empty($targetChatId)) {
            return false;
        }

        try {
            $response = Telegraph::chat($targetChatId)->html($htmlMessage)->send();

            return ! $response->telegraphError();
        } catch (Throwable $e) {
            Log::error('Direct Telegram message sending failed: '.$e->getMessage());

            return false;
        }
    }
}

<?php

namespace Tests\Feature;

use App\Listeners\TelegramSystemEventsSubscriber;
use App\Models\User;
use App\Notifications\SystemErrorNotification;
use App\Services\TelegramAlertService;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Telegraph as TelegraphCore;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Event as SchedulingEvent;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class TelegramSystemAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        config(['telegraph.chat_id' => '123456789']);
        config(['telegraph.bot_token' => 'mock-bot-token']);
        config(['telegraph.notifications.errors.enabled' => true]);
        config(['telegraph.notifications.errors.throttle_minutes' => 15]);
        config(['telegraph.notifications.scheduled_tasks.notify_on_failure' => true]);
        config(['telegraph.notifications.scheduled_tasks.notify_on_success' => true]);
        config(['telegraph.notifications.queue.notify_on_failure' => true]);
        config(['telegraph.notifications.auth.notify_on_lockout' => true]);
        Cache::flush();
    }

    public function test_it_reports_unhandled_exception_to_telegram(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 101]],
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $exception = new \RuntimeException('Database connection timed out');
        TelegramAlertService::reportException($exception);

        Telegraph::assertSent('🚨 <b>Backend Exception Occurred!</b>', false);
        Telegraph::assertSent('RuntimeException', false);
        Telegraph::assertSent('Database connection timed out', false);

        $notification = $admin->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('system_error', $notification->data['type']);
    }

    public function test_it_throttles_duplicate_exceptions_within_window(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 102]],
        ]);

        User::factory()->create(['role' => 'admin']);

        $exception = new \RuntimeException('Duplicate recurring error');

        // First call sends
        TelegramAlertService::reportException($exception);
        Telegraph::assertSent('Duplicate recurring error', false);

        // Reset fake assertions to test second call
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 103]],
        ]);

        // Second call within 15 minutes is throttled
        TelegramAlertService::reportException($exception);
        Telegraph::assertNothingSent();
    }

    public function test_it_ignores_expected_client_exceptions(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 104]],
        ]);

        User::factory()->create(['role' => 'admin']);

        TelegramAlertService::reportException(new NotFoundHttpException('Page not found'));
        TelegramAlertService::reportException(ValidationException::withMessages(['email' => 'Invalid email']));

        Telegraph::assertNothingSent();
    }

    public function test_it_handles_scheduled_task_failed_event(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 105]],
        ]);

        User::factory()->create(['role' => 'admin']);

        $task = $this->createMock(SchedulingEvent::class);
        $task->method('getSummaryForDisplay')->willReturn('artisan ccma:populate');

        $event = new ScheduledTaskFailed($task, new \Exception('Failed to process CSV file'));

        $subscriber = new TelegramSystemEventsSubscriber;
        $subscriber->handleScheduledTaskFailed($event);

        Telegraph::assertSent('❌ <b>Scheduled Task Failed!</b>', false);
        Telegraph::assertSent('artisan ccma:populate', false);
        Telegraph::assertSent('Failed to process CSV file', false);
    }

    public function test_it_handles_scheduled_task_finished_event_when_enabled(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 106]],
        ]);

        User::factory()->create(['role' => 'admin']);

        $task = $this->createMock(SchedulingEvent::class);
        $task->method('getSummaryForDisplay')->willReturn('artisan sitemap:generate');

        $event = new ScheduledTaskFinished($task, 3.45);

        $subscriber = new TelegramSystemEventsSubscriber;
        $subscriber->handleScheduledTaskFinished($event);

        Telegraph::assertSent('⏱️ <b>Scheduled Task Succeeded</b>', false);
        Telegraph::assertSent('artisan sitemap:generate', false);
        Telegraph::assertSent('3.45s', false);
    }

    public function test_it_handles_queue_job_failed_event(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 107]],
        ]);

        User::factory()->create(['role' => 'admin']);

        $job = $this->createMock(Job::class);
        $job->method('resolveName')->willReturn('App\Jobs\ProcessDatasetExport');
        $job->method('getQueue')->willReturn('default');
        $job->method('attempts')->willReturn(3);

        $event = new JobFailed('redis', $job, new \Exception('Disk full'));

        $subscriber = new TelegramSystemEventsSubscriber;
        $subscriber->handleJobFailed($event);

        Telegraph::assertSent('💥 <b>Queue Job Failed!</b>', false);
        Telegraph::assertSent('App\Jobs\ProcessDatasetExport', false);
        Telegraph::assertSent('Disk full', false);
    }

    public function test_it_handles_auth_lockout_event(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 108]],
        ]);

        User::factory()->create(['role' => 'admin']);

        $request = Request::create('/login', 'POST', [
            'email' => 'hacker@example.com',
        ], [], [], [
            'REMOTE_ADDR' => '192.168.1.50',
            'HTTP_USER_AGENT' => 'BadBot/1.0',
        ]);

        $event = new Lockout($request);

        $subscriber = new TelegramSystemEventsSubscriber;
        $subscriber->handleLockout($event);

        Telegraph::assertSent('🛡️ <b>Security Alert: Auth Lockout!</b>', false);
        Telegraph::assertSent('hacker@example.com', false);
        Telegraph::assertSent('192.168.1.50', false);
    }

    public function test_direct_fallback_when_database_is_unavailable(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 109]],
        ]);

        // No admin user created in DB, fallback to direct message to config telegraph.chat_id
        $notification = new SystemErrorNotification(
            exceptionClass: 'PDOException',
            errorMessage: 'Postgres unreachable',
            file: '/var/www/html/app/Http/Controllers/TestController.php',
            line: 55,
        );

        TelegramAlertService::dispatchNotification($notification);

        Telegraph::assertSent('🚨 <b>Backend Exception Occurred!</b>', false);
        Telegraph::assertSent('Postgres unreachable', false);
    }
}

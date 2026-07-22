<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ManualNotification;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

use function Laravel\Prompts\text;

class SendTelegramNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:send
                            {message? : The message to send}
                            {--chat= : Override the default Telegram chat ID}
                            {--markdown : Send the message using Markdown instead of HTML}
                            {--direct : Bypass database logging and send directly to the Telegram bot}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually send a notification using the configured Telegram bot';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $message = $this->argument('message');

        if (empty($message)) {
            $message = text(
                label: 'What is the message you want to send?',
                placeholder: 'Enter notification message...',
                required: true
            );
        }

        $format = $this->option('markdown') ? 'markdown' : 'html';
        $chatId = $this->option('chat') ?: config('telegraph.chat_id');
        $direct = $this->option('direct') || !empty($this->option('chat'));

        $admin = User::where('role', 'admin')->first();

        if (!$direct && $admin !== null) {
            $this->info("Sending notification to admin '{$admin->email}' (chat: {$chatId})...");
            Notification::send($admin, new ManualNotification($message, $format));
            $this->info('Notification sent successfully to admin and logged to the database!');
            return self::SUCCESS;
        }

        if (empty($chatId)) {
            $this->error('No Telegram chat ID configured. Please set TELEGRAM_CHAT_ID in your .env file or pass it using the --chat option.');
            return self::FAILURE;
        }

        $this->info("Sending message directly to Telegram chat: {$chatId}...");

        $telegraph = Telegraph::chat($chatId);

        $response = $format === 'markdown'
            ? $telegraph->markdown($message)->send()
            : $telegraph->html($message)->send();

        if ($response->telegraphError()) {
            $this->error('Failed to send notification: ' . ($response->json('description') ?? 'Unknown error'));
            return self::FAILURE;
        }

        $this->info('Notification sent successfully directly via Telegram bot!');
        return self::SUCCESS;
    }
}

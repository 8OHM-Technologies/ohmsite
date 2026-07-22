<?php

namespace Tests\Feature;

use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Telegraph as TelegraphCore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendTelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['telegraph.chat_id' => '123456789']);
        config(['telegraph.bot_token' => 'mock-bot-token']);
    }

    /**
     * Test sending manual notification through Laravel Notifications if admin exists.
     */
    public function test_it_sends_notification_via_laravel_notifications_if_admin_exists(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => [
                'ok' => true,
                'result' => ['message_id' => 999],
            ],
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->artisan('telegram:send', ['message' => 'Hello World'])
            ->expectsOutput("Sending notification to admin '{$admin->email}' (chat: 123456789)...")
            ->expectsOutput('Notification sent successfully to admin and logged to the database!')
            ->assertExitCode(0);

        $notification = $admin->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('Hello World', $notification->data['message']);
        $this->assertEquals('manual', $notification->data['type']);

        Telegraph::assertSent('Hello World');
    }

    /**
     * Test sending directly if chat option is provided.
     */
    public function test_it_sends_directly_if_chat_option_is_provided(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => [
                'ok' => true,
                'result' => ['message_id' => 999],
            ],
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->artisan('telegram:send', [
            'message' => 'Hello Direct',
            '--chat' => '987654321',
        ])
            ->expectsOutput('Sending message directly to Telegram chat: 987654321...')
            ->expectsOutput('Notification sent successfully directly via Telegram bot!')
            ->assertExitCode(0);

        Telegraph::assertSentData(TelegraphCore::ENDPOINT_MESSAGE, [
            'chat_id' => '987654321',
            'text' => 'Hello Direct',
        ]);

        $this->assertEquals(0, $admin->notifications()->count());
    }

    /**
     * Test prompting for message if none is provided.
     */
    public function test_it_prompts_for_message_if_none_is_provided(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => [
                'ok' => true,
                'result' => ['message_id' => 999],
            ],
        ]);

        $this->artisan('telegram:send', ['--direct' => true])
            ->expectsQuestion('What is the message you want to send?', 'Prompted Message')
            ->expectsOutput('Sending message directly to Telegram chat: 123456789...')
            ->expectsOutput('Notification sent successfully directly via Telegram bot!')
            ->assertExitCode(0);

        Telegraph::assertSent('Prompted Message');
    }

    /**
     * Test handling of missing chat ID configuration.
     */
    public function test_it_handles_missing_chat_id_configuration(): void
    {
        config(['telegraph.chat_id' => null]);

        $this->artisan('telegram:send', [
            'message' => 'Hello Missing',
            '--direct' => true,
        ])
            ->expectsOutput('No Telegram chat ID configured. Please set TELEGRAM_CHAT_ID in your .env file or pass it using the --chat option.')
            ->assertExitCode(1);
    }

    /**
     * Test handling of Telegram API failures on direct send.
     */
    public function test_it_handles_telegram_api_failures_on_direct_send(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => [
                'ok' => false,
                'description' => 'Unauthorized or Bot was blocked by the user',
            ],
        ]);

        $this->artisan('telegram:send', [
            'message' => 'Hello Failure',
            '--direct' => true,
        ])
            ->expectsOutput('Sending message directly to Telegram chat: 123456789...')
            ->expectsOutput('Failed to send notification: Unauthorized or Bot was blocked by the user')
            ->assertExitCode(1);
    }
}

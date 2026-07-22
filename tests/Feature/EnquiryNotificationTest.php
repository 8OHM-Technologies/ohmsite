<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewUserRegistered;
use App\Notifications\WebsiteEnquiryReceived;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Telegraph as TelegraphCore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EnquiryNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['telegraph.chat_id' => '123456789']);
        config(['telegraph.bot_token' => 'mock-bot-token']);
    }

    /**
     * Test contact form validation.
     */
    public function test_contact_form_validation(): void
    {
        $response = $this->postJson('/api/send-website-enquiry', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'division', 'message']);
    }

    /**
     * Test contact form submission sends notification and forwards payload.
     */
    public function test_contact_form_submission_notifies_admins_and_forwards(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 999]],
        ]);

        Http::fake([
            'https://control-plane.8ohm.co.za/api/send-website-enquiry' => Http::response(['status' => 'success'], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $payload = [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'division' => 'general',
            'message' => 'Hello, I have a question.',
        ];

        $response = $this->postJson('/api/send-website-enquiry', $payload);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Check if notification was stored in database
        $this->assertEquals(1, $admin->notifications()->count());
        $this->assertEquals(0, $customer->notifications()->count());

        $notification = $admin->notifications()->first();
        $this->assertEquals('enquiry', $notification->data['type']);
        $this->assertEquals('John Doe', $notification->data['name']);

        // Check Telegram notification message
        Telegraph::assertSent("✉️ <b>New Website Enquiry!</b>\n\n<b>Name:</b> John Doe\n<b>Email:</b> johndoe@example.com\n<b>Division:</b> General\n\n<b>Message:</b>\n<code>Hello, I have a question.</code>");

        // Verify forwarding HTTP request
        Http::assertSent(function ($request) use ($payload) {
            return $request->url() === 'https://control-plane.8ohm.co.za/api/send-website-enquiry'
                && $request->method() === 'POST'
                && $request['name'] === 'John Doe'
                && $request['email'] === 'johndoe@example.com'
                && $request['division'] === 'general'
                && $request['message'] === 'Hello, I have a question.';
        });
    }

    /**
     * Test user registration sends notification.
     */
    public function test_user_registration_sends_notification_to_admins(): void
    {
        Telegraph::fake([
            TelegraphCore::ENDPOINT_MESSAGE => ['result' => ['message_id' => 999]],
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $registrationPayload = [
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'company_name' => 'Acme Corp',
            'phone' => '0711234567',
            'country' => 'South Africa',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $registrationPayload);

        $this->assertAuthenticated();

        // Verify admin got notification in database
        $this->assertEquals(1, $admin->notifications()->count());
        $notification = $admin->notifications()->first();
        $this->assertEquals('registration', $notification->data['type']);
        $this->assertEquals('Alice Smith', $notification->data['name']);
        $this->assertEquals('alice@example.com', $notification->data['email']);

        // Verify Telegram message was sent
        Telegraph::assertSent("👤 <b>New User Registered!</b>\n\n<b>Name:</b> Alice Smith\n<b>Email:</b> alice@example.com\n<b>Company:</b> Acme Corp\n<b>Phone:</b> <code>0711234567</code>\n<b>Country:</b> South Africa");
    }
}

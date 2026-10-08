<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureCanonicalHostTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_redirects_www_requests_to_non_www_with_301(): void
    {
        $response = $this->get('http://www.8ohm.co.za/demo');

        $response->assertStatus(301);
        $response->assertRedirect('http://8ohm.co.za/demo');
    }

    public function test_it_preserves_path_and_query_string_on_www_redirect(): void
    {
        $response = $this->get('http://www.8ohm.co.za/services?foo=bar');

        $response->assertStatus(301);
        $response->assertRedirect('http://8ohm.co.za/services?foo=bar');
    }

    public function test_it_allows_non_www_requests_to_proceed_normally(): void
    {
        $response = $this->get('/demo');

        $response->assertStatus(200);
    }

    public function test_demo_page_renders_canonical_tag_in_server_html(): void
    {
        $response = $this->get('/demo');

        $response->assertStatus(200);
        $expectedCanonical = rtrim(config('app.url') ?: 'http://localhost', '/') . '/demo';
        $response->assertSee('<link rel="canonical" head-key="canonical" href="' . $expectedCanonical . '">', false);
    }

    public function test_privacy_policy_page_renders_canonical_tag(): void
    {
        $response = $this->get('/privacy-policy');

        $response->assertStatus(200);
        $expectedCanonical = rtrim(config('app.url') ?: 'http://localhost', '/') . '/privacy-policy';
        $response->assertSee('<link rel="canonical" href="' . $expectedCanonical . '">', false);
    }
}

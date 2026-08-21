<?php

namespace Tests\Feature;

use App\Models\Dataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the /demo page is accessible and returns the correct Inertia view with cases.
     */
    public function test_demo_page_loads_and_passes_cases(): void
    {
        Dataset::create([
            'name' => 'Superior Courts Case Law',
            'slug' => 'high-court',
            'is_active' => true,
            'demo_data' => [
                [
                    'title' => 'Sample Constitutional Judgment',
                    'court' => 'Constitutional Court of South Africa',
                    'case_number' => 'CCT 01/21',
                    'ratio_decidendi' => 'Constitutional rights interpretation',
                    'judges' => ['Judge A', 'Judge B'],
                ],
            ],
        ]);

        $response = $this->get('/demo');

        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Demo/Analytics/Index')
            ->has('cases')
        );

        $cases = $response->original->getData()['page']['props']['cases'];
        $this->assertNotEmpty($cases);
        $this->assertArrayHasKey('title', $cases[0]);
        $this->assertArrayHasKey('court', $cases[0]);
        $this->assertArrayHasKey('case_number', $cases[0]);
    }
}

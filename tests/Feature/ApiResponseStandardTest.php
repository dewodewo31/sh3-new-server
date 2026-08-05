<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiResponseStandardTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_response_includes_meta(): void
    {
        $category = Category::create([
            'name' => 'Long Run',
            'slug' => 'long-run',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Event::create([
            'category_id' => $category->id,
            'title' => 'Long Run Test',
            'description' => 'Deskripsi',
            'location' => 'Monas',
            'address' => 'Jakarta',
            'start_date' => now()->addDays(10)->setTime(6, 0),
            'end_date' => now()->addDays(10)->setTime(9, 0),
            'registration_start_date' => now()->subDays(5),
            'registration_end_date' => now()->addDays(5),
            'quota' => 10,
            'price' => 0,
            'is_free_for_members' => true,
            'status' => 'publish',
        ]);

        $response = $this->getJson('/api/v1/events');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Long Run Test')
            ->assertJsonStructure([
                'data',
                'meta' => ['timestamp', 'request_id'],
            ]);

        $requestId = $response->json('meta.request_id');

        $this->assertNotNull($requestId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $requestId
        );
        $this->assertNotNull(strtotime($response->json('meta.timestamp')));
    }

    public function test_meta_request_id_echoes_header(): void
    {
        $this->getJson('/api/v1/events', ['X-Request-Id' => 'custom-request-id'])
            ->assertOk()
            ->assertJsonPath('meta.request_id', 'custom-request-id')
            ->assertHeader('X-Request-Id', 'custom-request-id');
    }

    public function test_validation_error_includes_meta(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'short',
        ])->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors',
                'meta' => ['timestamp', 'request_id'],
            ]);
    }

    public function test_unauthenticated_error_includes_meta(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.')
            ->assertJsonStructure([
                'message',
                'meta' => ['timestamp', 'request_id'],
            ]);
    }

    public function test_not_found_error_includes_meta(): void
    {
        $this->getJson('/api/v1/events/99999')
            ->assertNotFound()
            ->assertJsonStructure([
                'message',
                'meta' => ['timestamp', 'request_id'],
            ]);
    }

    public function test_web_requests_do_not_receive_api_meta(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $this->assertStringNotContainsString('"meta"', $response->getContent());
    }
}

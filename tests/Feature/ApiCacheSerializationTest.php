<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression guard for the PHP_Incomplete_Class bug.
 *
 * Root cause: GET /events and GET /membership/plans cached Eloquent/JsonResource
 * objects (or their toArray() output, which still contains nested CategoryResource /
 * Carbon objects). With config/cache.php `serializable_classes => false`, those objects
 * unserialize to __PHP_Incomplete_Class on a cache HIT.
 *
 * The real failure only manifests under the `redis` store, but the bug CLASS is
 * "caching non-plain-array values". This test enforces the invariant directly:
 * the cached payload must be a plain array with no object instances anywhere. Under
 * the `array` test store the cached value is returned as-is, so a reverted fix that
 * caches a Collection/Resource/Carbon will fail this test immediately.
 */
class ApiCacheSerializationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Recursively fail if any value is an object instance — the exact shape that
     * becomes __PHP_Incomplete_Class when serialized by the Redis cache store.
     */
    private function assertNoObjectsRecursive($value, string $path = ''): void
    {
        if (is_object($value)) {
            $this->fail(
                "Cached value at [{$path}] is an object (".get_debug_type($value).
                ") — would unserialize to __PHP_Incomplete_Class under Redis serializable_classes=false."
            );
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->assertNoObjectsRecursive($item, $path.'/'.$key);
            }
        }
    }

    public function test_events_list_cache_is_plain_array(): void
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
            'description' => 'Deskripsi event',
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
        $response->assertOk();

        $cached = Cache::get('api:events:list');
        $this->assertIsArray($cached, 'api:events:list must be cached as a plain array');
        $this->assertArrayHasKey('data', $cached);
        $this->assertIsArray($cached['data']);
        $this->assertNotEmpty($cached['data']);
        // Nested category must be a plain array, not a CategoryResource/Model object.
        $this->assertIsArray($cached['data'][0]['category']);
        $this->assertNoObjectsRecursive($cached, 'api:events:list');
        // Mirrors the EnsureApiMeta wrapper present in the real API response.
        $response->assertJsonStructure(['data', 'meta']);
    }

    public function test_membership_plans_cache_is_plain_array(): void
    {
        MembershipPlan::create([
            'name' => 'Tahunan',
            'key' => 'tahunan',
            'description' => 'Membership 1 tahun',
            'duration' => 12,
            'duration_unit' => 'months',
            'price' => 400000,
            'base_event_price' => 25000,
            'discount_percentage' => 10,
            'reference_event_count' => 50,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $participant = User::factory()->create(['role' => 'participant']);
        Sanctum::actingAs($participant);

        $response = $this->getJson('/api/v1/membership/plans');
        $response->assertOk();

        $cached = Cache::get('api:membership:plans');
        $this->assertIsArray($cached, 'api:membership:plans must be cached as a plain array');
        $this->assertNotEmpty($cached);
        $this->assertNoObjectsRecursive($cached, 'api:membership:plans');
        $response->assertJsonStructure(['data', 'meta']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryActivitySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_category_creates_linked_activity(): void
    {
        $category = Category::create(['name' => 'Trail Run', 'sort_order' => 7]);

        $activity = Activity::where('name', 'Trail Run')->first();

        $this->assertNotNull($activity);
        $this->assertEquals($category->id, $activity->category_id);
        $this->assertEquals(7, $activity->sort_order);
    }

    public function test_renaming_category_updates_linked_activity_name(): void
    {
        $category = Category::create(['name' => 'Short Run']);

        $category->update(['name' => 'Sprint Run']);

        $this->assertDatabaseHas('activities', [
            'category_id' => $category->id,
            'name' => 'Sprint Run',
        ]);
    }

    public function test_deleting_category_keeps_activity_but_unlinks_it(): void
    {
        $category = Category::create(['name' => 'Long Run']);

        $category->delete();

        $this->assertDatabaseHas('activities', [
            'name' => 'Long Run',
            'category_id' => null,
        ]);
    }

    public function test_existing_activity_with_same_name_gets_linked_not_duplicated(): void
    {
        Activity::create(['name' => 'City Run']);

        $category = Category::create(['name' => 'City Run']);

        $this->assertDatabaseCount('activities', 1);
        $this->assertEquals($category->id, Activity::where('name', 'City Run')->first()->category_id);
    }
}

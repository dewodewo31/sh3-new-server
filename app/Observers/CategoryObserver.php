<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Category;

class CategoryObserver
{
    // ponytail: sync satu arah Category -> Activity (B+). Aktivitas yang dibuat manual tidak tertaut balik.

    public function created(Category $category): void
    {
        Activity::updateOrCreate(
            ['name' => $category->name],
            ['category_id' => $category->id, 'sort_order' => $category->sort_order ?? 0]
        );
    }

    public function updated(Category $category): void
    {
        if ($category->wasChanged('name')) {
            Activity::where('category_id', $category->id)->update(['name' => $category->name]);
        }
    }
}

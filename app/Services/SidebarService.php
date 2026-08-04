<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class SidebarService
{
    public function getMenus(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $menus = config('sidebar.menus', []);

        $filteredMenus = [];

        foreach ($menus as $menuGroup) {
            $filteredItems = [];

            foreach ($menuGroup['items'] as $item) {
                if (in_array($user->role, $item['roles'])) {
                    $filteredItems[] = $item;
                }
            }

            if (! empty($filteredItems)) {
                $filteredMenus[] = [
                    'section' => $menuGroup['section'],
                    'items' => $filteredItems,
                ];
            }
        }

        return $filteredMenus;
    }

    public function isActive(array $routes): bool
    {
        foreach ($routes as $route) {
            if (request()->routeIs($route)) {
                return true;
            }
        }

        return false;
    }
}
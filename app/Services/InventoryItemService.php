<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Repositories\InventoryItemRepository;
use Illuminate\Support\Facades\DB;

class InventoryItemService
{
    public function __construct(
        private InventoryItemRepository $inventoryItemRepository,
        private UserService $userService,
    ) {}

    public function create(array $data): InventoryItem
    {
        $data['created_by'] = auth()->id();
        $data['asset_code'] = $data['asset_code'] ?? $this->nextAssetCode();

        $item = $this->inventoryItemRepository->create($data);

        $this->log('create_inventory_item', $item);

        return $item;
    }

    public function update(InventoryItem $item, array $data): bool
    {
        $data['updated_by'] = auth()->id();

        $result = $this->inventoryItemRepository->update($item, $data);

        $this->log('update_inventory_item', $item);

        return $result;
    }

    public function delete(InventoryItem $item): bool
    {
        $result = $this->inventoryItemRepository->delete($item);

        $this->log('delete_inventory_item', $item);

        return $result;
    }

    /** ponytail: AST- + 4 digits (max 9999); widen pad when the table fills up. */
    private function nextAssetCode(): string
    {
        $max = (int) DB::table('inventory_items')
            ->where('asset_code', 'like', 'AST-%')
            ->max(DB::raw('CAST(SUBSTRING(asset_code, 5) AS UNSIGNED)'));

        return 'AST-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    private function log(string $action, InventoryItem $item): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $this->userService->logActivity($user, $action, [
            'inventory_item_id' => $item->id,
            'asset_code' => $item->asset_code,
        ]);
    }
}

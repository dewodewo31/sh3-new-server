<?php

namespace Tests\Feature;

use App\Models\OrganizationMember;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): OrganizationMember
    {
        return OrganizationMember::create(array_merge([
            'name' => 'Hendra Wijaya',
            'position' => 'Ketua Umum',
            'role_description' => 'Memimpin organisasi SH3',
            'sort_order' => 1,
            'level' => 1,
            'is_active' => true,
            'period_start' => now()->startOfYear()->toDateString(),
            'period_end' => now()->endOfYear()->toDateString(),
        ], $attributes));
    }

    public function test_list_organization_is_public(): void
    {
        $this->createMember();

        $this->getJson('/api/v1/organization')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Hendra Wijaya');
    }

    public function test_list_organization_only_shows_active_sorted_by_sort_order(): void
    {
        $this->createMember(['name' => 'Inactive', 'is_active' => false, 'sort_order' => 0]);
        $this->createMember(['name' => 'Second', 'position' => 'Wakil', 'sort_order' => 2]);
        $this->createMember(['name' => 'First', 'position' => 'Ketua', 'sort_order' => 1]);

        $response = $this->getJson('/api/v1/organization');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'First')
            ->assertJsonPath('data.1.name', 'Second');
    }

    public function test_list_organization_can_filter_by_search(): void
    {
        $this->createMember(['name' => 'Hendra Wijaya', 'position' => 'Ketua Umum']);
        $this->createMember(['name' => 'Rina Susanti', 'position' => 'Koordinator Membership', 'sort_order' => 2]);

        $response = $this->getJson('/api/v1/organization?search=rina');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Rina Susanti');
    }

    public function test_list_organization_can_filter_by_level(): void
    {
        $this->createMember(['level' => 1]);
        $this->createMember(['name' => 'Koordinator', 'position' => 'Koordinator', 'level' => 3, 'sort_order' => 2]);

        $response = $this->getJson('/api/v1/organization?level=3');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.level', 3);
    }

    public function test_list_organization_can_filter_by_year(): void
    {
        $this->createMember(['period_start' => '2024-01-01', 'period_end' => '2024-12-31']);
        $this->createMember(['name' => 'Current', 'position' => 'Sekretaris', 'sort_order' => 2]);

        $response = $this->getJson('/api/v1/organization?year='.now()->year);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Current');
    }

    public function test_show_organization_returns_detail_with_holder(): void
    {
        $participant = Participant::factory()->create();
        $member = $this->createMember(['participant_id' => $participant->id]);

        $this->getJson('/api/v1/organization/'.$member->id)
            ->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.position', 'Ketua Umum')
            ->assertJsonPath('data.holder.id', $participant->id)
            ->assertJsonStructure(['data' => ['id', 'name', 'position', 'period_start', 'period_end', 'holder']]);
    }

    public function test_show_nonexistent_organization_returns_404(): void
    {
        $this->getJson('/api/v1/organization/999')->assertNotFound();
    }

    public function test_stats_returns_counts(): void
    {
        $participant = Participant::factory()->create();
        $this->createMember(['participant_id' => $participant->id, 'level' => 1]);
        $this->createMember(['name' => 'Wakil', 'position' => 'Wakil Ketua', 'sort_order' => 2, 'level' => 2]);
        $this->createMember(['name' => 'Inactive', 'position' => 'Pensiun', 'is_active' => false, 'sort_order' => 3, 'level' => 0]);

        $this->getJson('/api/v1/organization/stats')
            ->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.active', 2)
            ->assertJsonPath('data.with_holder', 1)
            ->assertJsonPath('data.by_level.1', 1)
            ->assertJsonPath('data.by_level.2', 1)
            ->assertJsonStructure(['data' => ['total', 'active', 'with_holder', 'by_level', 'by_period']]);
    }

    public function test_tree_returns_hierarchy(): void
    {
        $ketum = $this->createMember(['name' => 'Ketua Umum', 'position' => 'Ketua Umum', 'level' => 1]);
        $this->createMember([
            'name' => 'Wakil Ketua',
            'position' => 'Wakil Ketua',
            'parent_id' => $ketum->id,
            'level' => 2,
            'sort_order' => 2,
        ]);
        $this->createMember(['name' => 'Lain', 'position' => 'Koordinator', 'sort_order' => 3]);

        $response = $this->getJson('/api/v1/organization/tree');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Ketua Umum')
            ->assertJsonPath('data.0.children.0.name', 'Wakil Ketua');
    }
}

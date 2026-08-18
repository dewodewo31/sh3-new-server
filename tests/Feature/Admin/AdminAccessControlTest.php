<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->app['auth']->forgetGuards();

        $routes = [
            '/admin/dashboard',
            '/admin/notifications',
            '/admin/users',
            '/admin/membership-plans',
            '/admin/participants',
            '/admin/memberships',
            '/admin/events',
            '/admin/categories',
            '/admin/galleries',
            '/admin/organization',
            '/admin/sponsors',
            '/admin/merchandise',
            '/admin/payments',
            '/admin/bookkeepings',
            '/admin/attendance/report',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertRedirect('/login');
        }
    }

    public function test_dashboard_is_accessible_by_any_authenticated_user(): void
    {
        $participant = $this->user('participant');

        $this->actingAs($participant)->get('/admin/dashboard')->assertOk();
    }

    public function test_notifications_are_accessible_by_any_authenticated_user(): void
    {
        $participant = $this->user('participant');

        $this->actingAs($participant)->get('/admin/notifications')->assertOk();
    }

    public function test_users_requires_admin_full_access(): void
    {
        $this->actingAs($this->user('organizer'))->get('/admin/users')->assertForbidden();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/users')->assertOk();
    }

    public function test_membership_plans_require_admin_full_access(): void
    {
        $this->actingAs($this->user('admin_laman'))->get('/admin/membership-plans')->assertForbidden();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/membership-plans')->assertOk();
    }

    public function test_participants_allows_admin_full_access_and_admin_member(): void
    {
        $this->actingAs($this->user('bendahara'))->get('/admin/participants')->assertForbidden();
        $this->actingAs($this->user('admin_member'))->get('/admin/participants')->assertOk();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/participants')->assertOk();
    }

    public function test_memberships_allow_admin_full_access_admin_member_and_bendahara(): void
    {
        $this->actingAs($this->user('organizer'))->get('/admin/memberships')->assertForbidden();
        $this->actingAs($this->user('admin_member'))->get('/admin/memberships')->assertOk();
        $this->actingAs($this->user('bendahara'))->get('/admin/memberships')->assertOk();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/memberships')->assertOk();
    }

    public function test_events_allow_admin_full_access_and_organizer(): void
    {
        $this->actingAs($this->user('bendahara'))->get('/admin/events')->assertForbidden();
        $this->actingAs($this->user('organizer'))->get('/admin/events')->assertOk();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/events')->assertOk();
    }

    public function test_categories_allow_admin_full_access_and_admin_laman(): void
    {
        $this->actingAs($this->user('organizer'))->get('/admin/categories')->assertForbidden();
        $this->actingAs($this->user('admin_laman'))->get('/admin/categories')->assertOk();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/categories')->assertOk();
    }

    public function test_galleries_allow_admin_full_access_and_admin_laman(): void
    {
        $this->actingAs($this->user('organizer'))->get('/admin/galleries')->assertForbidden();
        $this->actingAs($this->user('admin_laman'))->get('/admin/galleries')->assertOk();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/galleries')->assertOk();
    }

    public function test_organization_allow_admin_full_access_and_admin_laman(): void
    {
        $this->actingAs($this->user('organizer'))->get('/admin/organization')->assertForbidden();
        $this->actingAs($this->user('admin_laman'))->get('/admin/organization')->assertOk();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/organization')->assertOk();
    }

    public function test_sponsors_allow_admin_full_access_admin_laman_and_sponsor(): void
    {
        $this->actingAs($this->user('organizer'))->get('/admin/sponsors')->assertForbidden();
        $this->actingAs($this->user('admin_laman'))->get('/admin/sponsors')->assertOk();
        $this->actingAs($this->user('sponsor'))->get('/admin/sponsors')->assertOk();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/sponsors')->assertOk();
    }

    public function test_merchandise_allow_admin_full_access_admin_laman_and_merchandise(): void
    {
        $this->actingAs($this->user('sponsor'))->get('/admin/merchandise')->assertForbidden();
        $this->actingAs($this->user('admin_laman'))->get('/admin/merchandise')->assertOk();
        $this->actingAs($this->user('merchandise'))->get('/admin/merchandise')->assertOk();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/merchandise')->assertOk();
    }

    public function test_payments_allow_admin_full_access_and_bendahara(): void
    {
        $this->actingAs($this->user('organizer'))->get('/admin/payments')->assertForbidden();
        $this->actingAs($this->user('bendahara'))->get('/admin/payments')->assertOk();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/payments')->assertOk();
    }

    public function test_bookkeepings_allow_admin_full_access_and_bendahara(): void
    {
        $this->actingAs($this->user('organizer'))->get('/admin/bookkeepings')->assertForbidden();
        $this->actingAs($this->user('bendahara'))->get('/admin/bookkeepings')->assertOk();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/bookkeepings')->assertOk();
    }

    public function test_attendance_allow_admin_full_access_and_admin_laman(): void
    {
        $this->actingAs($this->user('bendahara'))->get('/admin/attendance/report')->assertForbidden();
        $this->actingAs($this->user('admin_laman'))->get('/admin/attendance/report')->assertOk();
        $this->actingAs($this->user('admin_full_access'))->get('/admin/attendance/report')->assertOk();
    }
}

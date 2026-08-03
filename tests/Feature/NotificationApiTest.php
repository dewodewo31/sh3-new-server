<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use App\Notifications\AdminNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }

    public function test_can_list_notifications(): void
    {
        $this->user->notify(new AdminNotification('Judul notifikasi', 'Isi notifikasi'));

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Judul notifikasi')
            ->assertJsonPath('data.0.body', 'Isi notifikasi')
            ->assertJsonPath('data.0.icon', 'bell')
            ->assertJsonPath('data.0.read_at', null);
    }

    public function test_list_is_limited_to_twenty(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->user->notify(new AdminNotification("Notifikasi {$i}", 'Isi'));
        }

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('unread_count', 25);
    }

    public function test_can_get_unread_count(): void
    {
        $this->user->notify(new AdminNotification('A', 'B'));
        $this->user->notify(new AdminNotification('C', 'D'));

        $this->user->notifications()->first()->markAsRead();

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    public function test_can_mark_notification_as_read(): void
    {
        $this->user->notify(new AdminNotification('A', 'B'));
        $notification = $this->user->notifications()->first();

        $this->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('message', 'Notifikasi ditandai dibaca.');

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(0, $this->user->unreadNotifications()->count());
    }

    public function test_marking_unknown_notification_returns_404(): void
    {
        $this->postJson('/api/v1/notifications/00000000-0000-0000-0000-000000000000/read')
            ->assertNotFound();
    }

    public function test_cannot_mark_other_users_notification_as_read(): void
    {
        $other = User::factory()->create();
        $other->notify(new AdminNotification('A', 'B'));
        $notification = $other->notifications()->first();

        $this->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertNotFound();
    }

    public function test_can_mark_all_notifications_as_read(): void
    {
        $this->user->notify(new AdminNotification('A', 'B'));
        $this->user->notify(new AdminNotification('C', 'D'));

        $this->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('message', 'Semua notifikasi ditandai dibaca.');

        $this->assertSame(0, $this->user->unreadNotifications()->count());
    }

    public function test_notify_participant_creates_notification_for_active_user(): void
    {
        $participant = Participant::factory()->create(['user_id' => $this->user->id]);

        app(NotificationService::class)->notifyParticipant($participant, 'Judul', 'Isi');

        $this->assertSame(1, $this->user->notifications()->count());
        $this->assertSame('Judul', $this->user->notifications()->first()->data['title']);
    }

    public function test_notify_participant_skips_participant_without_user(): void
    {
        $participant = Participant::factory()->create(['user_id' => null]);

        app(NotificationService::class)->notifyParticipant($participant, 'Judul', 'Isi');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_notify_participant_skips_inactive_user(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);
        $participant = Participant::factory()->create(['user_id' => $inactive->id]);

        app(NotificationService::class)->notifyParticipant($participant, 'Judul', 'Isi');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_admin_can_view_all_notifications_page(): void
    {
        $this->user->notify(new AdminNotification('Judul notifikasi', 'Isi notifikasi'));

        $this->get('/admin/notifications')
            ->assertOk()
            ->assertSee('Semua Notifikasi')
            ->assertSee('Judul notifikasi')
            ->assertSee('Isi notifikasi');
    }

    public function test_notifications_page_does_not_limit_to_twenty(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => AdminNotification::class,
                'data' => ['title' => "Notifikasi {$i}", 'body' => 'Isi', 'icon' => 'bell', 'url' => null],
                'created_at' => now()->addMinutes($i),
            ]);
        }

        $pageOne = $this->get('/admin/notifications');

        $pageOne->assertOk()
            ->assertSee('Notifikasi 24')
            ->assertSee('25 total')
            ->assertSee('Notifikasi 10')
            ->assertSee('page=2');

        $pageTwo = $this->get('/admin/notifications?page=2');

        $pageTwo->assertOk()
            ->assertSee('Notifikasi 9')
            ->assertSee('Notifikasi 0')
            ->assertDontSee('Notifikasi 24');
    }

    public function test_web_notifications_page_requires_auth(): void
    {
        $this->app['auth']->forgetGuards();

        $this->get('/admin/notifications')->assertRedirect('/login');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Merchandise;
use App\Models\MerchandiseOrder;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Participant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->participant = Participant::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    private function makePendingPayment(): Payment
    {
        $merchandise = Merchandise::factory()->create(['stock' => 10, 'price' => 100000]);

        $order = $merchandise->orders()->create([
            'participant_id' => $this->participant->id,
            'customer_name' => 'Andi',
            'customer_contact' => '08123456789',
            'size' => 'M',
            'quantity' => 2,
            'total_price' => 200000,
            'payment_status' => 'pending',
        ]);

        return Payment::create([
            'participant_id' => $this->participant->id,
            'invoice_number' => 'INV/'.now()->format('Ymd').'/TEST123',
            'payment_type' => 'merchandise',
            'paymentable_type' => MerchandiseOrder::class,
            'paymentable_id' => $order->id,
            'amount' => 200000,
            'payment_method' => 'transfer',
            'payment_proof' => null,
            'status' => 'pending',
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/payments/confirm/1')->assertUnauthorized();
    }

    public function test_participant_cannot_confirm_payment(): void
    {
        Sanctum::actingAs($this->user);
        $payment = $this->makePendingPayment();

        $this->postJson('/api/v1/payments/confirm/'.$payment->id)
            ->assertForbidden();
    }

    public function test_bendahara_can_confirm_payment(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara']);
        Sanctum::actingAs($bendahara);

        $payment = $this->makePendingPayment();

        $this->postJson('/api/v1/payments/confirm/'.$payment->id)
            ->assertOk()
            ->assertJsonPath('message', 'Pembayaran dikonfirmasi');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'confirmed',
            'confirmed_by' => $bendahara->id,
        ]);

        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertSame('paid', $payment->paymentable->fresh()->payment_status);
    }

    public function test_confirm_nonexistent_payment_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin_full_access']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/payments/confirm/99999')->assertNotFound();
    }
}

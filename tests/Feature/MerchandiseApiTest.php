<?php

namespace Tests\Feature;

use App\Models\Merchandise;
use App\Models\MerchandiseOrder;
use App\Models\Participant;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MerchandiseApiTest extends TestCase
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

        Sanctum::actingAs($this->user);
    }

    private function makeMerchandise(array $attributes = []): Merchandise
    {
        return Merchandise::factory()->create($attributes);
    }

    private function orderPayload(Merchandise $merchandise): array
    {
        return [
            'merchandise_id' => $merchandise->id,
            'customer_name' => 'Andi',
            'customer_contact' => '08123456789',
            'size' => 'M',
            'quantity' => 2,
        ];
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/merchandise/order', [])->assertUnauthorized();
        $this->getJson('/api/v1/merchandise/orders')->assertUnauthorized();
    }

    public function test_can_list_available_merchandise(): void
    {
        $this->makeMerchandise();
        $this->makeMerchandise(['status' => 'sold_out', 'stock' => 0]);
        $this->makeMerchandise(['status' => 'discontinued']);

        $this->getJson('/api/v1/merchandise')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_merchandise_detail(): void
    {
        $merchandise = $this->makeMerchandise(['name' => 'SH3 Jersey']);

        $this->getJson('/api/v1/merchandise/'.$merchandise->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'SH3 Jersey');
    }

    public function test_show_merchandise_not_found_returns_404(): void
    {
        $this->getJson('/api/v1/merchandise/99999')->assertNotFound();
    }

    public function test_can_order_merchandise(): void
    {
        $merchandise = $this->makeMerchandise([
            'stock' => 10,
            'price' => 150000,
            'size_options' => ['S', 'M', 'L'],
        ]);

        $response = $this->postJson('/api/v1/merchandise/order', $this->orderPayload($merchandise));

        $response->assertCreated()
            ->assertJsonPath('message', 'Order berhasil dibuat')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.total_price', '300000.00')
            ->assertJsonPath('data.merchandise.id', $merchandise->id);

        $this->assertSame(8, $merchandise->fresh()->stock);

        $order = $this->participant->merchandiseOrders()->first();

        $this->assertNotNull($order);
        $this->assertSame('pending', $order->payment_status);
        $this->assertNotNull($order->payment_id);
        $this->assertDatabaseHas('payments', [
            'id' => $order->payment_id,
            'participant_id' => $this->participant->id,
            'payment_type' => 'merchandise',
            'paymentable_id' => $order->id,
            'paymentable_type' => MerchandiseOrder::class,
            'amount' => '300000.00',
            'status' => 'pending',
        ]);
    }

    public function test_order_rejects_when_stock_insufficient(): void
    {
        $merchandise = $this->makeMerchandise(['stock' => 1]);

        $this->postJson('/api/v1/merchandise/order', $this->orderPayload($merchandise))
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', 'Stok tidak mencukupi.');
    }

    public function test_order_rejects_invalid_size(): void
    {
        $merchandise = $this->makeMerchandise(['size_options' => ['S', 'L']]);

        $payload = $this->orderPayload($merchandise);
        $payload['size'] = 'XXL';

        $this->postJson('/api/v1/merchandise/order', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors.size.0', 'Ukuran tidak tersedia untuk merchandise ini.');
    }

    public function test_order_rejects_missing_fields(): void
    {
        $this->postJson('/api/v1/merchandise/order', [])->assertUnprocessable();
    }

    public function test_order_requires_participant(): void
    {
        $this->user->participants()->delete();
        $merchandise = $this->makeMerchandise();

        $this->postJson('/api/v1/merchandise/order', $this->orderPayload($merchandise))
            ->assertNotFound();
    }

    public function test_can_list_my_orders(): void
    {
        $merchandise = $this->makeMerchandise(['stock' => 10]);
        $this->postJson('/api/v1/merchandise/order', $this->orderPayload($merchandise));

        $otherUser = User::factory()->create();
        $otherParticipant = Participant::factory()->create(['user_id' => $otherUser->id]);
        $otherMerchandise = $this->makeMerchandise(['stock' => 10]);
        $otherMerchandise->orders()->create([
            'participant_id' => $otherParticipant->id,
            'customer_name' => 'Budi',
            'customer_contact' => '0812',
            'size' => 'M',
            'quantity' => 1,
            'total_price' => 100000,
            'payment_status' => 'pending',
        ]);

        $this->getJson('/api/v1/merchandise/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_order_detail(): void
    {
        $merchandise = $this->makeMerchandise(['stock' => 10]);
        $response = $this->postJson('/api/v1/merchandise/order', $this->orderPayload($merchandise));
        $orderId = $response->json('data.id');

        $this->getJson('/api/v1/merchandise/orders/'.$orderId)
            ->assertOk()
            ->assertJsonPath('data.id', $orderId);
    }

    public function test_cannot_show_others_order_detail(): void
    {
        $otherUser = User::factory()->create();
        $otherParticipant = Participant::factory()->create(['user_id' => $otherUser->id]);
        $merchandise = $this->makeMerchandise(['stock' => 10]);
        $order = $merchandise->orders()->create([
            'participant_id' => $otherParticipant->id,
            'customer_name' => 'Budi',
            'customer_contact' => '0812',
            'size' => 'M',
            'quantity' => 1,
            'total_price' => 100000,
            'payment_status' => 'pending',
        ]);

        $this->getJson('/api/v1/merchandise/orders/'.$order->id)->assertNotFound();
        $this->postJson('/api/v1/merchandise/orders/'.$order->id.'/cancel')->assertNotFound();
    }

    public function test_can_cancel_pending_order_restores_stock(): void
    {
        $merchandise = $this->makeMerchandise(['stock' => 10]);
        $response = $this->postJson('/api/v1/merchandise/order', $this->orderPayload($merchandise));
        $orderId = $response->json('data.id');

        $this->postJson('/api/v1/merchandise/orders/'.$orderId.'/cancel')
            ->assertOk()
            ->assertJsonPath('message', 'Order dibatalkan.');

        $this->assertSame(10, $merchandise->fresh()->stock);
        $this->assertSame('cancelled', $this->participant->merchandiseOrders()->first()->payment_status);
    }

    public function test_cannot_cancel_paid_order(): void
    {
        $merchandise = $this->makeMerchandise(['stock' => 10]);
        $response = $this->postJson('/api/v1/merchandise/order', $this->orderPayload($merchandise));
        $orderId = $response->json('data.id');

        $this->participant->merchandiseOrders()->first()->update(['payment_status' => 'paid']);

        $this->postJson('/api/v1/merchandise/orders/'.$orderId.'/cancel')
            ->assertUnprocessable()
            ->assertJsonPath('errors.payment_status.0', 'Order hanya bisa dibatalkan saat status pending.');
    }

    public function test_can_upload_payment_proof(): void
    {
        Storage::fake('public');

        $merchandise = $this->makeMerchandise(['stock' => 10]);
        $response = $this->postJson('/api/v1/merchandise/order', $this->orderPayload($merchandise));
        $orderId = $response->json('data.id');

        $this->post('/api/v1/merchandise/orders/'.$orderId.'/payment', [
            'payment_proof' => UploadedFile::fake()->image('proof.png'),
            'payment_method' => 'transfer',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', 'Bukti pembayaran berhasil diunggah.');

        $order = $this->participant->merchandiseOrders()->first();
        $this->assertNotNull($order->payment->payment_proof);
        $this->assertSame('transfer', $order->payment->payment_method);
    }

    public function test_upload_payment_rejects_invalid_file(): void
    {
        $merchandise = $this->makeMerchandise(['stock' => 10]);
        $response = $this->postJson('/api/v1/merchandise/order', $this->orderPayload($merchandise));
        $orderId = $response->json('data.id');

        $this->post('/api/v1/merchandise/orders/'.$orderId.'/payment', [], ['Accept' => 'application/json'])
            ->assertUnprocessable();
    }

    public function test_cannot_upload_payment_for_others_order(): void
    {
        Storage::fake('public');

        $otherUser = User::factory()->create();
        $otherParticipant = Participant::factory()->create(['user_id' => $otherUser->id]);
        $merchandise = $this->makeMerchandise(['stock' => 10]);
        $order = $merchandise->orders()->create([
            'participant_id' => $otherParticipant->id,
            'customer_name' => 'Budi',
            'customer_contact' => '0812',
            'size' => 'M',
            'quantity' => 1,
            'total_price' => 100000,
            'payment_status' => 'pending',
        ]);

        $this->post('/api/v1/merchandise/orders/'.$order->id.'/payment', [
            'payment_proof' => UploadedFile::fake()->image('proof.png'),
        ], ['Accept' => 'application/json'])
            ->assertNotFound();
    }

    public function test_confirmed_payment_marks_order_paid(): void
    {
        $merchandise = $this->makeMerchandise(['stock' => 10]);
        $response = $this->postJson('/api/v1/merchandise/order', $this->orderPayload($merchandise));
        $orderId = $response->json('data.id');

        $order = $this->participant->merchandiseOrders()->findOrFail($orderId);
        $bendahara = User::factory()->create(['role' => 'bendahara']);

        app(PaymentService::class)->confirmPayment($order->payment, $bendahara->id);

        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}

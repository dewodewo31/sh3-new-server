<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Merchandise Redemption (Model B, per merchandise).
 *
 * `merchandise`:
 *   points_required      — flat points earned/required PER UNIT to redeem.
 *   price_after_points   — the cash the participant pays PER UNIT after the
 *                          point discount. (price - price_after_points) = the
 *                          discount PER UNIT that points cover.
 *   Both nullable; a row with points_required NULL is NOT point-redeemable.
 *   Config guard: price_after_points must be >= 0 and <= price (enforced in
 *   MerchandiseRequest/validation), so a discount can never exceed the price.
 *
 * `merchandise_orders` snapshot columns — captured at order creation so later
 *   price/point-config changes never change what a pending/paid order owes.
 *   total_price is re-aimed to the FINAL cash payable (after point discount)
 *   so the existing (server-side) payment amount logic stays consistent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchandise', function (Blueprint $table) {
            $table->integer('points_required')->nullable()->after('price');
            $table->decimal('price_after_points', 12, 2)->nullable()->after('points_required');
        });

        Schema::table('merchandise_orders', function (Blueprint $table) {
            $table->integer('points_used')->nullable()->after('quantity');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('total_price');
            // Snapshots are redemption metadata; nullable so plain cash orders
            // created outside MerchandiseService::createOrder (factories, seeds)
            // still succeed. createOrder always populates them.
            $table->decimal('unit_price_snapshot', 12, 2)->nullable()->after('total_price');
            $table->integer('quantity_snapshot')->nullable()->after('unit_price_snapshot');
            $table->integer('points_per_unit_snapshot')->nullable()->after('quantity_snapshot');
            $table->decimal('discount_per_unit_snapshot', 12, 2)->nullable()->after('points_per_unit_snapshot');
            $table->decimal('cash_amount_snapshot', 12, 2)->nullable()->after('discount_per_unit_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('merchandise_orders', function (Blueprint $table) {
            $table->dropColumn([
                'points_used',
                'discount_amount',
                'unit_price_snapshot',
                'quantity_snapshot',
                'points_per_unit_snapshot',
                'discount_per_unit_snapshot',
                'cash_amount_snapshot',
            ]);
        });

        Schema::table('merchandise', function (Blueprint $table) {
            $table->dropColumn(['points_required', 'price_after_points']);
        });
    }
};

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryDocument extends Model
{
    public const TYPE_LOAN_AGREEMENT = 'loan_agreement';

    public const TYPE_HANDOVER_RECEIPT = 'handover_receipt';

    public const TYPE_BORROWER_ID = 'borrower_id';

    public const TYPE_PURCHASE_INVOICE = 'purchase_invoice';

    public const TYPE_WARRANTY = 'warranty';

    public const TYPE_MAINTENANCE = 'maintenance';

    public const TYPE_RETURN_RECEIPT = 'return_receipt';

    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_LOAN_AGREEMENT,
        self::TYPE_HANDOVER_RECEIPT,
        self::TYPE_BORROWER_ID,
        self::TYPE_PURCHASE_INVOICE,
        self::TYPE_WARRANTY,
        self::TYPE_MAINTENANCE,
        self::TYPE_RETURN_RECEIPT,
        self::TYPE_OTHER,
    ];

    protected $guarded = [];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(InventoryLoan::class, 'inventory_loan_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

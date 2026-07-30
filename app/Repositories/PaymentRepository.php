<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository extends BaseRepository
{
    public function __construct(Payment $payment)
    {
        parent::__construct($payment);
    }

    public function findByInvoice(string $invoiceNumber)
    {
        return $this->findFirstBy('invoice_number', $invoiceNumber);
    }

    public function findPending()
    {
        return $this->model->where('status', 'pending')->get();
    }

    public function findByParticipant(int $participantId)
    {
        return $this->model->where('participant_id', $participantId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginateWithParticipant(int $perPage = 15)
    {
        return $this->model->with('participant')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

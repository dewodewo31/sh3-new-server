<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an EARN reversal would push a participant's point_balance below
 * zero. The caller must NOT silently clamp — it must surface the condition and
 * route it to the audited technical/admin adjustment workflow instead.
 */
class PointReversalBlockedException extends RuntimeException
{
    public function __construct(string $message = 'Reversal dibatalkan: saldo poin tidak mencukupi (perlu penyesuaian admin).')
    {
        parent::__construct($message);
    }
}

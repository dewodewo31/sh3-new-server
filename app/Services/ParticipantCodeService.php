<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use OverflowException;

class ParticipantCodeService
{
    public function next(string $prefix): string
    {
        return DB::transaction(function () use ($prefix): string {
            $row = DB::table('participant_code_sequences')
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                DB::table('participant_code_sequences')->insert(['prefix' => $prefix]);
                $row = DB::table('participant_code_sequences')
                    ->where('prefix', $prefix)
                    ->lockForUpdate()
                    ->first();
            }

            $next = (int) $row->last_value;
            $code = '';

            do {
                $next++;

                if ($next > 9999) {
                    // ponytail: cap 9999/prefix from user's 4-digit format; raising the cap requires a format change.
                    throw new OverflowException("Participant code sequence exhausted for prefix '{$prefix}': max 9999.");
                }

                $code = $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            } while ($this->codeInUse($code));

            DB::table('participant_code_sequences')
                ->where('prefix', $prefix)
                ->update(['last_value' => $next]);

            return $code;
        });
    }

    /**
     * Codes imported from legacy systems bypass the sequence, so every fresh
     * allocation skips values already present in either identity column.
     */
    private function codeInUse(string $code): bool
    {
        return DB::table('participants')
            ->where('hash_id', $code)
            ->orWhere('non_member_code', $code)
            ->exists();
    }
}

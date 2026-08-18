<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Import peserta SH3 dari data pendaftaran (spreadsheet) ke tabel participants.
 *
 * Idempotent: participant diidentifikasi lewat user-nya (username = identitas
 * login stabil), bukan hash_id (diberi otomatis oleh hook creating,
 * tidak bisa ditebak dari key sumber). Bisa dijalankan ulang tanpa duplicate.
 *
 *   php artisan db:seed --class=Sh3ParticipantImportSeeder
 *
 * Login participant = username + password (hashed via Hash::make / cast 'hashed').
 */
class Sh3ParticipantImportSeeder extends Seeder
{
    public array $summary = [
        'total' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'duplicate' => 0,
        'conflict' => 0,
        'failed' => 0,
    ];

    public array $conflicts = [];

    public array $errors = [];

    public function run(): void
    {
        $rows = $this->sourceData();
        $this->summary['total'] = count($rows);

        $usedEmails = [];
        $usedUsernames = [];

        DB::transaction(function () use ($rows, &$usedEmails, &$usedUsernames) {
            Participant::firstOrCreate(
                ['hash_id' => Participant::OTS_AGGREGATOR_CODE],
                [
                    'name' => 'Manual OTS NON MEMBER',
                    'is_active' => true,
                    'email' => 'manual.ots@sh3.com',
                    'phone' => null,
                ]
            );

            foreach ($rows as $row) {
                try {
                    DB::transaction(function () use ($row, &$usedEmails, &$usedUsernames) {
                        $this->importRow($row, $usedEmails, $usedUsernames);
                    });
                } catch (\Throwable $e) {
                    $this->summary['failed']++;
                    $this->errors[] = [
                        'hash_id' => $row['hash_id'],
                        'name' => $row['name'],
                        'field' => 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        $this->printSummary();
    }

    protected function importRow(array $row, array &$usedEmails, array &$usedUsernames): void
    {
        $sourceKey = (string) $row['hash_id'];
        $name = $row['name'];
        $phone = $row['phone'] !== '' ? $row['phone'] : null;

        // Idempotency keyed via the participant's user: username is the stable
        // login identity, whereas hash_id is hook-assigned (NM\d{4}) and
        // not predictable from the legacy source key. Both the raw and normalized
        // username are tried so a re-run still matches rows whose username was
        // normalized (e.g. 'Budi Kang' -> 'Budi_Kang').
        $existing = Participant::with('user')->whereHas('user', function ($q) use ($row, $sourceKey) {
            $q->where('username', trim($row['username']))
                ->orWhere('username', $this->normalizeUsername($row['username'], $sourceKey));
        })->first();
        $isDuplicate = $existing !== null;

        if ($isDuplicate) {
            $this->summary['duplicate']++;
        }

        $email = $this->resolveEmail($row['email'], $sourceKey, $name, $usedEmails, $existing);
        $username = $this->resolveUsername($row['username'], $sourceKey, $name, $usedUsernames, $existing);

        $user = $this->findOrCreateUser($username, $name, $email, $row['password'], $existing);

        $participantData = [
            'user_id' => $user->id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'is_active' => true,
        ];

        if ($isDuplicate) {
            $existing->update($participantData);
            $this->summary['updated']++;
        } else {
            // hash_id deliberately NOT set here: the model's creating
            // hook assigns it from the sequence (membership_type 'none' -> NM\d{4}),
            // keeping imported codes consistent with the backfill. The legacy
            // source key ($sourceKey) is NOT a valid hash_id.
            Participant::create(array_merge($participantData, [
                'membership_type' => 'none',
                'total_events_participated' => 0,
                'created_at' => $this->parseTimestamp($row['timestamp']),
                'updated_at' => $this->parseTimestamp($row['timestamp']),
            ]));
            $this->summary['inserted']++;
        }
    }

    protected function resolveEmail(string $email, string $sourceKey, string $name, array &$usedEmails, ?Participant $existing): string
    {
        $email = trim($email);

        if ($email === '') {
            $this->recordConflict($sourceKey, $name, 'email', 'email kosong, pakai dummy email');

            return $this->dummyEmail($sourceKey);
        }

        // Jika ini sudah email milik participant tersebut (re-run), tidak dianggap conflict.
        if ($existing && $existing->email === $email) {
            $usedEmails[$email] = true;

            return $email;
        }

        $taken = User::where('email', $email)
            ->when($existing?->user_id, fn ($q) => $q->where('id', '!=', $existing->user_id))
            ->exists()
            || Participant::where('email', $email)
                ->when($existing?->id, fn ($q) => $q->where('id', '!=', $existing->id))
                ->exists()
            || isset($usedEmails[$email]);

        if ($taken) {
            $this->recordConflict($sourceKey, $name, 'email', "email {$email} sudah dipakai, pakai dummy email");

            return $this->dummyEmail($sourceKey);
        }

        $usedEmails[$email] = true;

        return $email;
    }

    protected function resolveUsername(string $username, string $sourceKey, string $name, array &$usedUsernames, ?Participant $existing): string
    {
        $username = trim($username);

        if ($username === '') {
            $this->recordConflict($sourceKey, $name, 'username', 'username kosong, generate dari hash_id');
            $username = 'participant_'.$sourceKey;
        }

        if (! preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $this->recordConflict($sourceKey, $name, 'username', "username '{$username}' melanggar format a-z0-9_ (3-30), dinormalisasi");
            $username = $this->normalizeUsername($username, $sourceKey);
        }

        // Jika ini sudah username milik user participant tersebut (re-run), tidak dianggap conflict.
        if ($existing?->user && $existing->user->username === $username) {
            $usedUsernames[$username] = true;

            return $username;
        }

        $taken = User::where('username', $username)
            ->when($existing?->user_id, fn ($q) => $q->where('id', '!=', $existing->user_id))
            ->exists()
            || isset($usedUsernames[$username]);

        if ($taken) {
            $this->recordConflict($sourceKey, $name, 'username', "username '{$username}' sudah dipakai, diberi suffix");
            $base = $username;
            $suffix = 1;
            do {
                $candidate = substr($base, 0, 30 - strlen((string) $suffix) - 1).'_'.$suffix;
                $suffix++;
            } while (User::where('username', $candidate)
                ->when($existing?->user_id, fn ($q) => $q->where('id', '!=', $existing->user_id))
                ->exists()
                || isset($usedUsernames[$candidate]));

            $username = $candidate;
        }

        $usedUsernames[$username] = true;

        return $username;
    }

    protected function findOrCreateUser(string $username, string $name, string $email, string $password, ?Participant $existing): User
    {
        // Re-run: reuse user milik participant yang sudah ada.
        if ($existing?->user) {
            $existing->user->update([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password !== '' ? Hash::make($password) : $existing->user->password,
                'role' => 'participant',
                'is_active' => true,
            ]);

            return $existing->user;
        }

        $user = User::where('username', $username)->first();

        if ($user) {
            $user->update([
                'name' => $name,
                'email' => $email,
                'password' => $password !== '' ? Hash::make($password) : $user->password,
                'role' => 'participant',
                'is_active' => true,
            ]);

            return $user;
        }

        return User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password !== '' ? Hash::make($password) : Hash::make(Str::random(32)),
            'role' => 'participant',
            'is_active' => true,
        ]);
    }

    protected function dummyEmail(string $sourceKey): string
    {
        return 'dummy+'.$sourceKey.'@example.com';
    }

    protected function normalizeUsername(string $username, string $sourceKey): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9_]/', '_', $username);
        $normalized = trim($normalized, '_');

        if (strlen($normalized) < 3) {
            $normalized = 'participant_'.$sourceKey;
        }

        if (strlen($normalized) > 30) {
            $normalized = substr($normalized, 0, 30);
        }

        return $normalized;
    }

    protected function recordConflict(string $sourceKey, string $name, string $field, string $message): void
    {
        $this->summary['conflict']++;
        $this->conflicts[] = [
            'hash_id' => $sourceKey,
            'name' => $name,
            'field' => $field,
            'error' => $message,
        ];
    }

    protected function parseTimestamp(string $timestamp): Carbon
    {
        return Carbon::createFromFormat('n/j/Y H:i:s', $timestamp);
    }

    protected function printSummary(): void
    {
        $this->line('');
        $this->line('Participant Import Summary');
        $this->line('--------------------------');
        $this->line('Total source data : '.$this->summary['total']);
        $this->line('Inserted          : '.$this->summary['inserted']);
        $this->line('Updated           : '.$this->summary['updated']);
        $this->line('Skipped           : '.$this->summary['skipped']);
        $this->line('Duplicate         : '.$this->summary['duplicate']);
        $this->line('Conflict          : '.$this->summary['conflict']);
        $this->line('Failed            : '.$this->summary['failed']);

        if ($this->conflicts !== []) {
            $this->line('');
            $this->line('Conflicts / Notes:');
            $this->line('Participant Code | Nama | Field | Error');
            foreach ($this->conflicts as $c) {
                $this->line("{$c['hash_id']} | {$c['name']} | {$c['field']} | {$c['error']}");
            }
        }

        if ($this->errors !== []) {
            $this->line('');
            $this->line('Errors:');
            $this->line('Participant Code | Nama | Field | Error');
            foreach ($this->errors as $e) {
                $this->line("{$e['hash_id']} | {$e['name']} | {$e['field']} | {$e['error']}");
            }
        }
    }

    protected function line(string $message): void
    {
        if ($this->command) {
            $this->command->line($message);
        } else {
            echo $message.PHP_EOL;
        }
    }

    public function sourceData(): array
    {
        return [
            ['timestamp' => '8/12/2026 12:43:57', 'name' => 'Cohan Luchas', 'hash_id' => '3690', 'username' => 'Bengkiam', 'password' => '334477', 'phone' => '81617180189', 'email' => ''],
            ['timestamp' => '8/12/2026 13:52:11', 'name' => 'Riri', 'hash_id' => '3749', 'username' => 'Riri', 'password' => 'Riri2703', 'phone' => '81347286262', 'email' => ''],
            ['timestamp' => '8/12/2026 13:53:52', 'name' => 'Moka', 'hash_id' => '3317', 'username' => 'Moka', 'password' => 'Moka123', 'phone' => '85250280800', 'email' => ''],
            ['timestamp' => '8/12/2026 14:11:19', 'name' => 'Yuliani', 'hash_id' => '2976', 'username' => 'Yuliani', 'password' => '123456', 'phone' => '81350673333', 'email' => 'tanyuliani800@gmail.com'],
            ['timestamp' => '8/12/2026 14:24:48', 'name' => 'Subhan Agus', 'hash_id' => '2790', 'username' => 'Agusoppa', 'password' => 'Agus1973', 'phone' => '85250789247', 'email' => 'subhanagus0@gmail.com'],
            ['timestamp' => '8/13/2026 9:18:45', 'name' => 'Ming', 'hash_id' => '2898', 'username' => 'Ming2898', 'password' => 'Ming1010', 'phone' => '811555882', 'email' => 'minardis@yahoo.com'],
            ['timestamp' => '8/13/2026 9:25:28', 'name' => 'Natalia/Afang', 'hash_id' => '2517', 'username' => 'AfangSh3', 'password' => 'NRaSh3', 'phone' => '811555878', 'email' => 'natalia.rosalie_1271@yahoo.com'],
            ['timestamp' => '8/13/2026 9:39:56', 'name' => 'Teddy Tarmidji', 'hash_id' => '2890', 'username' => 'Teddyt', 'password' => 'Atheng', 'phone' => '8125803738', 'email' => 'banjir06@gmail.com'],
            ['timestamp' => '8/13/2026 9:46:28', 'name' => 'Budi Kantono', 'hash_id' => '2903', 'username' => 'Budi Kang', 'password' => 'BudiKang88', 'phone' => '811586818', 'email' => 'indianatra@gmail.com'],
            ['timestamp' => '8/13/2026 10:08:41', 'name' => 'Natasya', 'hash_id' => '3796', 'username' => 'nataaaaaaa_', 'password' => 'Gagab123', 'phone' => '87812358871', 'email' => 'natasyamabe@gmail.com'],
            ['timestamp' => '8/13/2026 10:19:41', 'name' => 'ARI O', 'hash_id' => '3130', 'username' => 'ArioSH3', 'password' => 'SH3JAYA', 'phone' => '82157390548', 'email' => 'ariokmawanto@gmail.com'],
            ['timestamp' => '8/13/2026 10:27:09', 'name' => 'Glen', 'hash_id' => '3614', 'username' => 'Glenmario', 'password' => 'Glenmario25', 'phone' => '81244622652', 'email' => 'glenmario888@gmail.com'],
            ['timestamp' => '8/13/2026 11:57:01', 'name' => 'Aan', 'hash_id' => '3002', 'username' => 'Keanggotaansh3', 'password' => '123456', 'phone' => '811558856', 'email' => 'fkchandra35@gmail.com'],
            ['timestamp' => '8/13/2026 12:05:33', 'name' => 'Joms oentu', 'hash_id' => '2048', 'username' => 'Joms', 'password' => '220282', 'phone' => '8195508859', 'email' => 'jomsoentu08@gmail.com'],
            ['timestamp' => '8/13/2026 19:12:38', 'name' => 'Tan lie hui', 'hash_id' => '3180', 'username' => 'Lihui', 'password' => '123456', 'phone' => '82250585583', 'email' => 'tanliehui73@gmail.com'],
            ['timestamp' => '8/13/2026 20:18:37', 'name' => 'mc. susilowati', 'hash_id' => '3496', 'username' => 'mcsus3496', 'password' => '123456', 'phone' => '811552862', 'email' => 'srwongkojoyo@gmail.com'],
            ['timestamp' => '8/14/2026 17:59:30', 'name' => 'Megawati', 'hash_id' => '2429', 'username' => 'Ipau', 'password' => '202476', 'phone' => '8115510109', 'email' => 'megawati23tk@gmail.com'],
            ['timestamp' => '8/14/2026 18:01:14', 'name' => 'Hermawan sulistio', 'hash_id' => '2431', 'username' => 'Asing', 'password' => '202476', 'phone' => '811556349', 'email' => 'megawati23tk@gmail.com'],
            ['timestamp' => '8/14/2026 18:02:33', 'name' => 'Siti rohmah', 'hash_id' => '3788', 'username' => '888999', 'password' => '888999', 'phone' => '82352395622', 'email' => 'sitirohmah141182@gmail.com'],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventParticipantSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::whereIn('status', ['publish', 'ongoing', 'completed'])->get();

        if ($events->isEmpty()) {
            return;
        }

        $participants = Participant::all();

        if ($participants->isEmpty()) {
            return;
        }

        $bendahara = User::where('role', 'bendahara')->first()?->id ?? User::first()?->id;
        $adminBnh = User::where('role', 'admin_bnh')->first()?->id ?? User::first()?->id;

        foreach ($events as $event) {
            $count = min(8 + ($event->id % 9), $participants->count());
            $selected = $participants->take($count);
            $eventStart = $event->start_date;

            foreach ($selected as $participant) {
                $registrationType = 'free';
                $amount = 0;
                $isMembershipFree = false;

                if ($event->price > 0) {
                    if ($event->is_free_for_members && $participant->isMembershipActive()) {
                        $registrationType = 'membership';
                        $isMembershipFree = true;
                    } else {
                        $registrationType = 'paid';
                        $amount = $event->price;
                    }
                }

                $registration = EventParticipant::firstOrCreate(
                    ['event_id' => $event->id, 'participant_id' => $participant->id],
                    [
                        'registration_type' => $registrationType,
                        'amount' => $amount,
                        'payment_status' => $amount > 0 ? 'confirmed' : 'confirmed',
                        'is_membership_free' => $isMembershipFree,
                        'qr_code' => 'SH3-'.$event->id.'-'.$participant->id.'-'.strtoupper(substr(md5($event->id.$participant->id), 0, 8)),
                    ]
                );

                if ($registration->wasRecentlyCreated && $amount > 0) {
                    $payment = Payment::create([
                        'participant_id' => $participant->id,
                        'invoice_number' => 'INV/'.now()->format('Ymd').'/'.strtoupper(uniqid()),
                        'payment_type' => 'event_registration',
                        'paymentable_type' => EventParticipant::class,
                        'paymentable_id' => $registration->id,
                        'amount' => $amount,
                        'payment_method' => 'transfer',
                        'status' => 'confirmed',
                        'confirmed_by' => $bendahara,
                        'paid_at' => now()->subDays(rand(5, 20)),
                    ]);

                    $registration->update(['payment_id' => $payment->id]);
                }

                $attended = $event->status === 'completed' || ($event->status === 'ongoing' && ($participant->id % 2 === 1));

                if ($attended) {
                    $checkIn = (clone $eventStart)->subHours(rand(1, 3));
                    $checkOut = (clone $eventStart)->addHours(rand(1, 5));

                    Attendance::firstOrCreate(
                        ['event_participant_id' => $registration->id],
                        [
                            'check_in_time' => $checkIn,
                            'check_out_time' => $checkOut,
                            'status' => 'present',
                            'check_in_method' => 'qr_code',
                        ]
                    );

                    if ($registration->wasRecentlyCreated) {
                        AttendanceLog::firstOrCreate(
                            ['event_id' => $event->id, 'participant_id' => $participant->id, 'type' => 'check_in'],
                            [
                                'scan_time' => $checkIn,
                                'scanned_by' => $adminBnh,
                                'qr_code' => $registration->qr_code,
                            ]
                        );

                        AttendanceLog::firstOrCreate(
                            ['event_id' => $event->id, 'participant_id' => $participant->id, 'type' => 'check_out'],
                            [
                                'scan_time' => $checkOut,
                                'scanned_by' => $adminBnh,
                                'qr_code' => $registration->qr_code,
                            ]
                        );

                        $registration->update([
                            'is_attended' => true,
                            'check_in_at' => $checkIn,
                            'check_out_at' => $checkOut,
                        ]);
                    }
                }
            }
        }

        DB::table('participants')
            ->whereIn('id', EventParticipant::query()->select('participant_id')->distinct())
            ->update(['total_events_participated' => DB::raw('(SELECT COUNT(*) FROM event_participants WHERE event_participants.participant_id = participants.id)')]);
    }
}

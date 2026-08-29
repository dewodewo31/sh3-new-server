<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flat Point Configuration (SH3)
    |--------------------------------------------------------------------------
    |
    | Points are EARNED ONLY from a valid event attendance/check-in. There is
    | NO global "1 point = RpX" rate — redemption is configured per merchandise
    | (Model B). See plan .omo/plans/flat-point-redemption.md.
    |
    | non_member_rate: flat points earned per valid check-in for a participant
    | with NO active membership at check-in time. Defaults to 0 (non-members
    | earn nothing). The rate for a member is read from their ACTIVE
    | membership plan's point_per_event_checkin, resolved via membership
    | history (never the denormalized participants.membership_type cache).
    |
    */

    'non_member_rate' => (int) env('POINTS_NON_MEMBER_RATE', 0),

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Early Check-in Window
    |--------------------------------------------------------------------------
    |
    | Shift শুরু হওয়ার সর্বোচ্চ কত মিনিট আগে check-in করা যাবে।
    |
    | Example:
    | Shift: 09:00
    | Value: 120
    | Earliest check-in: 07:00
    |
    */

    'early_check_in_minutes' =>
        (int) env(
            'ATTENDANCE_EARLY_CHECK_IN_MINUTES',
            120
        ),


    /*
    |--------------------------------------------------------------------------
    | Automatic Checkout
    |--------------------------------------------------------------------------
    |
    | Scheduled end time-এর পরেও checkout না করলে কত মিনিট পরে system
    | automatic checkout করবে।
    |
    | Default 360 minutes = 6 hours.
    |
    */

    'auto_checkout_after_minutes' =>
        (int) env(
            'ATTENDANCE_AUTO_CHECKOUT_AFTER_MINUTES',
            360
        ),


    /*
    |--------------------------------------------------------------------------
    | Future Attendance Protection
    |--------------------------------------------------------------------------
    |
    | Future schedule Attendance table-এ আগেই generate হবে না।
    | Future schedule শুধু Shift Schedule page-এ দেখা যাবে।
    |
    */

    'prevent_future_sync' =>
        filter_var(
            env(
                'ATTENDANCE_PREVENT_FUTURE_SYNC',
                true
            ),
            FILTER_VALIDATE_BOOLEAN
        ),

];
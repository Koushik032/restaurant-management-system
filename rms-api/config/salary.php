<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Full Salary Late Limit
    |--------------------------------------------------------------------------
    |
    | Schedule start-এর সর্বোচ্চ 10 মিনিটের মধ্যে check-in করলে
    | full scheduled salary পাওয়া যাবে।
    |
    */

    'full_salary_late_limit_minutes' =>
        (int) env(
            'SALARY_FULL_LATE_LIMIT_MINUTES',
            10
        ),


    /*
    |--------------------------------------------------------------------------
    | Half Salary Late Limit
    |--------------------------------------------------------------------------
    |
    | 10 মিনিটের বেশি কিন্তু 180 মিনিট বা 3 ঘণ্টার মধ্যে check-in করলে
    | scheduled salary-এর 50% পাওয়া যাবে।
    |
    */

    'half_salary_late_limit_minutes' =>
        (int) env(
            'SALARY_HALF_LATE_LIMIT_MINUTES',
            180
        ),


    /*
    |--------------------------------------------------------------------------
    | Half Salary Percentage
    |--------------------------------------------------------------------------
    */

    'half_salary_percentage' =>
        (float) env(
            'SALARY_HALF_PERCENTAGE',
            50
        ),


    /*
    |--------------------------------------------------------------------------
    | Overtime Multiplier
    |--------------------------------------------------------------------------
    |
    | Default 1.0 মানে overtime hourly rate এবং regular hourly rate একই।
    |
    */

    'overtime_multiplier' =>
        (float) env(
            'SALARY_OVERTIME_MULTIPLIER',
            1
        ),

];
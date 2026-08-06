<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryDetail extends Model
{
    use HasFactory;


    public const TYPE_FULL =
        'full_salary';

    public const TYPE_HALF =
        'half_salary';

    public const TYPE_FULL_OVERTIME =
        'full_salary_overtime';

    public const TYPE_HALF_OVERTIME =
        'half_salary_overtime';

    public const TYPE_OVERTIME_ONLY =
        'overtime_only';

    public const TYPE_NO_SALARY =
        'no_salary';


    public const SOURCE_AUTOMATIC =
        'automatic';

    public const SOURCE_MANUAL =
        'manual';


    protected $fillable = [

        'salary_payroll_id',

        'attendance_id',

        'employee_id',

        'salary_date',

        'scheduled_minutes',

        'worked_minutes',

        'late_minutes',

        'break_minutes',

        'overtime_minutes',

        'hourly_rate',

        'salary_type',

        'regular_salary',

        'overtime_salary',

        'total_amount',

        'calculation_source',

        'notes',

        'updated_by',

    ];


    protected $casts = [

        'salary_date' =>
            'date',

        'scheduled_minutes' =>
            'integer',

        'worked_minutes' =>
            'integer',

        'late_minutes' =>
            'integer',

        'break_minutes' =>
            'integer',

        'overtime_minutes' =>
            'integer',

        'hourly_rate' =>
            'decimal:2',

        'regular_salary' =>
            'decimal:2',

        'overtime_salary' =>
            'decimal:2',

        'total_amount' =>
            'decimal:2',

    ];


    public static function allowedSalaryTypes(): array
    {
        return [

            self::TYPE_FULL,

            self::TYPE_HALF,

            self::TYPE_FULL_OVERTIME,

            self::TYPE_HALF_OVERTIME,

            self::TYPE_OVERTIME_ONLY,

            self::TYPE_NO_SALARY,

        ];
    }


    public function salaryPayroll(): BelongsTo
    {
        return $this->belongsTo(
            SalaryPayroll::class
        );
    }


    public function attendance(): BelongsTo
    {
        return $this->belongsTo(
            Attendance::class
        );
    }


    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }


    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }


    public function salaryTypeLabel(): string
    {
        return match ($this->salary_type) {

            self::TYPE_FULL =>
                'Full Salary',

            self::TYPE_HALF =>
                'Half Salary',

            self::TYPE_FULL_OVERTIME =>
                'Full Salary + Overtime',

            self::TYPE_HALF_OVERTIME =>
                'Half Salary + Overtime',

            self::TYPE_OVERTIME_ONLY =>
                'Overtime Salary Only',

            self::TYPE_NO_SALARY =>
                'No Salary',

            default =>
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        (string) $this->salary_type
                    )
                ),

        };
    }
}
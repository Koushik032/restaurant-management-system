<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SalaryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCalculateDailySalary extends Command
{
    protected $signature = 'salary:auto-calculate';

    protected $description =
        'Automatically calculate daily employee salary';

    public function __construct(
        private readonly SalaryService $salaryService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $date =
            Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | Find Admin
        |--------------------------------------------------------------------------
        */

        $admin =
            User::query()
                ->whereHas(
                    'role',
                    function ($query) {
                        $query->where(
                            'name',
                            'admin'
                        );
                    }
                )
                ->first();

        if (!$admin) {

            $this->error(
                'Admin user not found.'
            );

            return Command::FAILURE;
        }

        try {

            $result =
                $this->salaryService
                    ->processDailySalary(
                        $date,
                        $admin
                    );

            $this->info(
                'Daily salary calculation completed successfully.'
            );

            $this->table(
                [
                    'Date',
                    'Payroll Created',
                    'Payroll Updated',
                    'Details Created',
                    'Details Updated',
                    'Paid Skipped',
                ],
                [
                    [
                        $result['salary_date'],

                        $result['payroll_created'],

                        $result['payroll_updated'],

                        $result['details_created'],

                        $result['details_updated'],

                        $result['skipped_paid_payrolls'],
                    ],
                ]
            );

            return Command::SUCCESS;

        }
        catch (\Throwable $e) {

            report($e);

            $this->error(
                $e->getMessage()
            );

            return Command::FAILURE;
        }
    }
}
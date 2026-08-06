<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreShiftScheduleOverrideRequest;
use App\Http\Requests\Api\UpdateShiftScheduleOverrideRequest;
use App\Http\Resources\ShiftScheduleOverrideResource;
use App\Models\ShiftSchedule;
use App\Models\ShiftScheduleOverride;
use App\Services\ShiftScheduleService;
use Illuminate\Http\Request;

class ShiftScheduleOverrideController extends Controller
{
    public function __construct(
        private readonly ShiftScheduleService $shiftScheduleService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Override List for Schedule
    |--------------------------------------------------------------------------
    */

    public function index(
        ShiftSchedule $shiftSchedule
    ) {
        $overrides =
            $shiftSchedule
                ->overrides()
                ->with([
                    'creator',
                    'updater',
                ])
                ->orderByDesc(
                    'override_date'
                )
                ->get();

        return ShiftScheduleOverrideResource::collection(
            $overrides
        )->additional([

            'success' =>
                true,

            'message' =>
                'Schedule overrides loaded successfully.',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Override
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreShiftScheduleOverrideRequest $request,
        ShiftSchedule $shiftSchedule
    ) {
        $override =
            $this
                ->shiftScheduleService
                ->createOverride(
                    $shiftSchedule,
                    $request->validated(),
                    $request->user()
                );

        return (
            new ShiftScheduleOverrideResource(
                $override
            )
        )
            ->additional([

                'success' =>
                    true,

                'message' =>
                    $override->override_type === 'day_off'
                        ? 'One-day leave override created successfully.'
                        : 'One-day shift change created successfully.',

            ])
            ->response()
            ->setStatusCode(201);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Override
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateShiftScheduleOverrideRequest $request,
        ShiftScheduleOverride $shiftScheduleOverride
    ) {
        $override =
            $this
                ->shiftScheduleService
                ->updateOverride(
                    $shiftScheduleOverride,
                    $request->validated(),
                    $request->user()
                );

        return (
            new ShiftScheduleOverrideResource(
                $override
            )
        )->additional([

            'success' =>
                true,

            'message' =>
                'One-day schedule override updated successfully.',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Override
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        ShiftScheduleOverride $shiftScheduleOverride
    ) {
        $this
            ->shiftScheduleService
            ->deleteOverride(
                $shiftScheduleOverride,
                $request->user()
            );

        return response()->json([

            'success' =>
                true,

            'message' =>
                'Override removed. The regular schedule will apply again.',

        ]);
    }
}
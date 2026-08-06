<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform resource into array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,


            'user_id' =>
                (int) $this->user_id,


            /*
            |--------------------------------------------------------------------------
            | User Account Information
            |--------------------------------------------------------------------------
            */

            'user' =>
                $this->whenLoaded(
                    'user',
                    function () {

                        if (! $this->user) {
                            return null;
                        }

                        return [

                            'id' =>
                                (int) $this->user->id,


                            'name' =>
                                $this->user->name,


                            'username' =>
                                $this->user->username,


                            'email' =>
                                $this->user->email,


                            'is_active' =>
                                (bool) $this->user->is_active,


                            'blocked_at' =>
                                $this->user->blocked_at
                                    ?->toISOString(),


                            'account_status' =>
                                $this->getAccountStatus(),


                            'account_status_label' =>
                                $this->getAccountStatusLabel(),


                            'role' =>
                                $this->user->relationLoaded(
                                    'role'
                                )
                                    &&
                                    $this->user->role
                                        ? [

                                            'id' =>
                                                (int)
                                                $this->user
                                                    ->role
                                                    ->id,


                                            'name' =>
                                                $this->user
                                                    ->role
                                                    ->name,


                                            'label' =>
                                                $this->getRoleLabel(),

                                        ]
                                        : null,

                        ];

                    },
                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Flattened User Information
            |--------------------------------------------------------------------------
            |
            | Frontend table-এ সহজে ব্যবহার করার জন্য।
            |
            */

            'staff_name' =>
                $this->user?->name
                ??
                'Unknown Staff',


            'username' =>
                $this->user?->username,


            'email' =>
                $this->user?->email,


            'role_id' =>
                $this->user?->role_id
                    ? (int) $this->user->role_id
                    : null,


            'role_name' =>
                $this->user?->role?->name,


            'role_label' =>
                $this->getRoleLabel(),


            /*
            |--------------------------------------------------------------------------
            | Employee Information
            |--------------------------------------------------------------------------
            */

            'phone' =>
                $this->phone,


            'joining_date' =>
                $this->joining_date
                    ?->format(
                        'Y-m-d'
                    ),


            'joining_date_label' =>
                $this->joining_date
                    ?->format(
                        'd M Y'
                    ),


            /*
            |--------------------------------------------------------------------------
            | Hourly Rate
            |--------------------------------------------------------------------------
            */

            'hourly_rate' =>
                (float) $this->hourly_rate,


            'hourly_rate_formatted' =>
                $this->money(
                    $this->hourly_rate
                )
                .
                ' / hour',


            /*
            |--------------------------------------------------------------------------
            | Current Working Status
            |--------------------------------------------------------------------------
            */

            'current_status' =>
                $this->current_status,


            'current_status_label' =>
                $this->currentStatusLabel(),


            'status_updated_at' =>
                $this->status_updated_at
                    ?->toISOString(),


            'status_updated_at_label' =>
                $this->status_updated_at
                    ?->format(
                        'd M Y, h:i A'
                    ),


            /*
            |--------------------------------------------------------------------------
            | Account Status
            |--------------------------------------------------------------------------
            */

            'is_active' =>
                (bool) (
                    $this->user?->is_active
                    ??
                    false
                ),


            'is_blocked' =>
                $this->isBlocked(),


            'account_status' =>
                $this->getAccountStatus(),


            'account_status_label' =>
                $this->getAccountStatusLabel(),


            /*
            |--------------------------------------------------------------------------
            | Audit Information
            |--------------------------------------------------------------------------
            */

            'created_by' =>
                $this->whenLoaded(
                    'creator',
                    function () {

                        if (! $this->creator) {
                            return null;
                        }

                        return [

                            'id' =>
                                (int) $this->creator->id,


                            'name' =>
                                $this->creator->name,

                        ];

                    },
                    null
                ),


            'updated_by' =>
                $this->whenLoaded(
                    'updater',
                    function () {

                        if (! $this->updater) {
                            return null;
                        }

                        return [

                            'id' =>
                                (int) $this->updater->id,


                            'name' =>
                                $this->updater->name,

                        ];

                    },
                    null
                ),


            'created_at' =>
                $this->created_at
                    ?->toISOString(),


            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Role Label
    |--------------------------------------------------------------------------
    */

    private function getRoleLabel(): string
    {
        $roleName =
            $this->user?->role?->name;

        return match ($roleName) {

            'manager' =>
                'Manager',

            'waiter' =>
                'Waiter',

            'chef' =>
                'Chef',

            null, '' =>
                'No Role',

            default =>
                ucwords(
                    str_replace(
                        [
                            '_',
                            '-',
                        ],
                        ' ',
                        (string) $roleName
                    )
                ),

        };
    }


    /*
    |--------------------------------------------------------------------------
    | Account Status
    |--------------------------------------------------------------------------
    */

    private function isBlocked(): bool
    {
        if (! $this->user) {
            return true;
        }

        return (
            ! $this->user->is_active
            ||
            $this->user->blocked_at !== null
        );
    }


    private function getAccountStatus(): string
    {
        return $this->isBlocked()
            ? 'blocked'
            : 'active';
    }


    private function getAccountStatusLabel(): string
    {
        return $this->isBlocked()
            ? 'Blocked'
            : 'Active';
    }

    
    /*
    |--------------------------------------------------------------------------
    | Money Formatter
    |--------------------------------------------------------------------------
    */

    private function money(
        mixed $amount
    ): string {

        return '৳ '
            .
            number_format(
                (float) $amount,
                2
            );
    }
}
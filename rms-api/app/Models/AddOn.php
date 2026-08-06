<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddOn extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'add_on_name',
        'price',
        'description',
        'is_available',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function scopeAvailable(
        Builder $query
    ): Builder {
        return $query->where(
            'is_available',
            true
        );
    }

    public function scopeUnavailable(
        Builder $query
    ): Builder {
        return $query->where(
            'is_available',
            false
        );
    }

    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {
        $search = trim(
            (string) $search
        );

        if ($search === '') {
            return $query;
        }

        return $query->where(
            function (Builder $builder) use ($search): void {
                $builder
                    ->where(
                        'add_on_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'description',
                        'like',
                        "%{$search}%"
                    );
            }
        );
    }
}
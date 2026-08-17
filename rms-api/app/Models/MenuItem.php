<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MenuItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Menu Item Types
    |--------------------------------------------------------------------------
    */

    public const TYPE_REGULAR = 'regular';

    public const TYPE_COMBO = 'combo';

    public const TYPE_SET_MEAL = 'set_meal';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Fields
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'menu_category_id',
        'menu_name',
        'item_type',
        'price',
        'ingredients',
        'description',
        'image_path',
        'preparation_time',
        'is_available',
        'is_featured',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'menu_category_id' => 'integer',
        'price' => 'decimal:2',
        'preparation_time' => 'integer',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Appended Attributes
    |--------------------------------------------------------------------------
    */

    protected $appends = [
        'image_url',
        'item_type_label',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            MenuCategory::class,
            'menu_category_id'
        );
    }

    public function variants(): HasMany
    {
        return $this->hasMany(
            MenuItemVariant::class,
            'menu_item_id'
        );
    }

    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(
            AddOn::class,
            'menu_item_add_on',
            'menu_item_id',
            'add_on_id'
        );
    }
    /*
|--------------------------------------------------------------------------
| Recipe Mappings
|--------------------------------------------------------------------------
|
| A menu item may require multiple raw materials / ingredients.
|
*/

public function recipeMappings(): HasMany
{
    return $this->hasMany(
        RecipeMapping::class,
        'menu_item_id'
    );
}

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

public function getImageUrlAttribute(): ?string
{
    if (empty($this->image_path)) {
        return null;
    }

    return url(
        Storage::disk('public')->url(
            $this->image_path
        )
    );
}

    public function getItemTypeLabelAttribute(): string
    {
        return match ($this->item_type) {
            self::TYPE_COMBO => 'Combo',
            self::TYPE_SET_MEAL => 'Set Meal',
            default => 'Regular',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

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

    public function scopeFeatured(
        Builder $query
    ): Builder {
        return $query->where(
            'is_featured',
            true
        );
    }

    public function scopeOfType(
        Builder $query,
        ?string $type
    ): Builder {
        if (empty($type)) {
            return $query;
        }

        return $query->where(
            'item_type',
            $type
        );
    }

    public function scopeOfCategory(
        Builder $query,
        int|string|null $categoryId
    ): Builder {
        if (empty($categoryId)) {
            return $query;
        }

        return $query->where(
            'menu_category_id',
            $categoryId
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
            function (
                Builder $builder
            ) use ($search): void {
                $builder
                    ->where(
                        'menu_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'ingredients',
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

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function allowedTypes(): array
    {
        return [
            self::TYPE_REGULAR,
            self::TYPE_COMBO,
            self::TYPE_SET_MEAL,
        ];
    }

    public function hasImage(): bool
    {
        return !empty($this->image_path);
    }

    public function deleteImage(): void
    {
        if (!$this->hasImage()) {
            return;
        }

        Storage::disk('public')->delete(
            $this->image_path
        );

        $this->forceFill([
            'image_path' => null,
        ])->saveQuietly();
    }
}
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\IngredientsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $recipe_id
 * @property-read string $name
 * @property-read string $quantity
 * @property-read string $unit
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Ingredients extends Model
{
    /** @use HasFactory<IngredientsFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'recipe_id',
        'name',
        'quantity',
        'unit',
    ];

    /**
     * Get the recipe that owns the ingredient.
     *
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'recipe_id' => 'integer',
            'name' => 'string',
            'quantity' => 'string',
            'unit' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}

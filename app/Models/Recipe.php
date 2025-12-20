<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecipeVisibility;
use Carbon\CarbonInterface;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int|null $family_id
 * @property-read string $title
 * @property-read string $description
 * @property-read RecipeVisibility $visibility
 * @property-read string|null $image_path
 * @property-read int $prep_time
 * @property-read int $cook_time
 * @property-read int $servings
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'family_id',
        'title',
        'description',
        'visibility',
        'image_path',
        'prep_time',
        'cook_time',
        'servings',
    ];

    /**
     * Get the user that owns the recipe.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the family that the recipe belongs to.
     *
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get the ingredients for the recipe.
     *
     * @return HasMany<Ingredients, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredients::class);
    }

    /**
     * Get the steps for the recipe.
     *
     * @return HasMany<Step, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(Step::class)
            ->orderBy('step_number');
    }

    /**
     * Get the favorites for the recipe.
     *
     * @return HasMany<Favorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * The users who favorited the recipe.
     *
     * @return BelongsToMany<User, $this>
     */
    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    /**
     * Get the comments for the recipe.
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
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
            'user_id' => 'integer',
            'family_id' => 'integer',
            'title' => 'string',
            'description' => 'string',
            'visibility' => RecipeVisibility::class,
            'image_path' => 'string',
            'prep_time' => 'integer',
            'cook_time' => 'integer',
            'servings' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}

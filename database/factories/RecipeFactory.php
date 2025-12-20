<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RecipeVisibility;
use App\Models\Family;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
final class RecipeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $recipeTitles = [
            'Classic Spaghetti Carbonara',
            'Grandma\'s Chocolate Chip Cookies',
            'Homemade Beef Lasagna',
            'Thai Green Curry',
            'Perfect Roast Chicken',
            'Creamy Mushroom Risotto',
            'Apple Pie with Cinnamon',
            'Fresh Garden Salad',
            'Slow-Cooked BBQ Ribs',
            'Vegetarian Pad Thai',
        ];

        return [
            'user_id' => User::factory(),
            'family_id' => fake()->boolean(60) ? Family::factory() : null,
            'title' => fake()->randomElement($recipeTitles),
            'description' => fake()->paragraph(3),
            'visibility' => fake()->randomElement(RecipeVisibility::cases()),
            'image_path' => fake()->boolean(70) ? 'recipes/' . fake()->uuid() . '.jpg' : null,
            'prep_time' => fake()->numberBetween(5, 60),
            'cook_time' => fake()->numberBetween(10, 180),
            'servings' => fake()->numberBetween(1, 12),
        ];
    }
}

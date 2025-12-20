<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ingredients;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredients>
 */
final class IngredientsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ingredients = [
            ['name' => 'All-purpose flour', 'unit' => 'cups'],
            ['name' => 'Sugar', 'unit' => 'cups'],
            ['name' => 'Salt', 'unit' => 'teaspoons'],
            ['name' => 'Butter', 'unit' => 'tablespoons'],
            ['name' => 'Eggs', 'unit' => 'pieces'],
            ['name' => 'Milk', 'unit' => 'cups'],
            ['name' => 'Olive oil', 'unit' => 'tablespoons'],
            ['name' => 'Garlic cloves', 'unit' => 'pieces'],
            ['name' => 'Onion', 'unit' => 'pieces'],
            ['name' => 'Tomatoes', 'unit' => 'pieces'],
            ['name' => 'Chicken breast', 'unit' => 'pounds'],
            ['name' => 'Ground beef', 'unit' => 'pounds'],
            ['name' => 'Pasta', 'unit' => 'ounces'],
            ['name' => 'Rice', 'unit' => 'cups'],
            ['name' => 'Black pepper', 'unit' => 'teaspoons'],
            ['name' => 'Paprika', 'unit' => 'teaspoons'],
            ['name' => 'Parmesan cheese', 'unit' => 'cups'],
            ['name' => 'Heavy cream', 'unit' => 'cups'],
        ];

        $ingredient = fake()->randomElement($ingredients);

        return [
            'recipe_id' => Recipe::factory(),
            'name' => $ingredient['name'],
            'quantity' => (string) fake()->randomFloat(2, 0.25, 10),
            'unit' => $ingredient['unit'],
        ];
    }
}

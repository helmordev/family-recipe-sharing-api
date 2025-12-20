<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\Step;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Step>
 */
final class StepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $instructions = [
            'Preheat the oven to 350°F (175°C) and prepare your baking pan.',
            'In a large bowl, mix together the dry ingredients until well combined.',
            'Add the wet ingredients to the dry mixture and stir until just combined.',
            'Pour the batter into the prepared pan and spread evenly.',
            'Heat oil in a large skillet over medium-high heat.',
            'Add the chopped onions and garlic, sauté until fragrant and translucent.',
            'Season with salt and pepper to taste, then add your protein of choice.',
            'Cook for 5-7 minutes, stirring occasionally until browned on all sides.',
            'Reduce heat to low and let simmer for the specified time.',
            'Remove from heat and let rest for 5 minutes before serving.',
            'Garnish with fresh herbs and serve immediately while hot.',
            'Combine all ingredients in a food processor and blend until smooth.',
            'Transfer mixture to a covered container and refrigerate for at least 2 hours.',
        ];

        return [
            'recipe_id' => Recipe::factory(),
            'step_number' => fake()->numberBetween(1, 10),
            'instruction' => fake()->randomElement($instructions),
        ];
    }
}

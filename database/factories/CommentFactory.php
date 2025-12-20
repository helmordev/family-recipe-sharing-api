<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
final class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $comments = [
            'This recipe is amazing! My family loved it.',
            'I made this last night and it turned out perfectly. Thank you for sharing!',
            'Great recipe! I added a bit more garlic and it was even better.',
            'This has become a staple in our household. So easy to make!',
            'Delicious! I\'ve made this three times already.',
            'The instructions were clear and easy to follow. Turned out great!',
            'I substituted some ingredients and it still came out wonderful.',
            'My kids actually ate their vegetables with this recipe!',
            'This recipe reminds me of my grandmother\'s cooking. Brings back memories!',
            'Perfect for meal prep! I made a big batch and froze portions.',
            'Could use a bit more seasoning, but overall a solid recipe.',
            'I halved the recipe for just two people and it worked perfectly.',
        ];

        return [
            'user_id' => User::factory(),
            'recipe_id' => Recipe::factory(),
            'content' => fake()->randomElement($comments),
        ];
    }
}

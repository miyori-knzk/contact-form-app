<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoryData = Category::inRandomOrder()->first();
        $digit = random_int(10, 11);

        return [
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'gender' => fake()->randomElement([1, 2, 3]),
            'email' => fake()->safeEmail(),
            'tel' => fake()->numerify(str_repeat('#', $digit)),
            'address' => fake()->address(),
            'building' => fake()->word(),
            'detail' => fake()->sentence(),
            'category_id' => $categoryData->id,
        ];
    }
}

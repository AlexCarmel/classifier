<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Technical Support',
            'Billing Issues',
            'Product Feedback', 
            'Bug Reports',
            'Feature Requests',
            'Account Management',
            'General Inquiry',
            'Complaints',
            'Sales Questions',
            'Security Concerns',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($categories),
        ];
    }
}

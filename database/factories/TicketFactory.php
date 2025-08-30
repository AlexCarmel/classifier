<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = [
            'Unable to login to my account',
            'Website loading very slowly',
            'Payment not processed correctly',
            'Feature request: Dark mode',
            'Bug in mobile app',
            'Invoice missing from my account',
            'How to cancel subscription?',
            'Security concern about data',
            'API documentation unclear',
            'Product pricing question',
            'Account locked after password reset',
            'Export functionality not working',
            'Integration with third-party tool',
            'Mobile app crashes on startup',
            'Refund request for unused service',
        ];

        $status = ['open', 'in_progress', 'resolved', 'closed'];

        return [
            'category_id' => Category::factory(),
            'subject' => $this->faker->randomElement($subjects),
            'body' => $this->faker->paragraphs(rand(2, 5), true),
            'status' => $this->faker->randomElement($status),
            'explanation' => $this->faker->optional(0.6)->sentence(rand(8, 15)),
            'confidence' => $this->faker->optional(0.7)->numberBetween(1, 100),
            'created_by' => $this->faker->boolean(80) ? (string) \Illuminate\Support\Str::ulid() : null,
            'updated_by' => $this->faker->boolean(50) ? (string) \Illuminate\Support\Str::ulid() : null,
        ];
    }

    /**
     * Indicate that the ticket has detailed explanation notes.
     */
    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'explanation' => $this->faker->paragraph(rand(3, 6)),
            'confidence' => $this->faker->numberBetween(70, 95),
        ]);
    }

    /**
     * Indicate that the ticket is a high priority item.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $this->faker->randomElement(['open', 'in_progress']),
            'confidence' => $this->faker->numberBetween(80, 100),
            'explanation' => 'Urgent ticket requiring immediate attention: ' . $this->faker->sentence(),
        ]);
    }
}

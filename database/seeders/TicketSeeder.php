<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 8 categories first
        $categories = Category::factory(8)->create();

        // Create 30 tickets with mixed configurations
        // 15 regular tickets
        Ticket::factory(15)
            ->recycle($categories)
            ->create();

        // 10 tickets with detailed notes/explanations
        Ticket::factory(10)
            ->withNotes()
            ->recycle($categories)
            ->create();

        // 5 urgent tickets
        Ticket::factory(5)
            ->urgent()
            ->recycle($categories)
            ->create();

        $this->command->info('Created ' . Category::count() . ' categories and ' . Ticket::count() . ' tickets');
    }
}

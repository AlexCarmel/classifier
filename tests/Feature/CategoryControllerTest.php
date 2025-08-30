<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->category = Category::factory()->create(['name' => 'Technical Support']);
    }

    public function test_index_returns_categories_with_ticket_count(): void
    {
        // Create tickets for the category
        Ticket::factory(3)->create(['category_id' => $this->category->id]);
        
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'tickets_count',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true
            ]);

        $categories = $response->json('data');
        $testCategory = collect($categories)->firstWhere('id', $this->category->id);
        $this->assertEquals(3, $testCategory['tickets_count']);
    }
}
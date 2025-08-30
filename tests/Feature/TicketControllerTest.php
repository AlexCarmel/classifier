<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Services\TicketClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use OpenAI\Laravel\Facades\OpenAI;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->category = Category::factory()->create(['name' => 'Technical Support']);
        $this->ticket = Ticket::factory()->create([
            'subject' => 'Test ticket',
            'body' => 'Test ticket body',
            'category_id' => $this->category->id,
            'status' => 'open'
        ]);
    }

    public function test_index_returns_tickets_list(): void
    {
        $response = $this->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'subject',
                        'body',
                        'status',
                        'category'
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]
            ])
            ->assertJson([
                'success' => true
            ]);
    }

    public function test_index_with_search_filters_tickets(): void
    {
        // Create another ticket with different subject
        Ticket::factory()->create([
            'subject' => 'Different subject',
            'body' => 'Different body',
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson('/api/tickets?search=Test');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Test', $data[0]['subject']);
    }

    public function test_index_with_status_filter(): void
    {
        // Create tickets with different statuses
        Ticket::factory()->create([
            'status' => 'closed',
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson('/api/tickets?status=open');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $ticket) {
            $this->assertEquals('open', $ticket['status']);
        }
    }

    public function test_store_creates_new_ticket(): void
    {
        $ticketData = [
            'category_id' => $this->category->id,
            'subject' => 'New ticket subject',
            'body' => 'New ticket body content',
            'status' => 'open',
            'explanation' => 'Initial explanation',
            'confidence' => 80
        ];

        $response = $this->postJson('/api/tickets', $ticketData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'subject',
                    'body',
                    'status',
                    'category'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Ticket created successfully'
            ]);

        $this->assertDatabaseHas('tickets', [
            'subject' => 'New ticket subject',
            'body' => 'New ticket body content'
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->postJson('/api/tickets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'body', 'status']);
    }

    public function test_store_validates_status_enum(): void
    {
        $ticketData = [
            'subject' => 'Test subject',
            'body' => 'Test body',
            'status' => 'invalid_status'
        ];

        $response = $this->postJson('/api/tickets', $ticketData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_show_returns_ticket_by_id(): void
    {
        $response = $this->getJson("/api/tickets/{$this->ticket->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'subject',
                    'body',
                    'status',
                    'category' => [
                        'id',
                        'name'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->ticket->id,
                    'subject' => $this->ticket->subject
                ]
            ]);
    }

    public function test_show_returns_404_for_nonexistent_ticket(): void
    {
        $response = $this->getJson('/api/tickets/nonexistent-id');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Ticket not found'
            ]);
    }

    public function test_update_modifies_ticket(): void
    {
        $updateData = [
            'subject' => 'Updated subject',
            'status' => 'in_progress'
        ];

        $response = $this->patchJson("/api/tickets/{$this->ticket->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ticket updated successfully',
                'data' => [
                    'id' => $this->ticket->id,
                    'subject' => 'Updated subject',
                    'status' => 'in_progress'
                ]
            ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'subject' => 'Updated subject',
            'status' => 'in_progress'
        ]);
    }

    public function test_update_validates_partial_data(): void
    {
        $updateData = [
            'status' => 'invalid_status'
        ];

        $response = $this->patchJson("/api/tickets/{$this->ticket->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_classify_status_returns_rate_limit_info(): void
    {
        $response = $this->getJson('/api/tickets/classify/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'rate_limit_status' => [
                        'calls_made',
                        'max_calls',
                        'remaining_calls',
                        'window_seconds'
                    ],
                    'openai_enabled'
                ]
            ])
            ->assertJson([
                'success' => true
            ]);
    }

    public function test_classify_with_openai_disabled(): void
    {
        Config::set('services.openai.classify_enabled', false);
        
        $response = $this->postJson("/api/tickets/{$this->ticket->id}/classify");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'ticket',
                    'classification' => [
                        'category',
                        'explanation',
                        'confidence'
                    ],
                    'rate_limit_status',
                    'openai_enabled'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Ticket classified successfully',
                'data' => [
                    'openai_enabled' => false
                ]
            ]);

        $classification = $response->json('data.classification');
        $this->assertStringContainsString('fallback system', $classification['explanation']);
    }

    public function test_classify_applies_results_to_ticket(): void
    {
        Config::set('services.openai.classify_enabled', false);
        
        $newCategory = Category::factory()->create(['name' => 'Bug Reports']);
        
        $response = $this->postJson("/api/tickets/{$this->ticket->id}/classify");

        $response->assertStatus(200);
        
        // Refresh ticket from database
        $this->ticket->refresh();
        
        // Check that ticket was updated with classification results
        $this->assertNotNull($this->ticket->explanation);
        $this->assertNotNull($this->ticket->confidence);
    }

    public function test_classify_endpoint_response_structure(): void
    {
        Config::set('services.openai.classify_enabled', false); // Use fallback for reliable testing
        
        $response = $this->postJson("/api/tickets/{$this->ticket->id}/classify");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'ticket',
                    'classification' => [
                        'category',
                        'explanation',
                        'confidence'
                    ],
                    'rate_limit_status' => [
                        'calls_made',
                        'max_calls',
                        'remaining_calls',
                        'window_seconds'
                    ],
                    'openai_enabled'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Ticket classified successfully'
            ]);

        $classification = $response->json('data.classification');
        $this->assertIsString($classification['category']);
        $this->assertIsString($classification['explanation']);
        $this->assertIsInt($classification['confidence']);
        $this->assertGreaterThanOrEqual(1, $classification['confidence']);
        $this->assertLessThanOrEqual(100, $classification['confidence']);
    }

    public function test_classify_handles_rate_limit_exceeded(): void
    {
        Config::set('services.openai.classify_enabled', true);
        
        // Hit the rate limit by making the maximum number of attempts
        $rateLimitKey = 'openai_classify_rate_limit';
        
        // Use Laravel's RateLimiter to simulate hitting the limit
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit($rateLimitKey, 60); // Hit the rate limit 10 times
        }

        $response = $this->postJson("/api/tickets/{$this->ticket->id}/classify");

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to classify ticket'
            ]);
    }

    public function test_classify_returns_404_for_nonexistent_ticket(): void
    {
        $response = $this->postJson('/api/tickets/nonexistent-id/classify');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Ticket not found'
            ]);
    }

    public function test_pagination_works_correctly(): void
    {
        // Create multiple tickets
        Ticket::factory(25)->create(['category_id' => $this->category->id]);

        $response = $this->getJson('/api/tickets?per_page=10');

        $response->assertStatus(200);
        
        $pagination = $response->json('pagination');
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertGreaterThan(1, $pagination['last_page']);
        $this->assertGreaterThan(10, $pagination['total']);
    }

    public function test_confidence_range_filter(): void
    {
        // Create tickets with different confidence levels
        Ticket::factory()->create([
            'category_id' => $this->category->id,
            'confidence' => 30
        ]);
        Ticket::factory()->create([
            'category_id' => $this->category->id,
            'confidence' => 80
        ]);

        $response = $this->getJson('/api/tickets?min_confidence=50');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $ticket) {
            if ($ticket['confidence'] !== null) {
                $this->assertGreaterThanOrEqual(50, $ticket['confidence']);
            }
        }
    }

    public function test_category_filter(): void
    {
        $anotherCategory = Category::factory()->create(['name' => 'Billing Issues']);
        Ticket::factory()->create(['category_id' => $anotherCategory->id]);

        $response = $this->getJson("/api/tickets?category_id={$this->category->id}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $ticket) {
            if ($ticket['category']) {
                $this->assertEquals($this->category->id, $ticket['category']['id']);
            }
        }
    }

    public function test_classification_respects_manual_category_changes(): void
    {
        Config::set('services.openai.classify_enabled', false);
        
        // Create categories
        $techSupportCategory = Category::factory()->create(['name' => 'Technical Support']);
        $bugReportsCategory = Category::factory()->create(['name' => 'Bug Reports']);
        
        // Create ticket with no category
        $ticket = Ticket::factory()->create([
            'category_id' => null,
            'explanation' => null,
            'confidence' => null
        ]);
        
        // First classification - should update everything
        $response = $this->postJson("/api/tickets/{$ticket->id}/classify");
        $response->assertStatus(200);
        
        $ticket->refresh();
        $this->assertNotNull($ticket->category_id, 'Category should be set after first classification');
        $this->assertNotNull($ticket->explanation, 'Explanation should be set');
        $this->assertNotNull($ticket->confidence, 'Confidence should be set');
        
        $originalCategoryId = $ticket->category_id;
        
        // User manually changes category to a different one
        $ticket->update(['category_id' => $bugReportsCategory->id]);
        $ticket->refresh();
        
        // Run classification again - should keep manual category but update explanation & confidence
        $response = $this->postJson("/api/tickets/{$ticket->id}/classify");
        $response->assertStatus(200);
        
        $ticket->refresh();
        $this->assertEquals($bugReportsCategory->id, $ticket->category_id, 'Manual category should be preserved');
        $this->assertNotNull($ticket->explanation, 'Explanation should be updated');
        $this->assertNotNull($ticket->confidence, 'Confidence should be updated');
    }

    public function test_classification_updates_category_if_not_manually_changed(): void
    {
        Config::set('services.openai.classify_enabled', false);
        
        // Create categories
        $techSupportCategory = Category::factory()->create(['name' => 'Technical Support']);
        
        // Create ticket with no category
        $ticket = Ticket::factory()->create([
            'category_id' => null,
            'explanation' => null,
            'confidence' => null
        ]);
        
        // First classification
        $response = $this->postJson("/api/tickets/{$ticket->id}/classify");
        $response->assertStatus(200);
        
        $ticket->refresh();
        $originalCategoryId = $ticket->category_id;
        $this->assertNotNull($originalCategoryId, 'Category should be set after first classification');
        $this->assertNotNull($ticket->explanation, 'Explanation should be set');
        $this->assertNotNull($ticket->confidence, 'Confidence should be set');
        
        // Clear the explanation to simulate a scenario where we want to test re-classification
        $ticket->update(['explanation' => 'Previous classification', 'confidence' => 50]);
        $ticket->refresh();
        
        // Run classification again without manual changes - should update everything including category
        $response = $this->postJson("/api/tickets/{$ticket->id}/classify");
        $response->assertStatus(200);
        
        $ticket->refresh();
        $this->assertNotNull($ticket->category_id, 'Category should still be set');
        $this->assertStringContainsString('fallback system', $ticket->explanation, 'Explanation should be updated with new classification');
        $this->assertNotEquals(50, $ticket->confidence, 'Confidence should be updated from the previous value');
    }

    public function test_classification_handles_ticket_with_category_but_no_explanation(): void
    {
        Config::set('services.openai.classify_enabled', false);
        
        // Create category
        $techSupportCategory = Category::factory()->create(['name' => 'Technical Support']);
        
        // Create ticket with category but no previous classification
        $ticket = Ticket::factory()->create([
            'category_id' => $techSupportCategory->id,
            'explanation' => null,
            'confidence' => null
        ]);
        
        // Classification should update category (since no previous explanation indicates no previous classification)
        $response = $this->postJson("/api/tickets/{$ticket->id}/classify");
        $response->assertStatus(200);
        
        $ticket->refresh();
        $this->assertNotNull($ticket->category_id, 'Category should be updated');
        $this->assertNotNull($ticket->explanation, 'Explanation should be set');
        $this->assertNotNull($ticket->confidence, 'Confidence should be set');
    }

    protected function tearDown(): void
    {
        Cache::flush();
        RateLimiter::clear('openai_classify_rate_limit'); // Clear rate limiter for tests
        parent::tearDown();
    }
}

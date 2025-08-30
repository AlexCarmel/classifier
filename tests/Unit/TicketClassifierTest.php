<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Ticket;
use App\Services\TicketClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use OpenAI\Laravel\Facades\OpenAI;
use Tests\TestCase;

class TicketClassifierTest extends TestCase
{
    use RefreshDatabase;

    protected TicketClassifier $classifier;
    protected Category $category;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->classifier = new TicketClassifier();
        
        // Create test data
        $this->category = Category::factory()->create(['name' => 'Technical Support']);
        $this->ticket = Ticket::factory()->create([
            'subject' => 'Login issue',
            'body' => 'I cannot login to my account',
            'category_id' => $this->category->id
        ]);
    }

    public function test_classify_returns_dummy_data_when_openai_disabled(): void
    {
        Config::set('services.openai.classify_enabled', false);
        
        $result = $this->classifier->classify($this->ticket);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('explanation', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertStringContainsString('fallback system', $result['explanation']);
        $this->assertIsInt($result['confidence']);
        $this->assertGreaterThanOrEqual(10, $result['confidence']);
        $this->assertLessThanOrEqual(95, $result['confidence']);
    }

    public function test_classify_with_openai_enabled_falls_back_when_no_api_key(): void
    {
        Config::set('services.openai.classify_enabled', true);
        Config::set('openai.api_key', null); // No API key set
        
        $result = $this->classifier->classify($this->ticket);

        // Should fall back to dummy data when API fails
        $this->assertIsArray($result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('explanation', $result);
        $this->assertArrayHasKey('confidence', $result);
    }

    public function test_rate_limiting_prevents_excessive_calls(): void
    {
        Config::set('services.openai.classify_enabled', true);
        
        // Hit the rate limit by making the maximum number of attempts
        $rateLimitKey = 'openai_classify_rate_limit';
        
        // Use Laravel's RateLimiter to simulate hitting the limit
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit($rateLimitKey, 60); // Hit the rate limit 10 times
        }

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $this->classifier->classify($this->ticket);
    }





    public function test_validation_logic_with_direct_method_calls(): void
    {
        // Test the validation logic directly using reflection
        $reflection = new \ReflectionClass($this->classifier);
        $method = $reflection->getMethod('isValidClassification');
        $method->setAccessible(true);

        // Test valid classification
        $validClassification = [
            'category' => 'Technical Support',
            'explanation' => 'Valid explanation',
            'confidence' => 85
        ];
        $this->assertTrue($method->invoke($this->classifier, $validClassification));

        // Test invalid classification - missing keys
        $invalidClassification = [
            'category' => 'Technical Support'
            // Missing explanation and confidence
        ];
        $this->assertFalse($method->invoke($this->classifier, $invalidClassification));

        // Test invalid confidence range
        $invalidConfidence = [
            'category' => 'Technical Support',
            'explanation' => 'Valid explanation',
            'confidence' => 150 // > 100
        ];
        $this->assertFalse($method->invoke($this->classifier, $invalidConfidence));

        // Test non-existent category
        $invalidCategory = [
            'category' => 'Non-existent Category',
            'explanation' => 'Valid explanation',
            'confidence' => 85
        ];
        $this->assertFalse($method->invoke($this->classifier, $invalidCategory));
    }

    public function test_dummy_classification_uses_available_categories(): void
    {
        Config::set('services.openai.classify_enabled', false);
        
        // Create additional categories
        Category::factory()->create(['name' => 'Billing Issues']);
        Category::factory()->create(['name' => 'Feature Requests']);

        $result = $this->classifier->classify($this->ticket);

        $allCategories = Category::pluck('name')->toArray();
        $this->assertContains($result['category'], $allCategories);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        RateLimiter::clear('openai_classify_rate_limit'); // Clear rate limiter for tests
        parent::tearDown();
    }
}

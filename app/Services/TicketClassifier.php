<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Service for classifying tickets using AI algorithms.
 *
 * This service handles ticket classification using OpenAI's GPT models with
 * intelligent fallback to dummy classification when OpenAI is unavailable.
 * Includes rate limiting, error handling, and logging.
 *
 * Features:
 * - OpenAI GPT-based classification with custom prompts
 * - Fallback classification system when AI is disabled/unavailable
 * - Rate limiting using Laravel's RateLimiter facade
 * - Validation of classification responses
 * - Detailed logging for monitoring and debugging
 *
 * @package App\Services
 */
class TicketClassifier
{
    private array $rateLimitConfig;
    private array $openaiConfig;

    /**
     * Initialize the TicketClassifier service.
     *
     * Loads configuration from the services.openai config section and sets up
     * rate limiting parameters. Configuration includes OpenAI API settings,
     * model parameters, and rate limiting constraints.
     */
    public function __construct()
    {
        $this->openaiConfig = Config::get('services.openai', []);
        $this->rateLimitConfig = $this->openaiConfig['rate_limit'] ?? [
            'max_calls' => 10,
            'window_seconds' => 60,
            'cache_key' => 'openai_classify_rate_limit',
        ];
    }

    /**
     * Classify a ticket using OpenAI or fallback to dummy classification.
     *
     * This is the main classification method that determines the category,
     * explanation, and confidence score for a given ticket. It first checks
     * if OpenAI classification is enabled, then applies rate limiting before
     * attempting AI classification. Falls back to dummy classification when
     * OpenAI is disabled, rate limited, or encounters errors.
     *
     * @param Ticket $ticket The ticket instance to classify
     * @return array Classification result containing:
     *               - category: String name of the suggested category
     *               - explanation: Human-readable explanation of the classification
     *               - confidence: Integer score from 1-100 indicating classification confidence
     * @throws \Exception When rate limit is exceeded or classification fails critically
     */
    public function classify(Ticket $ticket): array
    {
        // Check if OpenAI classification is enabled
        if (!$this->openaiConfig['classify_enabled']) {
            return $this->getDummyClassification('OpenAI classification disabled');
        }

        // Use Laravel's RateLimiter to check and increment rate limit
        $rateLimitKey = $this->rateLimitConfig['cache_key'];
        $maxAttempts = (int) $this->rateLimitConfig['max_calls'];
        $decaySeconds = (int) $this->rateLimitConfig['window_seconds'];

        $executed = RateLimiter::attempt(
            $rateLimitKey,
            $maxAttempts,
            function () use ($ticket) {
                return $this->executeClassification($ticket);
            },
            $decaySeconds
        );

        if ($executed === false) {
            throw new \Exception('Rate limit exceeded. Please try again later.');
        }

        return $executed;
    }

    /**
     * Execute the actual OpenAI classification.
     *
     * This method handles the core classification logic by calling the OpenAI API,
     * validating the response, and falling back to dummy classification on errors.
     * It includes comprehensive logging for monitoring and debugging purposes.
     *
     * @param Ticket $ticket The ticket instance to classify
     * @return array Valid classification array with category, explanation, and confidence
     * @throws \Exception Propagates OpenAI API exceptions when fallback fails
     */
    private function executeClassification(Ticket $ticket): array
    {
        try {
            // Call OpenAI API
            $classification = $this->classifyWithOpenAI($ticket);

            // Validate the response
            if (!$this->isValidClassification($classification)) {
                Log::warning('Invalid OpenAI classification response', [
                    'ticket_id' => $ticket->id,
                    'response' => $classification
                ]);
                return $this->getDummyClassification('Invalid AI response');
            }

            Log::info('Ticket classified successfully with OpenAI', [
                'ticket_id' => $ticket->id,
                'category' => $classification['category'],
                'confidence' => $classification['confidence']
            ]);

            return $classification;
        } catch (\Exception $e) {
            Log::error('OpenAI classification failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);

            // Fallback to dummy classification on error
            return $this->getDummyClassification('OpenAI API error');
        }
    }

    /**
     * Classify ticket using OpenAI's GPT API.
     *
     * Makes a direct API call to OpenAI using the configured model and parameters.
     * Uses a system prompt to instruct the AI to return structured JSON with
     * category, explanation, and confidence fields. Logs the request for debugging.
     *
     * @param Ticket $ticket The ticket instance to classify
     * @return array Raw classification data from OpenAI containing category, explanation, confidence
     * @throws \Exception When OpenAI API call fails or returns invalid JSON
     */
    private function classifyWithOpenAI(Ticket $ticket): array
    {
        $systemPrompt = $this->getSystemPrompt();
        $userMessage = $this->formatTicketForClassification($ticket);
        Log::info('OpenAI request', [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userMessage
            ]
        ]);
        $response = OpenAI::chat()->create([
            'model' => $this->openaiConfig['model'] ?? 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ],
            'max_tokens' => $this->openaiConfig['max_tokens'] ?? 200,
            'temperature' => $this->openaiConfig['temperature'] ?? 0.3,
            'response_format' => ['type' => 'json_object'],
        ]);
        
        $content = $response->choices[0]->message->content;
        
        $classification = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from OpenAI: ' . json_last_error_msg());
        }

        return $classification;
    }

    /**
     * Get the system prompt for OpenAI classification.
     *
     * Generates a system prompt that instructs the AI to classify
     * tickets into available categories. The prompt includes the list of valid
     * categories, response format requirements, and an example response.
     *
     * @return string Complete system prompt for OpenAI classification
     */
    private function getSystemPrompt(): string
    {
        $categories = Category::pluck('name')->toArray();
        $categoriesList = implode(', ', $categories);

        return "You are a support ticket classification system. Analyze the provided ticket and classify it into one of the available categories.

Available categories: {$categoriesList}

You must respond ONLY with a valid JSON object containing exactly these keys:
- category: string (must be one of the available categories)
- explanation: string (brief explanation of why this category was chosen, max 100 characters)
- confidence: integer (confidence score from 1-100)

Example response:
{\"category\":\"Technical Support\",\"explanation\":\"User experiencing login issues with the application\",\"confidence\":85}

Do not include any other text outside the JSON object.";
    }

    /**
     * Format ticket content for OpenAI classification request.
     *
     * Creates a structured representation of the ticket data that the AI
     * can easily understand and analyze. Includes subject, body, and status.
     *
     * @param Ticket $ticket The ticket to format for classification
     * @return string Formatted ticket content for AI analysis
     */
    private function formatTicketForClassification(Ticket $ticket): string
    {
        return "Ticket Subject: {$ticket->subject}\n\nTicket Body: {$ticket->body}\n\nCurrent Status: {$ticket->status}";
    }

    /**
     * Generate fallback classification when OpenAI is disabled or fails.
     *
     * Creates a classification result using available categories from the database
     * or fallback categories if none exist. Generates a random confidence score
     * and includes the reason for using fallback classification.
     *
     * @param string $reason Reason for using fallback classification (for logging/debugging)
     * @return array Fallback classification with random category, generic explanation, random confidence
     */
    private function getDummyClassification(string $reason = 'OpenAI unavailable'): array
    {
        $categories = Category::pluck('name')->toArray();
        
        if (empty($categories)) {
            $categories = ['General Inquiry', 'Technical Support', 'Bug Reports'];
        }

        $randomCategory = $categories[array_rand($categories)];
        $confidence = rand(10, 95);

        return [
            'category' => $randomCategory,
            'explanation' => "Automatically classified using fallback system ({$reason})",
            'confidence' => $confidence
        ];
    }


    /**
     * Validate OpenAI classification response structure and content.
     *
     * Ensures the classification response contains all required fields with
     * appropriate types and values. Validates that category exists in the database,
     * confidence is within valid range, and all required keys are present.
     *
     * @param array $classification Classification array to validate
     * @return bool True if classification is valid and complete, false otherwise
     */
    private function isValidClassification(array $classification): bool
    {
        // Check required keys
        if (!isset($classification['category'], $classification['explanation'], $classification['confidence'])) {
            return false;
        }

        // Validate category exists
        $categories = Category::pluck('name')->toArray();
        if (!in_array($classification['category'], $categories)) {
            return false;
        }

        // Validate confidence is integer between 1-100
        if (!is_int($classification['confidence']) || 
            $classification['confidence'] < 1 || 
            $classification['confidence'] > 100) {
            return false;
        }

        // Validate explanation is string and not empty
        if (!is_string($classification['explanation']) || empty(trim($classification['explanation']))) {
            return false;
        }

        return true;
    }
}

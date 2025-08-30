<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Category;
use App\Services\TicketClassifier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets with filters, search, and pagination.
     *
     * Supports filtering by status, category, confidence range, and text search.
     * Results are paginated based on the 'per_page' query parameter.
     *
     * @param Request $request The HTTP request containing optional filter parameters:
     *                        - search: Text search in subject, body, or explanation
     *                        - status: Filter by ticket status
     *                        - category_id: Filter by category ID
     *                        - min_confidence: Minimum confidence score
     *                        - max_confidence: Maximum confidence score
     *                        - per_page: Number of results per page (default: 10)
     * @return JsonResponse JSON response containing paginated tickets with category relationships
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::with(['category']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%")
                  ->orWhere('explanation', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        // Filter by confidence range
        if ($request->has('min_confidence')) {
            $query->where('confidence', '>=', $request->get('min_confidence'));
        }
        if ($request->has('max_confidence')) {
            $query->where('confidence', '<=', $request->get('max_confidence'));
        }

        // Filter by created_by
        if ($request->has('created_by')) {
            $query->where('created_by', $request->get('created_by'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100); // Limit to 100 max
        $tickets = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tickets->items(),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'from' => $tickets->firstItem(),
                'to' => $tickets->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created ticket in storage.
     *
     * Creates a new ticket with the provided data. All fields are validated
     * according to the ticket model rules. The category relationship is
     * loaded and returned in the response.
     *
     * @param Request $request The HTTP request containing ticket data:
     *                        - category_id: Optional category ULID
     *                        - subject: Required ticket subject (max 255 chars)
     *                        - body: Required ticket body content
     *                        - status: Required status (open|in_progress|resolved|closed)
     *                        - explanation: Optional AI explanation (max 255 chars)
     *                        - confidence: Optional AI confidence score (1-100)
     *                        - created_by: Optional ULID of creator
     *                        - updated_by: Optional ULID of updater
     * @return JsonResponse JSON response with created ticket data or validation errors
     * @throws \Illuminate\Validation\ValidationException When validation fails
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'string', 'exists:categories,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'explanation' => ['nullable', 'string', 'max:255'],
            'confidence' => ['nullable', 'integer', 'min:1', 'max:100'],
            'created_by' => ['nullable', 'string'],
            'updated_by' => ['nullable', 'string'],
        ]);

        try {
            $ticket = Ticket::create($validated);
            $ticket->load(['category']);

            return response()->json([
                'success' => true,
                'message' => 'Ticket created successfully',
                'data' => $ticket
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified ticket.
     *
     * Retrieves a single ticket by its ULID with the category relationship loaded.
     * Returns 404 error if the ticket is not found.
     *
     * @param string $id The ULID of the ticket to retrieve
     * @return JsonResponse JSON response containing ticket data or 404 error
     * @throws ModelNotFoundException When ticket with given ID is not found
     */
    public function show(string $id): JsonResponse
    {
        try {
            $ticket = Ticket::with(['category'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $ticket
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }
    }

    /**
     * Update the specified ticket in storage.
     *
     * Updates an existing ticket with validated data from the middleware.
     * The ValidateTicketUpdate middleware handles all validation rules
     * before the data reaches this method.
     *
     * @param Request $request The HTTP request (validation handled by middleware)
     * @param string $id The ULID of the ticket to update
     * @return JsonResponse JSON response with updated ticket data or error
     * @throws ModelNotFoundException When ticket with given ID is not found
     * @throws \Exception When update operation fails
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // Get validated data from middleware
        $validated = $request->get('validated', []);

        try {
            $ticket = Ticket::findOrFail($id);

            $ticket->update($validated);
            $ticket->load(['category']);

            return response()->json([
                'success' => true,
                'message' => 'Ticket updated successfully',
                'data' => $ticket
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Classify a ticket using AI/ML algorithms.
     *
     * Performs intelligent classification on the specified ticket using OpenAI
     * or fallback classification algorithms. Updates the ticket with classification
     * results while respecting manual category changes made by users.
     *
     * The method checks rate limits, performs classification, and intelligently
     * applies results based on whether the user has manually modified the category.
     *
     * @param Request $request The HTTP request (no additional parameters expected)
     * @param string $id The ULID of the ticket to classify
     * @param TicketClassifier $classifier Injected service for performing classification
     * @return JsonResponse JSON response containing:
     *                     - ticket: Updated ticket with classification results
     *                     - classification: Raw classification data (category, explanation, confidence)
     *                     - rate_limit_status: Current API rate limiting status
     *                     - openai_enabled: Whether OpenAI classification is enabled
     * @throws ModelNotFoundException When ticket with given ID is not found
     * @throws \Exception When classification fails or rate limit exceeded
     */
    public function classify(Request $request, string $id, TicketClassifier $classifier): JsonResponse
    {
        try {
            $ticket = Ticket::findOrFail($id);

            // Get rate limit status
            $rateLimitStatus = $this->getRateLimitStatus();

            // Perform classification
            $classification = $classifier->classify($ticket);

            // Apply classification results to ticket
            $ticket = $this->applyClassification($ticket, $classification);

            return response()->json([
                'success' => true,
                'message' => 'Ticket classified successfully',
                'data' => [
                    'ticket' => $ticket,
                    'classification' => $classification,
                    'rate_limit_status' => $rateLimitStatus,
                    'openai_enabled' => config('services.openai.classify_enabled', false)
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to classify ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get classification rate limit status and configuration.
     *
     * Returns the current rate limiting status for classification API calls
     * and whether OpenAI classification is enabled. This endpoint is useful
     * for frontend applications to display rate limit information to users.
     *
     * @return JsonResponse JSON response containing:
     *                     - rate_limit_status: Object with calls_made, max_calls, remaining_calls, etc.
     *                     - openai_enabled: Boolean indicating if OpenAI classification is enabled
     */
    public function classifyStatus(): JsonResponse
    {
        $rateLimitStatus = $this->getRateLimitStatus();

        return response()->json([
            'success' => true,
            'data' => [
                'rate_limit_status' => $rateLimitStatus,
                'openai_enabled' => config('services.openai.classify_enabled', false)
            ]
        ]);
    }

    /**
     * Apply classification results to a ticket with intelligent category handling.
     *
     * Logic:
     * - Always update: explanation and confidence
     * - Conditionally update category based on shouldUpdateTicketCategory()
     * - Log classification decisions for audit purposes
     *
     * @param Ticket $ticket The ticket instance to update
     * @param array $classification Classification results containing:
     *                              - category: Suggested category name
     *                              - explanation: AI-generated explanation
     *                              - confidence: Confidence score (1-100)
     * @return Ticket The updated ticket instance with category relationship loaded
     * @see shouldUpdateTicketCategory() For category update decision logic
     */
    private function applyClassification(Ticket $ticket, array $classification): Ticket
    {
        // Find the category suggested by classification
        $suggestedCategory = Category::where('name', $classification['category'])->first();
        
        // Always update explanation and confidence
        $updateData = [
            'explanation' => $classification['explanation'],
            'confidence' => $classification['confidence']
        ];

        // Determine if we should update the category
        $shouldUpdateCategory = $this->shouldUpdateTicketCategory($ticket, $suggestedCategory);
        
        if ($suggestedCategory && $shouldUpdateCategory) {
            $updateData['category_id'] = $suggestedCategory->id;
            Log::info('Applied full classification to ticket', [
                'ticket_id' => $ticket->id,
                'category' => $classification['category'],
                'confidence' => $classification['confidence'],
                'category_updated' => true
            ]);
        } else {
            $reason = !$suggestedCategory ? 'category not found' : 'user has manually set category';
            Log::info('Applied partial classification to ticket', [
                'ticket_id' => $ticket->id,
                'suggested_category' => $classification['category'],
                'confidence' => $classification['confidence'],
                'category_updated' => false,
                'reason' => $reason
            ]);
        }

        $ticket->update($updateData);
        $ticket->load(['category']);

        return $ticket;
    }

    /**
     * Determine if the ticket category should be updated based on classification.
     *
     * This method implements intelligent logic to detect whether a user has manually
     * changed a ticket's category. It preserves user autonomy by only updating
     * categories when it's safe to assume the user hasn't made deliberate changes.
     *
     * Decision logic:
     * 1. No suggested category found → Don't update (return false)
     * 2. Ticket has no category set → Always update (return true)
     * 3. Ticket never classified before (no explanation) → Update category (return true)
     * 4. Ticket previously classified → Only update if current category matches suggested
     *    category (indicating user hasn't manually changed it)
     *
     * @param Ticket $ticket The ticket to evaluate for category updates
     * @param Category|null $suggestedCategory The category suggested by classification algorithm
     * @return bool True if category should be updated, false if manual category should be preserved
     */
    private function shouldUpdateTicketCategory(Ticket $ticket, ?Category $suggestedCategory): bool
    {
        // If no suggested category found, don't update
        if (!$suggestedCategory) {
            return false;
        }
        
        // If ticket has no category set, always update
        if (!$ticket->category_id) {
            return true;
        }
        
        // If ticket has never been classified before (no explanation), update category
        if (!$ticket->explanation) {
            return true;
        }
        
        // If ticket was classified before, only update category if it matches the suggested one
        // This indicates user hasn't manually changed it from the previous classification
        return $ticket->category_id === $suggestedCategory->id;
    }

    /**
     * Get current rate limit status using Laravel RateLimiter.
     *
     * Retrieves rate limiting information for OpenAI API calls
     * by interfacing with Laravel's built-in RateLimiter facade. Configuration
     * is loaded from the services.openai.rate_limit config section.
     *
     * @return array Associative array containing:
     *               - calls_made: Number of API calls made in current window
     *               - max_calls: Maximum allowed calls per window
     *               - remaining_calls: Number of calls remaining in current window
     *               - window_seconds: Duration of the rate limiting window
     *               - retries_left: Number of retries available
     *               - available_in: Seconds until rate limit resets
     */
    private function getRateLimitStatus(): array
    {
        $rateLimitConfig = config('services.openai.rate_limit', [
            'max_calls' => 10,
            'window_seconds' => 60,
            'cache_key' => 'openai_classify_rate_limit',
        ]);
        
        $rateLimitKey = $rateLimitConfig['cache_key'];
        $maxAttempts = (int) $rateLimitConfig['max_calls'];
        
        $remaining = RateLimiter::remaining($rateLimitKey, $maxAttempts);
        $retriesLeft = RateLimiter::retriesLeft($rateLimitKey, $maxAttempts);
        $availableIn = RateLimiter::availableIn($rateLimitKey);
        
        return [
            'calls_made' => $maxAttempts - $remaining,
            'max_calls' => $maxAttempts,
            'remaining_calls' => $remaining,
            'window_seconds' => (int) $rateLimitConfig['window_seconds'],
            'retries_left' => $retriesLeft,
            'available_in' => $availableIn, // seconds until rate limit resets
        ];
    }
}

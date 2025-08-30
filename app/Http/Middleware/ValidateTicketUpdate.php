<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ValidateTicketUpdate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only validate if this is an update request (PATCH/PUT)
        if (in_array($request->method(), ['PATCH', 'PUT'])) {
            $validated = $request->validate([
                'category_id' => ['sometimes', 'nullable', 'string', 'exists:categories,id'],
                'subject' => ['sometimes', 'required', 'string', 'max:255'],
                'body' => ['sometimes', 'required', 'string'],
                'status' => ['sometimes', 'required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
                'explanation' => ['sometimes', 'nullable', 'string', 'max:255'],
                'confidence' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
                'created_by' => ['sometimes', 'nullable', 'string'],
                'updated_by' => ['sometimes', 'nullable', 'string'],
            ]);
            
            // Add validated data to request for controller access
            $request->merge(['validated' => $validated]);
        }

        return $next($request);
    }
}

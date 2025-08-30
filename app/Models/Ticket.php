<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ticket model representing support tickets in the system.
 *
 * This model handles ticket data including subject, body, status, and classification
 * information. Tickets can be categorized, classified by AI, and tracked with
 * confidence scores and explanations.
 *
 * @property string $id ULID primary key
 * @property string|null $category_id Foreign key to categories table
 * @property string $subject Ticket subject/title
 * @property string $body Ticket content/description
 * @property string $status Ticket status (open|in_progress|resolved|closed)
 * @property string|null $explanation AI-generated classification explanation
 * @property int|null $confidence AI classification confidence score (1-100)
 * @property string|null $created_by ULID of user who created the ticket
 * @property string|null $updated_by ULID of user who last updated the ticket
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Category|null $category
 *
 * @package App\Models
 */
class Ticket extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'category_id',
        'subject',
        'body',
        'status',
        'explanation',
        'confidence',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'confidence' => 'integer',
    ];

    /**
     * Get the category that this ticket belongs to.
     *
     * @return BelongsTo<Category, Ticket>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

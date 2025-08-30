<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Category model representing ticket categories in the system.
 *
 * This model handles categorization of support tickets. Categories are used
 * by the AI classification system and for organizing tickets in the dashboard.
 *
 * @property string $id ULID primary key
 * @property string $name Category name/label
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 *
 * @package App\Models
 */
class Category extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
    ];

    /**
     * Get all tickets belonging to this category.
     *
     * @return HasMany<Ticket>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}

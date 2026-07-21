<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'source',
        'status',
        'expected_value',
        'assigned_to',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_value' => 'decimal:2',
            'status' => LeadStatus::class,
            'source' => LeadSource::class,
        ];
    }

    /**
     * Get the user assigned to this lead.
     */
    public function assignedRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the activities logged for this lead.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}

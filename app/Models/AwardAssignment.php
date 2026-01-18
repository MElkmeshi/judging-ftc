<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AwardAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\AwardAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'award_id',
        'team_id',
        'level',
        'calculated_score',
        'rank',
        'is_finalized',
        'is_override',
        'assigned_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'calculated_score' => 'decimal:4',
            'rank' => 'integer',
            'is_finalized' => 'boolean',
            'is_override' => 'boolean',
        ];
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Award extends Model
{
    /** @use HasFactory<\Database\Factories\AwardFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'award_template_id',
        'name',
        'code',
        'description',
        'is_ranked',
        'is_hierarchical',
        'is_locked',
        'is_finalized',
    ];

    protected function casts(): array
    {
        return [
            'is_ranked' => 'boolean',
            'is_hierarchical' => 'boolean',
            'is_locked' => 'boolean',
            'is_finalized' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function awardTemplate(): BelongsTo
    {
        return $this->belongsTo(AwardTemplate::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(Criterion::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function judges(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'award_judge', 'award_id', 'user_id')
            ->withTimestamps();
    }

    public function awardAssignments(): HasMany
    {
        return $this->hasMany(AwardAssignment::class);
    }

    public function canBeScored(): bool
    {
        return ! $this->is_locked && $this->event->status === 'judging';
    }

    public function getAvailableLevels(): array
    {
        // Non-ranked awards (like Judge's Choice) only have a single winner
        if (! $this->is_ranked) {
            return ['Winner'];
        }

        // For ranked awards, apply FTC team count rules
        $teamCount = $this->event->activeTeamsCount();

        if ($teamCount < 11) {
            return ['1st'];
        } elseif ($teamCount <= 20) {
            return ['1st', '2nd'];
        }

        return ['1st', '2nd', '3rd'];
    }
}

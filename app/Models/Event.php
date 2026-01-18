<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'event_date',
        'location',
        'status',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function activeTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('is_active', true);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(Award::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')
            ->withPivot(['can_score', 'can_deliberate'])
            ->withTimestamps();
    }

    public function judges(): BelongsToMany
    {
        return $this->users()->wherePivot('can_score', true);
    }

    public function administrators(): BelongsToMany
    {
        return $this->users()->wherePivot('can_deliberate', true);
    }

    public function activeTeamsCount(): int
    {
        return $this->activeTeams()->count();
    }

    public function isInJudgingPhase(): bool
    {
        return $this->status === 'judging';
    }

    public function canEditScores(): bool
    {
        return in_array($this->status, ['judging', 'deliberation']);
    }
}

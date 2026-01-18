<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'team_number',
        'team_name',
        'school_organization',
        'city',
        'state_province',
        'country',
        'is_rookie',
    ];

    protected function casts(): array
    {
        return [
            'team_number' => 'integer',
            'is_rookie' => 'boolean',
        ];
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function awardAssignments(): HasMany
    {
        return $this->hasMany(AwardAssignment::class);
    }

    /**
     * Check if team is active for a specific event
     */
    public function isActiveForEvent(Event $event): bool
    {
        return $this->events()
            ->where('event_id', $event->id)
            ->wherePivot('is_active', true)
            ->exists();
    }
}

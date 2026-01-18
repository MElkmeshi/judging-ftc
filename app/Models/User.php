<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    public function eventAssignments(): HasMany
    {
        return $this->hasMany(EventUser::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_user')
            ->withPivot(['can_score', 'can_deliberate'])
            ->withTimestamps();
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class, 'judge_id');
    }

    public function awards(): BelongsToMany
    {
        return $this->belongsToMany(Award::class, 'award_judge', 'user_id', 'award_id')
            ->withTimestamps();
    }

    public function isJudge(): bool
    {
        return $this->role === UserRole::Judge;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::SuperAdmin]);
    }

    public function canAccessEvent(Event $event): bool
    {
        if ($this->role === UserRole::SuperAdmin) {
            return true;
        }

        return $this->events()->where('events.id', $event->id)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Judges can only access the judge panel
        if ($panel->getId() === 'judge') {
            return $this->role === UserRole::Judge;
        }

        // Admins and SuperAdmins can access the admin panel
        if ($panel->getId() === 'admin') {
            return in_array($this->role, [UserRole::Admin, UserRole::SuperAdmin]);
        }

        return false;
    }
}

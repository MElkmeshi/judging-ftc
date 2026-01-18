<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\AwardTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'judging_guidelines',
        'is_ranked',
        'is_hierarchical',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_ranked' => 'boolean',
            'is_hierarchical' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function criteriaTemplates(): HasMany
    {
        return $this->hasMany(CriterionTemplate::class);
    }

    public function cloneForEvent(Event $event): Award
    {
        $award = Award::query()->create([
            'event_id' => $event->id,
            'award_template_id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_ranked' => $this->is_ranked,
            'is_hierarchical' => $this->is_hierarchical,
            'is_locked' => false,
            'is_finalized' => false,
        ]);

        foreach ($this->criteriaTemplates as $criterionTemplate) {
            $criterionTemplate->cloneForAward($award);
        }

        return $award;
    }
}

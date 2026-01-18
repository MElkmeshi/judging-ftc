<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriterionTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\CriterionTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'award_template_id',
        'name',
        'description',
        'weight',
        'max_score',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'max_score' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function awardTemplate(): BelongsTo
    {
        return $this->belongsTo(AwardTemplate::class);
    }

    public function cloneForAward(Award $award): Criterion
    {
        return Criterion::query()->create([
            'award_id' => $award->id,
            'criterion_template_id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'weight' => $this->weight,
            'max_score' => $this->max_score,
            'display_order' => $this->display_order,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    /** @use HasFactory<\Database\Factories\CriterionFactory> */
    use HasFactory;

    protected $fillable = [
        'award_id',
        'criterion_template_id',
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

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function criterionTemplate(): BelongsTo
    {
        return $this->belongsTo(CriterionTemplate::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}

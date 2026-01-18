<?php

namespace App\Services;

use App\Models\Award;
use App\Models\Criterion;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AhpCalculationService
{
    /**
     * Calculate AHP-weighted scores for all teams in an award
     */
    public function calculateAwardScores(Award $award): Collection
    {
        $teams = $award->event->activeTeams()->get();
        $criteria = $award->criteria()->where('is_active', true)->orderBy('display_order')->get();

        if ($criteria->isEmpty()) {
            return collect();
        }

        // Validate weights sum to 100%
        $totalWeight = $criteria->sum('weight');
        if (abs($totalWeight - 100.0) > 0.01) {
            throw new \RuntimeException("Criteria weights must sum to 100%, got {$totalWeight}%");
        }

        $teamScores = [];

        foreach ($teams as $team) {
            $weightedScore = 0.0;
            $criteriaScored = 0;

            foreach ($criteria as $criterion) {
                $avgScore = $this->getAverageScoreForTeamCriterion($team, $criterion);

                if ($avgScore !== null) {
                    // Normalize to 0-1 scale, then apply weight
                    $normalizedScore = $avgScore / $criterion->max_score;
                    $weightedScore += $normalizedScore * ($criterion->weight / 100);
                    $criteriaScored++;
                }
            }

            // Only include teams that have been scored on at least one criterion
            if ($criteriaScored > 0) {
                $teamScores[] = [
                    'team_id' => $team->id,
                    'team_number' => $team->team_number,
                    'team_name' => $team->team_name,
                    'team' => $team,
                    'weighted_score' => $weightedScore,
                    'criteria_scored' => $criteriaScored,
                    'total_criteria' => $criteria->count(),
                    'completion_percentage' => ($criteriaScored / $criteria->count()) * 100,
                ];
            }
        }

        // Sort by weighted score descending
        return collect($teamScores)->sortByDesc('weighted_score')->values();
    }

    /**
     * Get average score across all judges for a team/criterion
     */
    protected function getAverageScoreForTeamCriterion(Team $team, Criterion $criterion): ?float
    {
        $scores = DB::table('scores')
            ->where('team_id', $team->id)
            ->where('criterion_id', $criterion->id)
            ->whereNotNull('submitted_at')
            ->avg('score');

        return $scores ? (float) $scores : null;
    }

    /**
     * Calculate rankings with tie handling
     */
    public function calculateRankings(Award $award): Collection
    {
        $scores = $this->calculateAwardScores($award);

        $rank = 1;
        $previousScore = null;
        $teamsAtRank = 0;

        return $scores->map(function ($item) use (&$rank, &$previousScore, &$teamsAtRank) {
            if ($previousScore !== null && abs($item['weighted_score'] - $previousScore) > 0.0001) {
                $rank += $teamsAtRank;
                $teamsAtRank = 0;
            }

            $teamsAtRank++;
            $previousScore = $item['weighted_score'];

            $item['rank'] = $rank;
            $item['is_tied'] = false; // Will be set in post-processing if needed

            return $item;
        });
    }

    /**
     * Suggest award assignments based on rankings and team count
     */
    public function suggestAwardAssignments(Award $award): Collection
    {
        $rankings = $this->calculateRankings($award);
        $availableLevels = $award->getAvailableLevels();

        $suggestions = [];

        foreach ($availableLevels as $index => $level) {
            $rank = $index + 1;
            $teamsAtRank = $rankings->where('rank', $rank);

            if ($teamsAtRank->isNotEmpty()) {
                foreach ($teamsAtRank as $teamScore) {
                    $suggestions[] = [
                        'team_id' => $teamScore['team_id'],
                        'team_number' => $teamScore['team_number'],
                        'team_name' => $teamScore['team_name'],
                        'team' => $teamScore['team'],
                        'level' => $level,
                        'rank' => $rank,
                        'weighted_score' => $teamScore['weighted_score'],
                        'calculated_score' => $teamScore['weighted_score'],
                        'is_tied' => $teamsAtRank->count() > 1,
                        'completion_percentage' => $teamScore['completion_percentage'],
                    ];
                }
            }
        }

        return collect($suggestions);
    }

    /**
     * Get detailed score breakdown for a specific team on an award
     * Shows per-criterion scores and per-judge breakdown
     */
    public function getDetailedScoreBreakdown(Award $award, Team $team): array
    {
        $criteria = $award->criteria()->where('is_active', true)->orderBy('display_order')->get();
        $judges = $award->judges()->get();

        $criteriaBreakdown = [];
        $judgeScores = [];
        $totalWeightedScore = 0.0;

        foreach ($criteria as $criterion) {
            $scores = DB::table('scores')
                ->where('criterion_id', $criterion->id)
                ->where('team_id', $team->id)
                ->whereNotNull('submitted_at')
                ->get();

            $avgScore = $scores->avg('score');
            $normalizedScore = $avgScore !== null ? $avgScore / $criterion->max_score : null;
            $contribution = $normalizedScore !== null ? $normalizedScore * ($criterion->weight / 100) : 0;
            $totalWeightedScore += $contribution;

            $criteriaBreakdown[] = [
                'criterion_id' => $criterion->id,
                'name' => $criterion->name,
                'weight' => $criterion->weight,
                'max_score' => $criterion->max_score,
                'average_score' => $avgScore !== null ? round($avgScore, 2) : null,
                'contribution' => round($contribution, 2),
                'scores_count' => $scores->count(),
            ];
        }

        // Get per-judge scores for each criterion
        foreach ($judges as $judge) {
            $judgeScoreData = [
                'judge_id' => $judge->id,
                'judge_name' => $judge->name,
                'criteria_scores' => [],
            ];

            foreach ($criteria as $criterion) {
                $score = DB::table('scores')
                    ->where('criterion_id', $criterion->id)
                    ->where('team_id', $team->id)
                    ->where('judge_id', $judge->id)
                    ->whereNotNull('submitted_at')
                    ->first();

                $judgeScoreData['criteria_scores'][$criterion->id] = $score?->score;
            }

            $judgeScores[] = $judgeScoreData;
        }

        // Calculate variance for each criterion
        $varianceData = [];
        foreach ($criteria as $criterion) {
            $scores = DB::table('scores')
                ->where('criterion_id', $criterion->id)
                ->where('team_id', $team->id)
                ->whereNotNull('submitted_at')
                ->pluck('score')
                ->toArray();

            if (count($scores) > 1) {
                $mean = array_sum($scores) / count($scores);
                $variance = array_sum(array_map(fn ($s) => pow($s - $mean, 2), $scores)) / count($scores);
                $stdDev = sqrt($variance);
                $varianceData[$criterion->id] = [
                    'variance' => round($variance, 2),
                    'std_dev' => round($stdDev, 2),
                    'is_high' => $stdDev > ($criterion->max_score * 0.2), // High if stddev > 20% of max
                ];
            } else {
                $varianceData[$criterion->id] = [
                    'variance' => 0,
                    'std_dev' => 0,
                    'is_high' => false,
                ];
            }
        }

        return [
            'team_id' => $team->id,
            'team_number' => $team->team_number,
            'team_name' => $team->team_name,
            'final_ahp_score' => round($totalWeightedScore, 2),
            'criteria_breakdown' => $criteriaBreakdown,
            'judge_scores' => $judgeScores,
            'variance_data' => $varianceData,
            'criteria' => $criteria->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->toArray(),
        ];
    }

    /**
     * Calculate overall variance indicator for a team
     * Returns 'low', 'medium', or 'high'
     */
    public function getVarianceLevel(Award $award, Team $team): string
    {
        $criteria = $award->criteria()->where('is_active', true)->get();
        $highVarianceCount = 0;

        foreach ($criteria as $criterion) {
            $scores = DB::table('scores')
                ->where('criterion_id', $criterion->id)
                ->where('team_id', $team->id)
                ->whereNotNull('submitted_at')
                ->pluck('score')
                ->toArray();

            if (count($scores) > 1) {
                $mean = array_sum($scores) / count($scores);
                $variance = array_sum(array_map(fn ($s) => pow($s - $mean, 2), $scores)) / count($scores);
                $stdDev = sqrt($variance);

                if ($stdDev > ($criterion->max_score * 0.2)) {
                    $highVarianceCount++;
                }
            }
        }

        $totalCriteria = $criteria->count();
        if ($totalCriteria === 0) {
            return 'low';
        }

        $ratio = $highVarianceCount / $totalCriteria;

        if ($ratio >= 0.5) {
            return 'high';
        } elseif ($ratio >= 0.25) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get scoring completion statistics for an award
     */
    public function getScoringStats(Award $award): array
    {
        $teams = $award->event->activeTeams()->count();
        $criteria = $award->criteria()->where('is_active', true)->count();
        $judges = $award->judges()->count();

        $totalPossibleScores = $teams * $criteria * $judges;
        $submittedScores = $award->scores()->whereNotNull('submitted_at')->count();

        return [
            'teams_count' => $teams,
            'criteria_count' => $criteria,
            'judges_count' => $judges,
            'total_possible_scores' => $totalPossibleScores,
            'submitted_scores' => $submittedScores,
            'completion_percentage' => $totalPossibleScores > 0
                ? ($submittedScores / $totalPossibleScores) * 100
                : 0,
        ];
    }
}

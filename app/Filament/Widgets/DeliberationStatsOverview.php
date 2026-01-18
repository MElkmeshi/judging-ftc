<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeliberationStatsOverview extends StatsOverviewWidget
{
    public array $scoringStats = [];

    protected function getStats(): array
    {
        if (empty($this->scoringStats)) {
            return [];
        }

        return [
            Stat::make('Total Teams', $this->scoringStats['teams_count'] ?? 0)
                ->description('Teams in this event')
                ->color('gray'),

            Stat::make('Judges Assigned', $this->scoringStats['judges_count'] ?? 0)
                ->description('Judges for this award')
                ->color('info'),

            Stat::make('Submitted Scores', $this->scoringStats['submitted_scores'] ?? 0)
                ->description('Completed scoresheets')
                ->color('success'),

            Stat::make('Completion', number_format($this->scoringStats['completion_percentage'] ?? 0, 1).'%')
                ->description('Overall progress')
                ->color($this->scoringStats['completion_percentage'] >= 100 ? 'success' : 'warning'),
        ];
    }
}

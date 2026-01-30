<?php

namespace App\Filament\Pages;

use App\Models\Award;
use App\Models\Event;
use App\Services\AhpCalculationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class AllAwardsOverview extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected string $view = 'filament.pages.all-awards-overview';

    protected static ?string $navigationLabel = 'All Awards Overview';

    protected static ?string $title = 'All Awards Overview';

    protected static string|UnitEnum|null $navigationGroup = 'Deliberation';

    protected static ?int $navigationSort = 2;

    public ?int $selectedEventId = null;

    public array $awardsData = [];

    public function mount(): void
    {
        // Auto-select event if there's only one
        $events = Event::query()->orderBy('event_date', 'desc')->get();

        if ($events->count() === 1) {
            $this->selectedEventId = $events->first()->id;
            $this->loadAllAwardsData();
        }

        $this->form->fill([
            'selectedEventId' => $this->selectedEventId,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('selectedEventId')
                    ->label('Select Event')
                    ->options(
                        Event::query()
                            ->orderBy('event_date', 'desc')
                            ->pluck('name', 'id')
                    )
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedEventId = $state;
                        $this->loadAllAwardsData();
                    }),
            ]);
    }

    protected function loadAllAwardsData(): void
    {
        if (! $this->selectedEventId) {
            $this->awardsData = [];

            return;
        }

        $awards = Award::where('event_id', $this->selectedEventId)
            ->with(['criteria', 'awardAssignments.team', 'judges'])
            ->orderBy('name')
            ->get();

        $ahpService = app(AhpCalculationService::class);
        $this->awardsData = [];

        foreach ($awards as $award) {
            $rankings = $ahpService->calculateRankings($award);
            $scoringStats = $ahpService->getScoringStats($award);

            // Get top 3 teams
            $topTeams = $rankings->take(3)->map(function ($ranking) {
                return [
                    'team_id' => $ranking['team_id'],
                    'team_number' => $ranking['team_number'],
                    'team_name' => $ranking['team_name'],
                    'weighted_score' => $ranking['weighted_score'],
                    'rank' => $ranking['rank'],
                ];
            })->toArray();

            // Get current assignments
            $assignments = $award->awardAssignments->map(function ($assignment) {
                return [
                    'level' => $assignment->level,
                    'team_id' => $assignment->team_id,
                    'team_number' => $assignment->team?->team_number,
                    'team_name' => $assignment->team?->team_name,
                ];
            })->keyBy('level')->toArray();

            $this->awardsData[$award->id] = [
                'id' => $award->id,
                'name' => $award->name,
                'code' => $award->code,
                'is_locked' => $award->is_locked,
                'is_finalized' => $award->is_finalized,
                'is_ranked' => $award->is_ranked,
                'judges_count' => $award->judges->count(),
                'available_levels' => $award->getAvailableLevels(),
                'scoring_stats' => $scoringStats,
                'top_teams' => $topTeams,
                'assignments' => $assignments,
                'total_teams' => $rankings->count(),
            ];
        }
    }

    protected function getViewData(): array
    {
        $event = $this->selectedEventId ? Event::find($this->selectedEventId) : null;

        return [
            'event' => $event,
            'awardsData' => $this->awardsData,
        ];
    }

    public function refreshData(): void
    {
        $this->loadAllAwardsData();

        Notification::make()
            ->title('Data refreshed')
            ->success()
            ->send();
    }

    public function exportAllCsv(): StreamedResponse
    {
        if (! $this->selectedEventId || empty($this->awardsData)) {
            Notification::make()
                ->title('Nothing to export')
                ->body('Select an event with awards to export.')
                ->warning()
                ->send();

            return response()->streamDownload(fn () => null, 'empty.csv');
        }

        $event = Event::find($this->selectedEventId);

        $filename = sprintf(
            '%s_all_awards_overview_%s.csv',
            str_replace(' ', '_', $event->name ?? 'event'),
            now()->format('Y-m-d')
        );

        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');

            // Header row
            fputcsv($output, [
                'Award',
                'Code',
                'Status',
                'Judges',
                'Completion %',
                '1st Place',
                '2nd Place',
                '3rd Place',
                'Top Ranked Team',
                'Top Score',
            ]);

            // Data rows
            foreach ($this->awardsData as $awardData) {
                $status = 'Open';
                if ($awardData['is_finalized']) {
                    $status = 'Finalized';
                } elseif ($awardData['is_locked']) {
                    $status = 'Locked';
                }

                $first = $awardData['assignments'][1] ?? null;
                $second = $awardData['assignments'][2] ?? null;
                $third = $awardData['assignments'][3] ?? null;

                $topTeam = $awardData['top_teams'][0] ?? null;

                fputcsv($output, [
                    $awardData['name'],
                    $awardData['code'],
                    $status,
                    $awardData['judges_count'],
                    number_format($awardData['scoring_stats']['completion_percentage'] ?? 0, 1).'%',
                    $first ? "#{$first['team_number']} - {$first['team_name']}" : '',
                    $second ? "#{$second['team_number']} - {$second['team_name']}" : '',
                    $third ? "#{$third['team_number']} - {$third['team_name']}" : '',
                    $topTeam ? "#{$topTeam['team_number']} - {$topTeam['team_name']}" : '',
                    $topTeam ? number_format($topTeam['weighted_score'], 4) : '',
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action('refreshData')
                ->visible(fn () => $this->selectedEventId !== null),

            Action::make('exportAllCsv')
                ->label('Export All to CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action('exportAllCsv')
                ->visible(fn () => $this->selectedEventId && ! empty($this->awardsData)),
        ];
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
    }
}

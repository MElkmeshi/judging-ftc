<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DeliberationStatsOverview;
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
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class DeliberationDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected string $view = 'filament.pages.deliberation-dashboard';

    protected static ?string $navigationLabel = 'Deliberation Dashboard';

    protected static ?string $title = 'Deliberation Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Deliberation';

    protected static ?int $navigationSort = 1;

    public ?int $selectedEventId = null;

    public ?int $selectedAwardId = null;

    public array $rankings = [];

    public array $scoringStats = [];

    public bool $showScoreDetailsModal = false;

    public ?int $scoreDetailsTeamId = null;

    public array $scoreDetails = [];

    public function mount(): void
    {
        $this->form->fill();
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
                        $this->selectedAwardId = null;
                        $this->rankings = [];
                        $this->scoringStats = [];
                    }),

                Select::make('selectedAwardId')
                    ->label('Select Award')
                    ->options(function () {
                        if (! $this->selectedEventId) {
                            return [];
                        }

                        return Award::where('event_id', $this->selectedEventId)
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedAwardId = $state;
                        $this->loadRankings();
                    })
                    ->visible(fn () => $this->selectedEventId !== null)
                    ->helperText('Select an award to view rankings and assign winners'),
            ]);
    }

    protected function loadRankings(): void
    {
        if (! $this->selectedAwardId) {
            $this->rankings = [];
            $this->scoringStats = [];

            return;
        }

        $award = Award::with(['event.teams', 'criteria'])->find($this->selectedAwardId);
        if (! $award) {
            $this->rankings = [];
            $this->scoringStats = [];

            return;
        }

        $ahpService = app(AhpCalculationService::class);

        // Get rankings
        $rankings = $ahpService->calculateRankings($award);

        // Add variance level to each ranking
        $this->rankings = $rankings->map(function ($ranking) use ($ahpService, $award) {
            $team = $ranking['team'] ?? null;
            if ($team) {
                $ranking['variance_level'] = $ahpService->getVarianceLevel($award, $team);
                $ranking['is_rookie'] = $team->is_rookie;
            } else {
                $ranking['variance_level'] = 'low';
                $ranking['is_rookie'] = false;
            }
            // Remove the team object from the array to keep it serializable
            unset($ranking['team']);

            return $ranking;
        })->toArray();

        // Get scoring stats
        $this->scoringStats = $ahpService->getScoringStats($award);
    }

    public function viewScoreDetails(int $teamId): void
    {
        if (! $this->selectedAwardId) {
            return;
        }

        $award = Award::find($this->selectedAwardId);
        $team = \App\Models\Team::find($teamId);

        if (! $award || ! $team) {
            return;
        }

        $ahpService = app(AhpCalculationService::class);
        $this->scoreDetails = $ahpService->getDetailedScoreBreakdown($award, $team);
        $this->scoreDetailsTeamId = $teamId;
        $this->showScoreDetailsModal = true;

        $this->dispatch('open-modal', id: 'score-details-modal');
    }

    public function closeScoreDetailsModal(): void
    {
        $this->showScoreDetailsModal = false;
        $this->scoreDetailsTeamId = null;
        $this->scoreDetails = [];

        $this->dispatch('close-modal', id: 'score-details-modal');
    }

    protected function getHeaderWidgets(): array
    {
        if (empty($this->scoringStats)) {
            return [];
        }

        return [
            DeliberationStatsOverview::make([
                'scoringStats' => $this->scoringStats,
            ]),
        ];
    }

    protected function getViewData(): array
    {
        $award = $this->selectedAwardId ? Award::with('awardAssignments')->find($this->selectedAwardId) : null;
        $event = $this->selectedEventId ? Event::find($this->selectedEventId) : null;

        return [
            'award' => $award,
            'event' => $event,
            'rankings' => $this->rankings,
            'scoringStats' => $this->scoringStats,
            'availableLevels' => $award?->getAvailableLevels() ?? [],
            'showScoreDetailsModal' => $this->showScoreDetailsModal,
            'scoreDetails' => $this->scoreDetails,
        ];
    }

    public function assignAward(int $teamId, int $level): void
    {
        if (! $this->selectedAwardId) {
            Notification::make()
                ->title('Please select an award')
                ->danger()
                ->send();

            return;
        }

        $award = Award::find($this->selectedAwardId);

        if (! $award) {
            Notification::make()
                ->title('Award not found')
                ->danger()
                ->send();

            return;
        }

        if ($award->is_locked) {
            Notification::make()
                ->title('Cannot assign locked award')
                ->body('Unlock the award before making changes')
                ->danger()
                ->send();

            return;
        }

        // Check authorization
        if (! Gate::allows('deliberate', $award)) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You do not have permission to assign this award')
                ->danger()
                ->send();

            return;
        }

        // Remove existing assignment at this level
        $award->awardAssignments()->where('level', $level)->delete();

        // Create new assignment
        $award->awardAssignments()->create([
            'team_id' => $teamId,
            'level' => $level,
            'assigned_by' => Auth::id(),
        ]);

        Notification::make()
            ->title('Award assigned successfully')
            ->success()
            ->send();

        $this->loadRankings();
    }

    public function lockAward(): void
    {
        if (! $this->selectedAwardId) {
            return;
        }

        $award = Award::find($this->selectedAwardId);
        if (! $award) {
            return;
        }

        $award->update(['is_locked' => true]);

        Notification::make()
            ->title('Award locked')
            ->body('Judges can no longer score this award')
            ->success()
            ->send();

        $this->loadRankings();
    }

    public function unlockAward(): void
    {
        if (! $this->selectedAwardId) {
            return;
        }

        $award = Award::find($this->selectedAwardId);
        if (! $award) {
            return;
        }

        $award->update(['is_locked' => false]);

        Notification::make()
            ->title('Award unlocked')
            ->body('Judges can now score this award')
            ->warning()
            ->send();

        $this->loadRankings();
    }

    public function finalizeAward(): void
    {
        if (! $this->selectedAwardId) {
            return;
        }

        $award = Award::find($this->selectedAwardId);
        if (! $award) {
            return;
        }

        if (! $award->is_locked) {
            Notification::make()
                ->title('Cannot finalize unlocked award')
                ->body('Lock the award before finalizing')
                ->danger()
                ->send();

            return;
        }

        $award->update(['is_finalized' => true]);

        Notification::make()
            ->title('Award finalized')
            ->body('This award is now official and cannot be changed')
            ->success()
            ->send();

        $this->loadRankings();
    }

    public function exportCsv(): StreamedResponse
    {
        if (! $this->selectedAwardId || empty($this->rankings)) {
            Notification::make()
                ->title('Nothing to export')
                ->body('Select an award with rankings to export.')
                ->warning()
                ->send();

            return response()->streamDownload(fn () => null, 'empty.csv');
        }

        $award = Award::with(['awardAssignments', 'event'])->find($this->selectedAwardId);

        $filename = sprintf(
            '%s_%s_rankings_%s.csv',
            str_replace(' ', '_', $award->event->name ?? 'event'),
            str_replace(' ', '_', $award->name ?? 'award'),
            now()->format('Y-m-d')
        );

        return response()->streamDownload(function () use ($award) {
            $output = fopen('php://output', 'w');

            // Header row
            fputcsv($output, [
                'Rank',
                'Team Number',
                'Team Name',
                'AHP Score',
                'Completion %',
                'Variance',
                'Assignment',
                'Is Rookie',
            ]);

            // Data rows
            foreach ($this->rankings as $ranking) {
                $assignment = $award->awardAssignments
                    ->where('team_id', $ranking['team_id'])
                    ->first();

                $assignmentLabel = $assignment
                    ? match ($assignment->level) {
                        1 => '1st Place',
                        2 => '2nd Place',
                        3 => '3rd Place',
                        default => 'Winner'
                    }
                : '';

                fputcsv($output, [
                    $ranking['rank'],
                    $ranking['team_number'],
                    $ranking['team_name'],
                    number_format($ranking['weighted_score'], 4),
                    number_format($ranking['completion_percentage'], 1),
                    $ranking['variance_level'] ?? 'low',
                    $assignmentLabel,
                    ($ranking['is_rookie'] ?? false) ? 'Yes' : 'No',
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
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action('exportCsv')
                ->visible(fn () => $this->selectedAwardId && ! empty($this->rankings)),

            Action::make('lockAward')
                ->label('Lock Award')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->action('lockAward')
                ->requiresConfirmation()
                ->modalHeading('Lock Award?')
                ->modalDescription('Judges will no longer be able to score this award.')
                ->visible(fn () => $this->selectedAwardId && Award::find($this->selectedAwardId)?->is_locked === false),

            Action::make('unlockAward')
                ->label('Unlock Award')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('gray')
                ->action('unlockAward')
                ->requiresConfirmation()
                ->modalHeading('Unlock Award?')
                ->modalDescription('Judges will be able to score this award again.')
                ->visible(fn () => $this->selectedAwardId && Award::find($this->selectedAwardId)?->is_locked === true && Award::find($this->selectedAwardId)?->is_finalized === false),

            Action::make('finalizeAward')
                ->label('Finalize Award')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->action('finalizeAward')
                ->requiresConfirmation()
                ->modalHeading('Finalize Award?')
                ->modalDescription('This will make the award assignments official and permanent. This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Finalize')
                ->visible(fn () => $this->selectedAwardId && Award::find($this->selectedAwardId)?->is_locked === true && Award::find($this->selectedAwardId)?->is_finalized === false),
        ];
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
    }
}

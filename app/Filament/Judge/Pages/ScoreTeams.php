<?php

namespace App\Filament\Judge\Pages;

use App\Models\Award;
use App\Models\Score;
use App\Models\Team;
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

class ScoreTeams extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected string $view = 'filament.judge.pages.score-teams';

    protected static ?string $navigationLabel = 'Score Teams';

    protected static ?string $title = 'Score Teams';

    public ?int $selectedAwardId = null;

    public ?int $selectedTeamId = null;

    public array $scores = [];

    public string $viewMode = 'all'; // 'single' or 'all'

    public array $allTeamsScores = [];

    public array $expandedTeams = [];

    public array $progressStats = [
        'total_teams' => 0,
        'scored_teams' => 0,
        'draft_teams' => 0,
        'pending_teams' => 0,
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('selectedAwardId')
                    ->label('Select Award')
                    ->options(function () {
                        $user = Auth::user();
                        if (! $user) {
                            return [];
                        }

                        // Get only awards assigned to this judge
                        return $user
                            ->eventAssignments()
                            ->with('event.awards.judges')
                            ->get()
                            ->flatMap(fn ($eventUser) => $eventUser->event->awards)
                            ->filter(fn ($award) => $award->judges->contains(Auth::id()))
                            ->filter(fn ($award) => $award->canBeScored())
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedAwardId = $state;
                        $this->selectedTeamId = null;
                        $this->scores = [];
                        $this->allTeamsScores = [];
                        $this->expandedTeams = [];
                        $this->loadAllTeamsScores();
                    })
                    ->helperText('You can only score awards you are assigned to during the judging phase'),

                Select::make('selectedTeamId')
                    ->label('Select Team to Score')
                    ->options(function () {
                        if (! $this->selectedAwardId) {
                            return [];
                        }

                        $award = Award::find($this->selectedAwardId);
                        if (! $award) {
                            return [];
                        }

                        return $award->event->activeTeams()
                            ->orderBy('team_number')
                            ->get()
                            ->mapWithKeys(function ($team) use ($award) {
                                $hasScores = Score::where('award_id', $award->id)
                                    ->where('team_id', $team->id)
                                    ->where('judge_id', Auth::id())
                                    ->exists();

                                $label = "#{$team->team_number} - {$team->team_name}";
                                if ($hasScores) {
                                    $label .= ' ✓';
                                }

                                return [$team->id => $label];
                            });
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedTeamId = $state;
                        $this->loadScores();
                    })
                    ->visible(fn () => $this->selectedAwardId !== null && $this->viewMode === 'single')
                    ->helperText('Select a team to score. Teams with ✓ already have your scores.'),
            ]);
    }

    public function loadScores(): void
    {
        if (! $this->selectedAwardId || ! $this->selectedTeamId) {
            $this->scores = [];

            return;
        }

        $award = Award::with('criteria')->find($this->selectedAwardId);
        if (! $award) {
            $this->scores = [];

            return;
        }

        // Load existing scores for this judge/team/award
        $existingScores = Score::where('award_id', $this->selectedAwardId)
            ->where('team_id', $this->selectedTeamId)
            ->where('judge_id', Auth::id())
            ->get()
            ->keyBy('criterion_id');

        // Build scores array with criteria
        $this->scores = $award->criteria()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(function ($criterion) use ($existingScores) {
                $existingScore = $existingScores->get($criterion->id);

                return [
                    'criterion_id' => $criterion->id,
                    'criterion_name' => $criterion->name,
                    'criterion_description' => $criterion->description,
                    'max_score' => $criterion->max_score,
                    'weight' => $criterion->weight,
                    'score' => $existingScore?->score,
                    'notes' => $existingScore?->notes,
                    'is_submitted' => $existingScore?->submitted_at !== null,
                ];
            })
            ->toArray();
    }

    protected function loadAllTeamsScores(): void
    {
        if (! $this->selectedAwardId) {
            $this->allTeamsScores = [];
            $this->progressStats = [
                'total_teams' => 0,
                'scored_teams' => 0,
                'draft_teams' => 0,
                'pending_teams' => 0,
            ];

            return;
        }

        $award = Award::with('criteria')->find($this->selectedAwardId);
        if (! $award) {
            $this->allTeamsScores = [];

            return;
        }

        $teams = $award->event->activeTeams()
            ->orderBy('team_number')
            ->get();

        $criteria = $award->criteria()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $this->allTeamsScores = [];
        $scoredTeams = 0;
        $draftTeams = 0;
        $pendingTeams = 0;

        foreach ($teams as $team) {
            // Load existing scores for this judge/team/award
            $existingScores = Score::where('award_id', $this->selectedAwardId)
                ->where('team_id', $team->id)
                ->where('judge_id', Auth::id())
                ->get()
                ->keyBy('criterion_id');

            $teamScores = [];
            $hasAnyScore = false;
            $allSubmitted = true;
            $allScored = true;

            foreach ($criteria as $criterion) {
                $existingScore = $existingScores->get($criterion->id);

                if ($existingScore) {
                    $hasAnyScore = true;
                    if ($existingScore->submitted_at === null) {
                        $allSubmitted = false;
                    }
                } else {
                    $allSubmitted = false;
                    $allScored = false;
                }

                $teamScores[] = [
                    'criterion_id' => $criterion->id,
                    'criterion_name' => $criterion->name,
                    'criterion_description' => $criterion->description,
                    'max_score' => $criterion->max_score,
                    'weight' => $criterion->weight,
                    'score' => $existingScore?->score,
                    'notes' => $existingScore?->notes,
                    'is_submitted' => $existingScore?->submitted_at !== null,
                ];
            }

            // Determine status
            $status = 'pending';
            if ($hasAnyScore && $allSubmitted && $allScored) {
                $status = 'submitted';
                $scoredTeams++;
            } elseif ($hasAnyScore) {
                $status = 'draft';
                $draftTeams++;
            } else {
                $pendingTeams++;
            }

            $this->allTeamsScores[$team->id] = [
                'team_id' => $team->id,
                'team_number' => $team->team_number,
                'team_name' => $team->team_name,
                'is_rookie' => $team->is_rookie,
                'status' => $status,
                'scores' => $teamScores,
            ];
        }

        $this->progressStats = [
            'total_teams' => $teams->count(),
            'scored_teams' => $scoredTeams,
            'draft_teams' => $draftTeams,
            'pending_teams' => $pendingTeams,
        ];

        // Auto-expand first pending team
        if (empty($this->expandedTeams)) {
            foreach ($this->allTeamsScores as $teamId => $teamData) {
                if ($teamData['status'] !== 'submitted') {
                    $this->expandedTeams[$teamId] = true;
                    break;
                }
            }
        }
    }

    public function toggleTeamCard(int $teamId): void
    {
        if (isset($this->expandedTeams[$teamId])) {
            unset($this->expandedTeams[$teamId]);
        } else {
            $this->expandedTeams[$teamId] = true;
        }
    }

    protected function getViewData(): array
    {
        $award = $this->selectedAwardId ? Award::find($this->selectedAwardId) : null;
        $team = $this->selectedTeamId ? Team::find($this->selectedTeamId) : null;

        return [
            'award' => $award,
            'team' => $team,
            'scores' => $this->scores,
            'viewMode' => $this->viewMode,
            'allTeamsScores' => $this->allTeamsScores,
            'expandedTeams' => $this->expandedTeams,
            'progressStats' => $this->progressStats,
        ];
    }

    public function saveDraft(): void
    {
        if (! $this->selectedAwardId || ! $this->selectedTeamId) {
            Notification::make()
                ->title('Please select an award and team')
                ->danger()
                ->send();

            return;
        }

        $this->saveScores(false);

        Notification::make()
            ->title('Draft saved successfully')
            ->body('You can continue editing these scores later.')
            ->success()
            ->send();
    }

    public function submitScores(): void
    {
        if (! $this->selectedAwardId || ! $this->selectedTeamId) {
            Notification::make()
                ->title('Please select an award and team')
                ->danger()
                ->send();

            return;
        }

        // Validate all criteria have scores
        $allScored = collect($this->scores)->every(fn ($item) => isset($item['score']) && $item['score'] !== null && $item['score'] !== '');

        if (! $allScored) {
            Notification::make()
                ->title('Please score all criteria')
                ->body('All criteria must have a score before submitting.')
                ->danger()
                ->send();

            return;
        }

        $this->saveScores(true);

        Notification::make()
            ->title('Scores submitted successfully')
            ->body('These scores are now locked and cannot be edited.')
            ->success()
            ->send();
    }

    protected function saveScores(bool $submit): void
    {
        $award = Award::find($this->selectedAwardId);

        // Check authorization
        if (! Gate::allows('score', $award)) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You cannot score this award at this time.')
                ->danger()
                ->send();

            return;
        }

        foreach ($this->scores as $scoreData) {
            if (! isset($scoreData['score']) || $scoreData['score'] === null || $scoreData['score'] === '') {
                continue;
            }

            Score::updateOrCreate(
                [
                    'award_id' => $this->selectedAwardId,
                    'criterion_id' => $scoreData['criterion_id'],
                    'team_id' => $this->selectedTeamId,
                    'judge_id' => Auth::id(),
                ],
                [
                    'score' => $scoreData['score'],
                    'notes' => $scoreData['notes'] ?? null,
                    'submitted_at' => $submit ? now() : null,
                ]
            );
        }

        // Reload scores to reflect submission status
        $this->loadScores();
    }

    public function saveTeamDraft(int $teamId): void
    {
        if (! $this->selectedAwardId) {
            return;
        }

        $award = Award::find($this->selectedAwardId);
        if (! $award || ! Gate::allows('score', $award)) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You cannot score this award at this time.')
                ->danger()
                ->send();

            return;
        }

        $teamScores = $this->allTeamsScores[$teamId]['scores'] ?? [];

        foreach ($teamScores as $scoreData) {
            if (! isset($scoreData['score']) || $scoreData['score'] === null || $scoreData['score'] === '') {
                continue;
            }

            Score::updateOrCreate(
                [
                    'award_id' => $this->selectedAwardId,
                    'criterion_id' => $scoreData['criterion_id'],
                    'team_id' => $teamId,
                    'judge_id' => Auth::id(),
                ],
                [
                    'score' => $scoreData['score'],
                    'notes' => $scoreData['notes'] ?? null,
                    'submitted_at' => null,
                ]
            );
        }

        $this->loadAllTeamsScores();

        Notification::make()
            ->title('Draft saved')
            ->body("Scores for team #{$this->allTeamsScores[$teamId]['team_number']} saved as draft.")
            ->success()
            ->send();
    }

    public function submitTeamScores(int $teamId): void
    {
        if (! $this->selectedAwardId) {
            return;
        }

        $award = Award::find($this->selectedAwardId);
        if (! $award || ! Gate::allows('score', $award)) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You cannot score this award at this time.')
                ->danger()
                ->send();

            return;
        }

        $teamScores = $this->allTeamsScores[$teamId]['scores'] ?? [];

        // Validate all criteria have scores
        $allScored = collect($teamScores)->every(fn ($item) => isset($item['score']) && $item['score'] !== null && $item['score'] !== '');

        if (! $allScored) {
            Notification::make()
                ->title('Please score all criteria')
                ->body('All criteria must have a score before submitting.')
                ->danger()
                ->send();

            return;
        }

        foreach ($teamScores as $scoreData) {
            Score::updateOrCreate(
                [
                    'award_id' => $this->selectedAwardId,
                    'criterion_id' => $scoreData['criterion_id'],
                    'team_id' => $teamId,
                    'judge_id' => Auth::id(),
                ],
                [
                    'score' => $scoreData['score'],
                    'notes' => $scoreData['notes'] ?? null,
                    'submitted_at' => now(),
                ]
            );
        }

        $this->loadAllTeamsScores();

        // Auto-collapse submitted team and expand next pending
        unset($this->expandedTeams[$teamId]);
        foreach ($this->allTeamsScores as $id => $teamData) {
            if ($teamData['status'] !== 'submitted' && ! isset($this->expandedTeams[$id])) {
                $this->expandedTeams[$id] = true;
                break;
            }
        }

        Notification::make()
            ->title('Scores submitted')
            ->body("Scores for team #{$this->allTeamsScores[$teamId]['team_number']} have been submitted.")
            ->success()
            ->send();
    }

    public function saveAllDrafts(): void
    {
        if (! $this->selectedAwardId) {
            return;
        }

        $award = Award::find($this->selectedAwardId);
        if (! $award || ! Gate::allows('score', $award)) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You cannot score this award at this time.')
                ->danger()
                ->send();

            return;
        }

        $savedCount = 0;

        foreach ($this->allTeamsScores as $teamId => $teamData) {
            if ($teamData['status'] === 'submitted') {
                continue;
            }

            $hasScore = false;
            foreach ($teamData['scores'] as $scoreData) {
                if (isset($scoreData['score']) && $scoreData['score'] !== null && $scoreData['score'] !== '') {
                    Score::updateOrCreate(
                        [
                            'award_id' => $this->selectedAwardId,
                            'criterion_id' => $scoreData['criterion_id'],
                            'team_id' => $teamId,
                            'judge_id' => Auth::id(),
                        ],
                        [
                            'score' => $scoreData['score'],
                            'notes' => $scoreData['notes'] ?? null,
                            'submitted_at' => null,
                        ]
                    );
                    $hasScore = true;
                }
            }

            if ($hasScore) {
                $savedCount++;
            }
        }

        $this->loadAllTeamsScores();

        Notification::make()
            ->title('Drafts saved')
            ->body("Saved drafts for {$savedCount} team(s).")
            ->success()
            ->send();
    }

    public function submitAllComplete(): void
    {
        if (! $this->selectedAwardId) {
            return;
        }

        $award = Award::find($this->selectedAwardId);
        if (! $award || ! Gate::allows('score', $award)) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You cannot score this award at this time.')
                ->danger()
                ->send();

            return;
        }

        $submittedCount = 0;

        foreach ($this->allTeamsScores as $teamId => $teamData) {
            if ($teamData['status'] === 'submitted') {
                continue;
            }

            // Check if all criteria have scores
            $allScored = collect($teamData['scores'])->every(fn ($item) => isset($item['score']) && $item['score'] !== null && $item['score'] !== '');

            if (! $allScored) {
                continue;
            }

            foreach ($teamData['scores'] as $scoreData) {
                Score::updateOrCreate(
                    [
                        'award_id' => $this->selectedAwardId,
                        'criterion_id' => $scoreData['criterion_id'],
                        'team_id' => $teamId,
                        'judge_id' => Auth::id(),
                    ],
                    [
                        'score' => $scoreData['score'],
                        'notes' => $scoreData['notes'] ?? null,
                        'submitted_at' => now(),
                    ]
                );
            }

            $submittedCount++;
        }

        $this->loadAllTeamsScores();

        if ($submittedCount > 0) {
            Notification::make()
                ->title('Scores submitted')
                ->body("Submitted scores for {$submittedCount} team(s) with complete scoring.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('No teams to submit')
                ->body('Make sure all criteria are scored for at least one team.')
                ->warning()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Single view mode actions
            Action::make('saveDraft')
                ->label('Save Draft')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->action('saveDraft')
                ->disabled(fn () => ! $this->selectedTeamId)
                ->visible(fn () => $this->viewMode === 'single'),

            Action::make('submitScores')
                ->label('Submit Scores')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->action('submitScores')
                ->disabled(fn () => ! $this->selectedTeamId)
                ->requiresConfirmation()
                ->modalHeading('Submit Scores?')
                ->modalDescription('Once submitted, these scores cannot be edited. Make sure all scores are accurate before submitting.')
                ->modalSubmitActionLabel('Yes, Submit Scores')
                ->visible(fn () => $this->viewMode === 'single'),

            // All view mode actions
            Action::make('saveAllDrafts')
                ->label('Save All Drafts')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->action('saveAllDrafts')
                ->disabled(fn () => ! $this->selectedAwardId || empty($this->allTeamsScores))
                ->visible(fn () => $this->viewMode === 'all'),

            Action::make('submitAllComplete')
                ->label('Submit All Complete')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->action('submitAllComplete')
                ->disabled(fn () => ! $this->selectedAwardId || empty($this->allTeamsScores))
                ->requiresConfirmation()
                ->modalHeading('Submit All Complete Scores?')
                ->modalDescription('All teams with complete scoring will be submitted. Once submitted, these scores cannot be edited.')
                ->modalSubmitActionLabel('Yes, Submit All')
                ->visible(fn () => $this->viewMode === 'all'),
        ];
    }
}

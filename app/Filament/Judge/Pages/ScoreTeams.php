<?php

namespace App\Filament\Judge\Pages;

use App\Models\Award;
use App\Models\Event;
use App\Models\Score;
use App\Models\Team;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
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

    public ?int $selectedEventId = null;

    public ?int $selectedAwardId = null;

    public ?int $selectedTeamId = null;

    public array $scores = [];

    public string $viewMode = 'byAward'; // 'byAward' or 'byTeam'

    public array $allTeamsScores = [];

    public array $allAwardsScores = [];

    public array $expandedTeams = [];

    public array $progressStats = [
        'total_teams' => 0,
        'scored_teams' => 0,
        'draft_teams' => 0,
        'pending_teams' => 0,
    ];

    public function mount(): void
    {
        // Auto-select event if there's only one
        $user = Auth::user();
        if ($user) {
            $events = $user->events()
                ->wherePivot('can_score', true)
                ->where('status', 'judging')
                ->get();

            if ($events->count() === 1) {
                $this->selectedEventId = $events->first()->id;
            }
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
                    ->options(function () {
                        $user = Auth::user();
                        if (! $user) {
                            return [];
                        }

                        return $user->events()
                            ->wherePivot('can_score', true)
                            ->where('status', 'judging')
                            ->orderBy('events.name')
                            ->pluck('events.name', 'events.id');
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedEventId = $state;
                        $this->selectedAwardId = null;
                        $this->selectedTeamId = null;
                        $this->scores = [];
                        $this->allTeamsScores = [];
                        $this->allAwardsScores = [];
                        $this->expandedTeams = [];
                    })
                    ->helperText('Select the event you are judging'),

                ToggleButtons::make('viewMode')
                    ->label('View Mode')
                    ->options([
                        'byAward' => 'By Award',
                        'byTeam' => 'By Team',
                    ])
                    ->icons([
                        'byAward' => 'heroicon-o-trophy',
                        'byTeam' => 'heroicon-o-users',
                    ])
                    ->default('byAward')
                    ->inline()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->viewMode = $state;
                        $this->selectedAwardId = null;
                        $this->selectedTeamId = null;
                        $this->scores = [];
                        $this->allTeamsScores = [];
                        $this->allAwardsScores = [];
                        $this->expandedTeams = [];
                    })
                    ->visible(fn () => $this->selectedEventId !== null)
                    ->helperText('Choose to view by award (all teams) or by team (all your awards)'),

                Select::make('selectedAwardId')
                    ->label('Select Award')
                    ->options(function () {
                        $user = Auth::user();
                        if (! $user || ! $this->selectedEventId) {
                            return [];
                        }

                        return Award::query()
                            ->where('event_id', $this->selectedEventId)
                            ->whereHas('judges', fn ($query) => $query->where('users.id', Auth::id()))
                            ->where('is_locked', false)
                            ->whereHas('event', fn ($query) => $query->where('status', 'judging'))
                            ->orderBy('name')
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
                    ->visible(fn () => $this->selectedEventId !== null && $this->viewMode === 'byAward')
                    ->helperText('You can only score awards you are assigned to during the judging phase'),

                Select::make('selectedTeamId')
                    ->label('Select Team')
                    ->options(function () {
                        if (! $this->selectedEventId) {
                            return [];
                        }

                        $event = Event::find($this->selectedEventId);
                        if (! $event) {
                            return [];
                        }

                        return $event->activeTeams()
                            ->orderBy('team_number')
                            ->get()
                            ->mapWithKeys(function ($team) {
                                return [$team->id => "#{$team->team_number} - {$team->team_name}"];
                            });
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedTeamId = $state;
                        $this->selectedAwardId = null;
                        $this->scores = [];
                        $this->allAwardsScores = [];
                        $this->expandedTeams = [];
                        $this->loadAllAwardsScores();
                    })
                    ->visible(fn () => $this->selectedEventId !== null && $this->viewMode === 'byTeam')
                    ->helperText('Select a team to score across all your assigned awards'),
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

    protected function loadAllAwardsScores(): void
    {
        if (! $this->selectedTeamId || ! $this->selectedEventId) {
            $this->allAwardsScores = [];
            $this->progressStats = [
                'total_teams' => 0,
                'scored_teams' => 0,
                'draft_teams' => 0,
                'pending_teams' => 0,
            ];

            return;
        }

        $user = Auth::user();
        if (! $user) {
            $this->allAwardsScores = [];

            return;
        }

        // Get all awards assigned to this judge for the selected event
        $awards = Award::query()
            ->where('event_id', $this->selectedEventId)
            ->whereHas('judges', fn ($query) => $query->where('users.id', Auth::id()))
            ->where('is_locked', false)
            ->whereHas('event', fn ($query) => $query->where('status', 'judging'))
            ->with('criteria')
            ->orderBy('name')
            ->get();

        $this->allAwardsScores = [];
        $scoredAwards = 0;
        $draftAwards = 0;
        $pendingAwards = 0;

        foreach ($awards as $award) {
            $criteria = $award->criteria()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get();

            // Load existing scores for this judge/team/award
            $existingScores = Score::where('award_id', $award->id)
                ->where('team_id', $this->selectedTeamId)
                ->where('judge_id', Auth::id())
                ->get()
                ->keyBy('criterion_id');

            $awardScores = [];
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

                $awardScores[] = [
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
                $scoredAwards++;
            } elseif ($hasAnyScore) {
                $status = 'draft';
                $draftAwards++;
            } else {
                $pendingAwards++;
            }

            $this->allAwardsScores[$award->id] = [
                'award_id' => $award->id,
                'award_name' => $award->name,
                'award_description' => $award->description,
                'status' => $status,
                'scores' => $awardScores,
            ];
        }

        $this->progressStats = [
            'total_teams' => $awards->count(),
            'scored_teams' => $scoredAwards,
            'draft_teams' => $draftAwards,
            'pending_teams' => $pendingAwards,
        ];

        // Auto-expand first pending award
        if (empty($this->expandedTeams)) {
            foreach ($this->allAwardsScores as $awardId => $awardData) {
                if ($awardData['status'] !== 'submitted') {
                    $this->expandedTeams[$awardId] = true;
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
            'allAwardsScores' => $this->allAwardsScores,
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

    public function saveAwardDraft(int $awardId): void
    {
        if (! $this->selectedTeamId) {
            return;
        }

        $award = Award::find($awardId);
        if (! $award || ! Gate::allows('score', $award)) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You cannot score this award at this time.')
                ->danger()
                ->send();

            return;
        }

        $awardScores = $this->allAwardsScores[$awardId]['scores'] ?? [];

        foreach ($awardScores as $scoreData) {
            if (! isset($scoreData['score']) || $scoreData['score'] === null || $scoreData['score'] === '') {
                continue;
            }

            Score::updateOrCreate(
                [
                    'award_id' => $awardId,
                    'criterion_id' => $scoreData['criterion_id'],
                    'team_id' => $this->selectedTeamId,
                    'judge_id' => Auth::id(),
                ],
                [
                    'score' => $scoreData['score'],
                    'notes' => $scoreData['notes'] ?? null,
                    'submitted_at' => null,
                ]
            );
        }

        $this->loadAllAwardsScores();

        Notification::make()
            ->title('Draft saved')
            ->body("Scores for {$this->allAwardsScores[$awardId]['award_name']} saved as draft.")
            ->success()
            ->send();
    }

    public function submitAwardScores(int $awardId): void
    {
        if (! $this->selectedTeamId) {
            return;
        }

        $award = Award::find($awardId);
        if (! $award || ! Gate::allows('score', $award)) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You cannot score this award at this time.')
                ->danger()
                ->send();

            return;
        }

        $awardScores = $this->allAwardsScores[$awardId]['scores'] ?? [];

        // Validate all criteria have scores
        $allScored = collect($awardScores)->every(fn ($item) => isset($item['score']) && $item['score'] !== null && $item['score'] !== '');

        if (! $allScored) {
            Notification::make()
                ->title('Please score all criteria')
                ->body('All criteria must have a score before submitting.')
                ->danger()
                ->send();

            return;
        }

        foreach ($awardScores as $scoreData) {
            Score::updateOrCreate(
                [
                    'award_id' => $awardId,
                    'criterion_id' => $scoreData['criterion_id'],
                    'team_id' => $this->selectedTeamId,
                    'judge_id' => Auth::id(),
                ],
                [
                    'score' => $scoreData['score'],
                    'notes' => $scoreData['notes'] ?? null,
                    'submitted_at' => now(),
                ]
            );
        }

        $this->loadAllAwardsScores();

        // Auto-collapse submitted award and expand next pending
        unset($this->expandedTeams[$awardId]);
        foreach ($this->allAwardsScores as $id => $awardData) {
            if ($awardData['status'] !== 'submitted' && ! isset($this->expandedTeams[$id])) {
                $this->expandedTeams[$id] = true;
                break;
            }
        }

        Notification::make()
            ->title('Scores submitted')
            ->body("Scores for {$this->allAwardsScores[$awardId]['award_name']} have been submitted.")
            ->success()
            ->send();
    }

    public function saveAllAwardDrafts(): void
    {
        if (! $this->selectedTeamId) {
            return;
        }

        $savedCount = 0;

        foreach ($this->allAwardsScores as $awardId => $awardData) {
            if ($awardData['status'] === 'submitted') {
                continue;
            }

            $award = Award::find($awardId);
            if (! $award || ! Gate::allows('score', $award)) {
                continue;
            }

            $hasScore = false;
            foreach ($awardData['scores'] as $scoreData) {
                if (isset($scoreData['score']) && $scoreData['score'] !== null && $scoreData['score'] !== '') {
                    Score::updateOrCreate(
                        [
                            'award_id' => $awardId,
                            'criterion_id' => $scoreData['criterion_id'],
                            'team_id' => $this->selectedTeamId,
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

        $this->loadAllAwardsScores();

        Notification::make()
            ->title('Drafts saved')
            ->body("Saved drafts for {$savedCount} award(s).")
            ->success()
            ->send();
    }

    public function submitAllAwardsComplete(): void
    {
        if (! $this->selectedTeamId) {
            return;
        }

        $submittedCount = 0;

        foreach ($this->allAwardsScores as $awardId => $awardData) {
            if ($awardData['status'] === 'submitted') {
                continue;
            }

            $award = Award::find($awardId);
            if (! $award || ! Gate::allows('score', $award)) {
                continue;
            }

            // Check if all criteria have scores
            $allScored = collect($awardData['scores'])->every(fn ($item) => isset($item['score']) && $item['score'] !== null && $item['score'] !== '');

            if (! $allScored) {
                continue;
            }

            foreach ($awardData['scores'] as $scoreData) {
                Score::updateOrCreate(
                    [
                        'award_id' => $awardId,
                        'criterion_id' => $scoreData['criterion_id'],
                        'team_id' => $this->selectedTeamId,
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

        $this->loadAllAwardsScores();

        if ($submittedCount > 0) {
            Notification::make()
                ->title('Scores submitted')
                ->body("Submitted scores for {$submittedCount} award(s) with complete scoring.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('No awards to submit')
                ->body('Make sure all criteria are scored for at least one award.')
                ->warning()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // By Award view mode actions
            Action::make('saveAllDrafts')
                ->label('Save All Drafts')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->action('saveAllDrafts')
                ->disabled(fn () => ! $this->selectedAwardId || empty($this->allTeamsScores))
                ->visible(fn () => $this->viewMode === 'byAward'),

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
                ->visible(fn () => $this->viewMode === 'byAward'),

            // By Team view mode actions
            Action::make('saveAllAwardDrafts')
                ->label('Save All Drafts')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->action('saveAllAwardDrafts')
                ->disabled(fn () => ! $this->selectedTeamId || empty($this->allAwardsScores))
                ->visible(fn () => $this->viewMode === 'byTeam'),

            Action::make('submitAllAwardsComplete')
                ->label('Submit All Complete')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->action('submitAllAwardsComplete')
                ->disabled(fn () => ! $this->selectedTeamId || empty($this->allAwardsScores))
                ->requiresConfirmation()
                ->modalHeading('Submit All Complete Scores?')
                ->modalDescription('All awards with complete scoring will be submitted. Once submitted, these scores cannot be edited.')
                ->modalSubmitActionLabel('Yes, Submit All')
                ->visible(fn () => $this->viewMode === 'byTeam'),
        ];
    }
}

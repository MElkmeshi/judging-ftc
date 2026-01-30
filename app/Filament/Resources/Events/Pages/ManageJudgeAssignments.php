<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\Award;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class ManageJudgeAssignments extends Page
{
    use InteractsWithRecord;

    protected static string $resource = EventResource::class;

    protected static ?string $title = 'Manage Judge Assignments';

    protected string $view = 'filament.resources.events.pages.manage-judge-assignments';

    public array $assignments = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->loadAssignments();
    }

    protected function loadAssignments(): void
    {
        $this->assignments = [];

        foreach ($this->getAwards() as $award) {
            $this->assignments[$award->id] = $award->judges()->pluck('users.id')->toArray();
        }
    }

    public function getAwards(): Collection
    {
        return $this->record->awards()->orderBy('name')->get();
    }

    public function getJudges(): Collection
    {
        return $this->record->judges()->orderBy('name')->get();
    }

    public function toggleAssignment(int $awardId, int $judgeId): void
    {
        $award = Award::find($awardId);

        if (! $award || $award->event_id !== $this->record->id) {
            return;
        }

        if (in_array($judgeId, $this->assignments[$awardId] ?? [])) {
            // Remove assignment
            $award->judges()->detach($judgeId);
            $this->assignments[$awardId] = array_values(array_diff($this->assignments[$awardId], [$judgeId]));
        } else {
            // Add assignment
            $award->judges()->attach($judgeId);
            $this->assignments[$awardId][] = $judgeId;
        }
    }

    public function assignAllToAward(int $awardId): void
    {
        $award = Award::find($awardId);

        if (! $award || $award->event_id !== $this->record->id) {
            return;
        }

        $judgeIds = $this->getJudges()->pluck('id')->toArray();
        $award->judges()->sync($judgeIds);
        $this->assignments[$awardId] = $judgeIds;

        Notification::make()
            ->title('All judges assigned')
            ->body("All judges have been assigned to {$award->name}.")
            ->success()
            ->send();
    }

    public function removeAllFromAward(int $awardId): void
    {
        $award = Award::find($awardId);

        if (! $award || $award->event_id !== $this->record->id) {
            return;
        }

        $award->judges()->detach();
        $this->assignments[$awardId] = [];

        Notification::make()
            ->title('All judges removed')
            ->body("All judges have been removed from {$award->name}.")
            ->success()
            ->send();
    }

    public function assignAllToJudge(int $judgeId): void
    {
        $awards = $this->getAwards();

        foreach ($awards as $award) {
            if (! in_array($judgeId, $this->assignments[$award->id] ?? [])) {
                $award->judges()->attach($judgeId);
                $this->assignments[$award->id][] = $judgeId;
            }
        }

        $judge = User::find($judgeId);

        Notification::make()
            ->title('Judge assigned to all awards')
            ->body("{$judge->name} has been assigned to all awards.")
            ->success()
            ->send();
    }

    public function removeAllFromJudge(int $judgeId): void
    {
        $awards = $this->getAwards();

        foreach ($awards as $award) {
            $award->judges()->detach($judgeId);
            $this->assignments[$award->id] = array_values(array_diff($this->assignments[$award->id] ?? [], [$judgeId]));
        }

        $judge = User::find($judgeId);

        Notification::make()
            ->title('Judge removed from all awards')
            ->body("{$judge->name} has been removed from all awards.")
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            EventResource::getUrl() => 'Events',
            EventResource::getUrl('edit', ['record' => $this->record]) => $this->record->name,
            'Judge Assignments',
        ];
    }
}

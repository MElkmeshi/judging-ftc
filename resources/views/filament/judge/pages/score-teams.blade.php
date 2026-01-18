<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        {{-- Award Selection Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Select Award
            </x-slot>

            <form wire:submit.prevent="submit">
                {{ $this->form }}
            </form>
        </x-filament::section>

        {{-- Progress Bar (All Teams View) --}}
        @if($viewMode === 'all' && $award && !empty($allTeamsScores))
            <x-filament::section>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 600; margin: 0;" class="text-gray-950 dark:text-white">
                            Progress: {{ $progressStats['scored_teams'] }} of {{ $progressStats['total_teams'] }} teams scored
                        </h3>
                        <p style="font-size: 0.875rem; margin: 0.25rem 0 0 0;" class="text-gray-500 dark:text-gray-400">
                            {{ $progressStats['draft_teams'] }} draft(s), {{ $progressStats['pending_teams'] }} pending
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 1.5rem; font-weight: 700; color: rgb(var(--primary-600));">
                            {{ $progressStats['total_teams'] > 0 ? round(($progressStats['scored_teams'] / $progressStats['total_teams']) * 100) : 0 }}%
                        </span>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div style="width: 100%; background: var(--fi-color-gray-200); border-radius: 9999px; height: 0.75rem; overflow: hidden;">
                    <div style="background: rgb(var(--primary-600)); height: 100%; border-radius: 9999px; transition: width 0.3s; width: {{ $progressStats['total_teams'] > 0 ? ($progressStats['scored_teams'] / $progressStats['total_teams']) * 100 : 0 }}%;"></div>
                </div>

                {{-- Legend --}}
                <div style="display: flex; gap: 1.5rem; margin-top: 0.75rem; font-size: 0.75rem;">
                    <span style="display: flex; align-items: center; gap: 0.375rem;">
                        <span style="width: 0.75rem; height: 0.75rem; background: rgb(var(--success-500)); border-radius: 9999px; display: inline-block;"></span>
                        Submitted
                    </span>
                    <span style="display: flex; align-items: center; gap: 0.375rem;">
                        <span style="width: 0.75rem; height: 0.75rem; background: rgb(var(--warning-500)); border-radius: 9999px; display: inline-block;"></span>
                        Draft
                    </span>
                    <span style="display: flex; align-items: center; gap: 0.375rem;">
                        <span style="width: 0.75rem; height: 0.75rem; background: var(--fi-color-gray-400); border-radius: 9999px; display: inline-block;"></span>
                        Pending
                    </span>
                </div>
            </x-filament::section>
        @endif

        {{-- Table View for Scoring --}}
        @if($viewMode === 'all' && $award && !empty($allTeamsScores))
            @php
                $criteria = collect($allTeamsScores)->first()['scores'] ?? [];
            @endphp

            <x-filament::section>
                <x-slot name="heading">
                    {{ $award->name }} - Score All Teams
                </x-slot>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--fi-color-gray-200);">
                                <th style="text-align: left; padding: 0.75rem 0.5rem; font-weight: 600; white-space: nowrap; position: sticky; left: 0; background: var(--fi-color-gray-50); z-index: 10;" class="dark:bg-gray-800 text-gray-950 dark:text-white">
                                    Team
                                </th>
                                @foreach($criteria as $criterion)
                                    <th style="text-align: center; padding: 0.75rem 0.5rem; font-weight: 600; min-width: 120px;" class="text-gray-950 dark:text-white">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                                            <span style="font-size: 0.75rem; line-height: 1.2;">{{ $criterion['criterion_name'] }}</span>
                                            <span style="font-size: 0.625rem; color: var(--fi-color-gray-500);">({{ number_format($criterion['weight'], 0) }}%)</span>
                                        </div>
                                    </th>
                                @endforeach
                                <th style="text-align: center; padding: 0.75rem 0.5rem; font-weight: 600; white-space: nowrap;" class="text-gray-950 dark:text-white">
                                    Status
                                </th>
                                <th style="text-align: center; padding: 0.75rem 0.5rem; font-weight: 600; white-space: nowrap;" class="text-gray-950 dark:text-white">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allTeamsScores as $teamId => $teamData)
                                @php
                                    $status = $teamData['status'];
                                    $statusColor = match($status) {
                                        'submitted' => 'success',
                                        'draft' => 'warning',
                                        default => 'gray'
                                    };
                                    $statusLabel = match($status) {
                                        'submitted' => 'Submitted',
                                        'draft' => 'Draft',
                                        default => 'Pending'
                                    };
                                    $rowBg = match($status) {
                                        'submitted' => 'background: rgba(var(--success-50), 0.5);',
                                        'draft' => 'background: rgba(var(--warning-50), 0.3);',
                                        default => ''
                                    };
                                @endphp
                                <tr style="border-bottom: 1px solid var(--fi-color-gray-200); {{ $rowBg }}" class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    {{-- Team Info --}}
                                    <td style="padding: 0.75rem 0.5rem; white-space: nowrap; position: sticky; left: 0; background: inherit; z-index: 5;">
                                        <div style="display: flex; flex-direction: column;">
                                            <span style="font-weight: 600;" class="text-gray-950 dark:text-white">
                                                #{{ $teamData['team_number'] }}
                                            </span>
                                            <span style="font-size: 0.75rem; max-width: 150px; overflow: hidden; text-overflow: ellipsis;" class="text-gray-500 dark:text-gray-400" title="{{ $teamData['team_name'] }}">
                                                {{ Str::limit($teamData['team_name'], 20) }}
                                            </span>
                                            @if($teamData['is_rookie'])
                                                <x-filament::badge color="info" size="sm" style="margin-top: 0.25rem; width: fit-content;">
                                                    Rookie
                                                </x-filament::badge>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Score Inputs for Each Criterion --}}
                                    @foreach($teamData['scores'] as $index => $scoreData)
                                        <td style="padding: 0.5rem; text-align: center;">
                                            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                                                @if($scoreData['is_submitted'])
                                                    <span style="font-weight: 600; font-size: 1rem;" class="text-success-600 dark:text-success-400">
                                                        {{ $scoreData['score'] ?? '-' }}
                                                    </span>
                                                    <span style="font-size: 0.625rem;" class="text-gray-400">/ {{ $scoreData['max_score'] }}</span>
                                                @else
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max="{{ $scoreData['max_score'] }}"
                                                        step="0.01"
                                                        wire:model.blur="allTeamsScores.{{ $teamId }}.scores.{{ $index }}.score"
                                                        style="width: 70px; text-align: center; padding: 0.375rem; border: 1px solid var(--fi-color-gray-300); border-radius: 0.375rem; font-size: 0.875rem; background: transparent;"
                                                        class="text-gray-950 dark:text-white dark:border-gray-600 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                        placeholder="0-{{ $scoreData['max_score'] }}"
                                                    />
                                                    <span style="font-size: 0.625rem;" class="text-gray-400">max {{ $scoreData['max_score'] }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach

                                    {{-- Status --}}
                                    <td style="padding: 0.75rem 0.5rem; text-align: center;">
                                        <x-filament::badge :color="$statusColor" size="sm">
                                            {{ $statusLabel }}
                                        </x-filament::badge>
                                    </td>

                                    {{-- Actions --}}
                                    <td style="padding: 0.75rem 0.5rem; text-align: center; white-space: nowrap;">
                                        @if($status !== 'submitted')
                                            <div style="display: flex; gap: 0.375rem; justify-content: center;">
                                                <x-filament::button
                                                    wire:click="saveTeamDraft({{ $teamId }})"
                                                    color="gray"
                                                    size="xs"
                                                >
                                                    Save
                                                </x-filament::button>
                                                <x-filament::button
                                                    wire:click="submitTeamScores({{ $teamId }})"
                                                    color="success"
                                                    size="xs"
                                                    wire:confirm="Submit scores for #{{ $teamData['team_number'] }}? This cannot be undone."
                                                >
                                                    Submit
                                                </x-filament::button>
                                            </div>
                                        @else
                                            <x-filament::icon icon="heroicon-o-lock-closed" class="h-5 w-5 text-gray-400 mx-auto" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Criteria Reference (collapsible) --}}
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--fi-color-gray-200);">
                    <details>
                        <summary style="cursor: pointer; font-weight: 600; font-size: 0.875rem; padding: 0.5rem 0;" class="text-gray-700 dark:text-gray-300">
                            View Criteria Descriptions
                        </summary>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; padding-top: 1rem;">
                            @foreach($criteria as $criterion)
                                <div style="padding: 0.75rem; background: var(--fi-color-gray-50); border-radius: 0.5rem;" class="dark:bg-white/5">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.25rem;">
                                        <h5 style="font-weight: 600; font-size: 0.875rem; margin: 0;" class="text-gray-950 dark:text-white">
                                            {{ $criterion['criterion_name'] }}
                                        </h5>
                                        <x-filament::badge size="sm" color="info">{{ number_format($criterion['weight'], 0) }}%</x-filament::badge>
                                    </div>
                                    @if($criterion['criterion_description'])
                                        <p style="font-size: 0.75rem; margin: 0; line-height: 1.4;" class="text-gray-500 dark:text-gray-400">
                                            {{ $criterion['criterion_description'] }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </details>
                </div>
            </x-filament::section>
        @endif

        {{-- Single Team View (Legacy) --}}
        @if($viewMode === 'single' && $team && $award && count($scores) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    Score Team #{{ $team->team_number }} - {{ $team->team_name }}
                </x-slot>

                <x-slot name="description">
                    Award: {{ $award->name }}
                </x-slot>

                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    @foreach($scores as $index => $scoreData)
                        <div style="padding: 1rem; background: var(--fi-color-gray-50); border-radius: 0.75rem;" class="dark:bg-white/5">
                            <div style="margin-bottom: 1rem;">
                                <h4 style="font-size: 1rem; font-weight: 600; margin: 0;" class="text-gray-950 dark:text-white">
                                    {{ $scoreData['criterion_name'] }}
                                </h4>
                                @if($scoreData['criterion_description'])
                                    <p style="font-size: 0.875rem; margin: 0.25rem 0 0 0;" class="text-gray-500 dark:text-gray-400">
                                        {{ $scoreData['criterion_description'] }}
                                    </p>
                                @endif
                                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                    <x-filament::badge>Max: {{ $scoreData['max_score'] }}</x-filament::badge>
                                    <x-filament::badge color="info">{{ $scoreData['weight'] }}%</x-filament::badge>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;" class="text-gray-950 dark:text-white">
                                        Score (0-{{ $scoreData['max_score'] }})
                                    </label>
                                    <x-filament::input.wrapper :disabled="$scoreData['is_submitted']">
                                        <x-filament::input
                                            type="number"
                                            min="0"
                                            max="{{ $scoreData['max_score'] }}"
                                            step="0.01"
                                            wire:model="scores.{{ $index }}.score"
                                            :disabled="$scoreData['is_submitted']"
                                        />
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;" class="text-gray-950 dark:text-white">
                                        Notes (Optional)
                                    </label>
                                    <x-filament::input.wrapper :disabled="$scoreData['is_submitted']">
                                        <textarea
                                            wire:model="scores.{{ $index }}.notes"
                                            @disabled($scoreData['is_submitted'])
                                            rows="2"
                                            style="width: 100%; resize: vertical; padding: 0.5rem; border: none; background: transparent;"
                                            class="text-gray-950 dark:text-white"
                                        ></textarea>
                                    </x-filament::input.wrapper>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Empty State --}}
        @if($award && empty($allTeamsScores) && $viewMode === 'all')
            <x-filament::section>
                <div style="text-align: center; padding: 3rem 0;">
                    <x-filament::icon
                        icon="heroicon-o-users"
                        class="h-12 w-12 text-gray-400 dark:text-gray-500"
                        style="margin: 0 auto 1rem auto;"
                    />
                    <p style="font-size: 1.125rem; font-weight: 500; margin: 0 0 0.5rem 0;" class="text-gray-500 dark:text-gray-400">No teams available</p>
                    <p style="font-size: 0.875rem; margin: 0;" class="text-gray-500 dark:text-gray-400">There are no active teams to score for this award.</p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

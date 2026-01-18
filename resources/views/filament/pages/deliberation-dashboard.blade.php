<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        {{-- Event and Award Selection Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Select Event and Award
            </x-slot>

            {{ $this->form }}
        </x-filament::section>

        {{-- Rankings Table --}}
        @if($award && count($rankings) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    AHP Rankings - {{ $award->name }}
                </x-slot>

                <x-slot name="description">
                    Rankings calculated using the Analytic Hierarchy Process (AHP). These are suggestions - you have final decision authority.
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="fi-ta-table w-full divide-y divide-gray-200 text-start dark:divide-white/5" style="min-width: 900px;">
                        <thead class="divide-y divide-gray-200 dark:divide-white/5">
                            <tr class="bg-gray-50 dark:bg-white/5">
                                <th class="fi-ta-header-cell px-4 py-4 text-start text-sm font-semibold text-gray-950 dark:text-white" style="width: 80px;">Rank</th>
                                <th class="fi-ta-header-cell px-4 py-4 text-start text-sm font-semibold text-gray-950 dark:text-white" style="min-width: 180px;">Team</th>
                                <th class="fi-ta-header-cell px-4 py-4 text-center text-sm font-semibold text-gray-950 dark:text-white" style="width: 140px;">AHP Score</th>
                                <th class="fi-ta-header-cell px-4 py-4 text-center text-sm font-semibold text-gray-950 dark:text-white" style="width: 100px;">Variance</th>
                                <th class="fi-ta-header-cell px-4 py-4 text-center text-sm font-semibold text-gray-950 dark:text-white" style="width: 140px;">Completion</th>
                                <th class="fi-ta-header-cell px-4 py-4 text-center text-sm font-semibold text-gray-950 dark:text-white" style="width: 140px;">Assignment</th>
                                <th class="fi-ta-header-cell px-4 py-4 text-end text-sm font-semibold text-gray-950 dark:text-white" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                            @foreach($rankings as $ranking)
                                <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="fi-ta-cell px-4 py-4">
                                        <x-filament::badge
                                            :color="match($ranking['rank']) {
                                                1 => 'warning',
                                                2 => 'gray',
                                                3 => 'danger',
                                                default => 'gray'
                                            }"
                                        >
                                            #{{ $ranking['rank'] }}
                                        </x-filament::badge>
                                    </td>

                                    <td class="fi-ta-cell px-4 py-4">
                                        <div class="font-medium text-gray-950 dark:text-white">
                                            #{{ $ranking['team_number'] }} - {{ $ranking['team_name'] }}
                                        </div>
                                        @if($ranking['is_rookie'] ?? false)
                                            <x-filament::badge color="info" size="sm" class="mt-1">
                                                Rookie
                                            </x-filament::badge>
                                        @endif
                                    </td>

                                    <td class="fi-ta-cell px-4 py-4 text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <span class="text-lg font-semibold text-gray-950 dark:text-white">
                                                {{ number_format($ranking['weighted_score'], 2) }}
                                            </span>
                                            <x-filament::button
                                                wire:click="viewScoreDetails({{ $ranking['team_id'] }})"
                                                size="xs"
                                                color="gray"
                                                outlined
                                            >
                                                View
                                            </x-filament::button>
                                        </div>
                                    </td>

                                    <td class="fi-ta-cell px-4 py-4 text-center">
                                        @php
                                            $varianceLevel = $ranking['variance_level'] ?? 'low';
                                            $varianceColor = match($varianceLevel) {
                                                'high' => 'danger',
                                                'medium' => 'warning',
                                                default => 'success'
                                            };
                                            $varianceLabel = match($varianceLevel) {
                                                'high' => 'High ⚠',
                                                'medium' => 'Medium',
                                                default => 'Low'
                                            };
                                        @endphp
                                        <x-filament::badge :color="$varianceColor" size="sm">
                                            {{ $varianceLabel }}
                                        </x-filament::badge>
                                    </td>

                                    <td class="fi-ta-cell px-4 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <div style="width: 80px;" class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $ranking['completion_percentage'] }}%"></div>
                                            </div>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ number_format($ranking['completion_percentage'], 0) }}%
                                            </span>
                                        </div>
                                    </td>

                                    <td class="fi-ta-cell px-4 py-4 text-center">
                                        @php
                                            $assignment = $award->awardAssignments->where('team_id', $ranking['team_id'])->first();
                                        @endphp
                                        @if($assignment)
                                            <x-filament::badge
                                                :color="match($assignment->level) {
                                                    1 => 'warning',
                                                    2 => 'gray',
                                                    default => 'danger'
                                                }"
                                            >
                                                {{ ['1st Place', '2nd Place', '3rd Place'][$assignment->level - 1] }}
                                            </x-filament::badge>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500 text-sm">Not assigned</span>
                                        @endif
                                    </td>

                                    <td class="fi-ta-cell px-4 py-4 text-end">
                                        @if(!$award->is_locked)
                                            <div class="flex justify-end gap-2">
                                                @foreach($availableLevels as $index => $level)
                                                    @php
                                                        $levelNum = $index + 1;
                                                        $color = match($levelNum) {
                                                            1 => 'warning',
                                                            2 => 'gray',
                                                            3 => 'danger',
                                                            default => 'gray'
                                                        };
                                                    @endphp
                                                    <x-filament::button
                                                        wire:click="assignAward({{ $ranking['team_id'] }}, {{ $levelNum }})"
                                                        size="xs"
                                                        :color="$color"
                                                    >
                                                        {{ $level }}
                                                    </x-filament::button>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Locked</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- FTC Rules Notice --}}
                @if(count($availableLevels) < 3 && $award->is_ranked)
                    <div class="mt-4 rounded-lg bg-info-50 p-4 dark:bg-info-400/10">
                        <div class="flex">
                            <x-filament::icon
                                icon="heroicon-o-information-circle"
                                class="h-5 w-5 text-info-400"
                            />
                            <div class="ml-3">
                                <p class="text-sm font-medium text-info-800 dark:text-info-400">
                                    @if(count($availableLevels) === 1)
                                        With {{ $event->activeTeamsCount() }} teams, only 1st place is available per FTC rules (≤10 teams).
                                    @else
                                        With {{ $event->activeTeamsCount() }} teams, only 1st and 2nd place are available per FTC rules (11-20 teams).
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($award->is_finalized)
                    <div class="mt-4 rounded-lg bg-success-50 p-4 dark:bg-success-400/10">
                        <div class="flex">
                            <x-filament::icon
                                icon="heroicon-o-check-circle"
                                class="h-5 w-5 text-success-400"
                            />
                            <div class="ml-3">
                                <p class="text-sm font-medium text-success-800 dark:text-success-400">
                                    This award has been finalized and is now official.
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif($award->is_locked)
                    <div class="mt-4 rounded-lg bg-warning-50 p-4 dark:bg-warning-400/10">
                        <div class="flex">
                            <x-filament::icon
                                icon="heroicon-o-lock-closed"
                                class="h-5 w-5 text-warning-400"
                            />
                            <div class="ml-3">
                                <p class="text-sm font-medium text-warning-800 dark:text-warning-400">
                                    This award is locked. Judges cannot score it.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </x-filament::section>
        @elseif($award)
            <x-filament::section>
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <x-filament::icon
                        icon="heroicon-o-clipboard-document-list"
                        class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4"
                    />
                    <p class="text-lg font-medium mb-2">No scores available yet</p>
                    <p class="text-sm">Teams will appear here once judges have submitted scores</p>
                </div>
            </x-filament::section>
        @endif
    </div>

    {{-- Score Details Modal --}}
    <x-filament::modal
        id="score-details-modal"
        :close-by-clicking-away="true"
        width="4xl"
    >
        @if(!empty($scoreDetails))
            <x-slot name="heading">
                Score Breakdown: #{{ $scoreDetails['team_number'] }} {{ $scoreDetails['team_name'] }}
            </x-slot>

            {{-- Final AHP Score --}}
            <div class="text-center p-4 bg-primary-50 dark:bg-primary-400/10 rounded-lg mb-6">
                <p class="text-sm text-primary-600 dark:text-primary-400">Final AHP Score</p>
                <p class="text-3xl font-bold text-primary-700 dark:text-primary-300">{{ $scoreDetails['final_ahp_score'] }}</p>
            </div>

            {{-- Criterion Breakdown --}}
            <div class="mb-6">
                <h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-3">Criterion Breakdown</h4>
                <div class="overflow-x-auto">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.05);">
                                <th style="padding: 8px 12px; text-align: left; font-weight: 500; font-size: 0.875rem;">Criterion</th>
                                <th style="padding: 8px 12px; text-align: center; font-weight: 500; font-size: 0.875rem; width: 80px;">Weight</th>
                                <th style="padding: 8px 12px; text-align: center; font-weight: 500; font-size: 0.875rem; width: 100px;">Avg Score</th>
                                <th style="padding: 8px 12px; text-align: center; font-weight: 500; font-size: 0.875rem; width: 100px;">Contribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scoreDetails['criteria_breakdown'] as $criterion)
                                <tr style="border-bottom: 1px solid rgba(128,128,128,0.2);">
                                    <td style="padding: 8px 12px; font-size: 0.875rem;">{{ $criterion['name'] }}</td>
                                    <td style="padding: 8px 12px; text-align: center; font-size: 0.875rem; color: #666;">{{ $criterion['weight'] }}%</td>
                                    <td style="padding: 8px 12px; text-align: center; font-size: 0.875rem;">
                                        @if($criterion['average_score'] !== null)
                                            <span>{{ $criterion['average_score'] }}</span>
                                            <span style="color: #888;"> / {{ $criterion['max_score'] }}</span>
                                        @else
                                            <span style="color: #888;">-</span>
                                        @endif
                                    </td>
                                    <td style="padding: 8px 12px; text-align: center; font-size: 0.875rem; font-weight: 500; color: #3b82f6;">
                                        +{{ $criterion['contribution'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Judge Scores --}}
            @if(!empty($scoreDetails['judge_scores']))
                <div>
                    <h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-3">Judge Scores</h4>
                    <div class="overflow-x-auto">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(0,0,0,0.05);">
                                    <th style="padding: 8px 12px; text-align: left; font-weight: 500; font-size: 0.875rem;">Judge</th>
                                    @foreach($scoreDetails['criteria'] as $criterion)
                                        <th style="padding: 8px 12px; text-align: center; font-weight: 500; font-size: 0.75rem; max-width: 120px;" title="{{ $criterion['name'] }}">
                                            {{ Str::limit($criterion['name'], 12) }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scoreDetails['judge_scores'] as $judgeScore)
                                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.2);">
                                        <td style="padding: 8px 12px; font-size: 0.875rem; font-weight: 500;">{{ $judgeScore['judge_name'] }}</td>
                                        @foreach($scoreDetails['criteria'] as $criterion)
                                            @php
                                                $score = $judgeScore['criteria_scores'][$criterion['id']] ?? null;
                                                $variance = $scoreDetails['variance_data'][$criterion['id']] ?? null;
                                                $isHighVariance = $variance && $variance['is_high'];
                                            @endphp
                                            <td style="padding: 8px 12px; text-align: center; font-size: 0.875rem; {{ $isHighVariance ? 'background: rgba(239,68,68,0.1);' : '' }}">
                                                {{ $score ?? '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Variance Note --}}
                    @php
                        $hasHighVariance = collect($scoreDetails['variance_data'])->contains('is_high', true);
                    @endphp
                    @if($hasHighVariance)
                        <div style="margin-top: 12px; padding: 12px; background: rgba(239,68,68,0.1); border-radius: 8px; display: flex; align-items: flex-start; gap: 8px;">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-4 h-4 text-danger-500" style="flex-shrink: 0; margin-top: 2px;" />
                            <p style="font-size: 0.75rem; color: #dc2626;">
                                Highlighted cells indicate high variance between judges (&gt;20% of max score). Consider reviewing these criteria.
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            <x-slot name="footerActions">
                <x-filament::button wire:click="closeScoreDetailsModal" color="gray">
                    Close
                </x-filament::button>
            </x-slot>
        @endif
    </x-filament::modal>
</x-filament-panels::page>

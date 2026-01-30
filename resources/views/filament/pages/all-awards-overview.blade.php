<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        {{-- Event Selection Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Select Event
            </x-slot>

            <form wire:submit.prevent="submit">
                {{ $this->form }}
            </form>
        </x-filament::section>

        @if($event && !empty($awardsData))
            {{-- Summary Stats --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                @php
                    $totalAwards = count($awardsData);
                    $finalizedAwards = collect($awardsData)->where('is_finalized', true)->count();
                    $lockedAwards = collect($awardsData)->where('is_locked', true)->count();
                    $assignedAwards = collect($awardsData)->filter(fn($a) => !empty($a['assignments']))->count();
                @endphp

                <x-filament::section>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700;" class="text-primary-600">{{ $totalAwards }}</div>
                        <div style="font-size: 0.875rem;" class="text-gray-500 dark:text-gray-400">Total Awards</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700;" class="text-success-600">{{ $assignedAwards }}</div>
                        <div style="font-size: 0.875rem;" class="text-gray-500 dark:text-gray-400">Awards Assigned</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700;" class="text-warning-600">{{ $lockedAwards }}</div>
                        <div style="font-size: 0.875rem;" class="text-gray-500 dark:text-gray-400">Awards Locked</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700;" class="text-info-600">{{ $finalizedAwards }}</div>
                        <div style="font-size: 0.875rem;" class="text-gray-500 dark:text-gray-400">Awards Finalized</div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Awards Table --}}
            <x-filament::section>
                <x-slot name="heading">
                    All Awards
                </x-slot>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--fi-color-gray-200);">
                                <th style="text-align: left; padding: 0.75rem 0.5rem; font-weight: 600;" class="text-gray-950 dark:text-white">
                                    Award
                                </th>
                                <th style="text-align: center; padding: 0.75rem 0.5rem; font-weight: 600;" class="text-gray-950 dark:text-white">
                                    Status
                                </th>
                                <th style="text-align: center; padding: 0.75rem 0.5rem; font-weight: 600;" class="text-gray-950 dark:text-white">
                                    Judges
                                </th>
                                <th style="text-align: center; padding: 0.75rem 0.5rem; font-weight: 600;" class="text-gray-950 dark:text-white">
                                    Completion
                                </th>
                                <th style="text-align: left; padding: 0.75rem 0.5rem; font-weight: 600;" class="text-gray-950 dark:text-white">
                                    Top Ranked
                                </th>
                                <th style="text-align: left; padding: 0.75rem 0.5rem; font-weight: 600;" class="text-gray-950 dark:text-white">
                                    Assigned Winners
                                </th>
                                <th style="text-align: center; padding: 0.75rem 0.5rem; font-weight: 600;" class="text-gray-950 dark:text-white">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($awardsData as $awardId => $awardData)
                                @php
                                    $statusColor = 'gray';
                                    $statusLabel = 'Open';
                                    if ($awardData['is_finalized']) {
                                        $statusColor = 'info';
                                        $statusLabel = 'Finalized';
                                    } elseif ($awardData['is_locked']) {
                                        $statusColor = 'warning';
                                        $statusLabel = 'Locked';
                                    }

                                    $completion = $awardData['scoring_stats']['completion_percentage'] ?? 0;
                                    $completionColor = $completion >= 100 ? 'success' : ($completion >= 50 ? 'warning' : 'danger');
                                @endphp
                                <tr style="border-bottom: 1px solid var(--fi-color-gray-200);" class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    {{-- Award Name --}}
                                    <td style="padding: 0.75rem 0.5rem;">
                                        <div>
                                            <span style="font-weight: 600;" class="text-gray-950 dark:text-white">
                                                {{ $awardData['name'] }}
                                            </span>
                                            @if($awardData['code'])
                                                <span style="font-size: 0.75rem; margin-left: 0.25rem;" class="text-gray-500 dark:text-gray-400">
                                                    ({{ $awardData['code'] }})
                                                </span>
                                            @endif
                                        </div>
                                        <div style="font-size: 0.75rem;" class="text-gray-500 dark:text-gray-400">
                                            {{ $awardData['total_teams'] }} teams
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td style="padding: 0.75rem 0.5rem; text-align: center;">
                                        <x-filament::badge :color="$statusColor">
                                            {{ $statusLabel }}
                                        </x-filament::badge>
                                    </td>

                                    {{-- Judges --}}
                                    <td style="padding: 0.75rem 0.5rem; text-align: center;">
                                        <span class="text-gray-950 dark:text-white">{{ $awardData['judges_count'] }}</span>
                                    </td>

                                    {{-- Completion --}}
                                    <td style="padding: 0.75rem 0.5rem; text-align: center;">
                                        <x-filament::badge :color="$completionColor">
                                            {{ number_format($completion, 0) }}%
                                        </x-filament::badge>
                                    </td>

                                    {{-- Top Ranked --}}
                                    <td style="padding: 0.75rem 0.5rem;">
                                        @if(!empty($awardData['top_teams']))
                                            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                                @foreach(array_slice($awardData['top_teams'], 0, 3) as $index => $team)
                                                    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem;">
                                                        <span style="width: 1.25rem; height: 1.25rem; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.625rem; background: {{ $index === 0 ? 'rgb(var(--warning-500))' : ($index === 1 ? 'rgb(var(--gray-400))' : 'rgb(191, 127, 63)') }}; color: white;">
                                                            {{ $index + 1 }}
                                                        </span>
                                                        <span class="text-gray-700 dark:text-gray-300">
                                                            #{{ $team['team_number'] }}
                                                        </span>
                                                        <span style="font-size: 0.625rem;" class="text-gray-500">
                                                            ({{ number_format($team['weighted_score'], 2) }})
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span style="font-size: 0.75rem;" class="text-gray-400">No scores yet</span>
                                        @endif
                                    </td>

                                    {{-- Assigned Winners --}}
                                    <td style="padding: 0.75rem 0.5rem;">
                                        @if(!empty($awardData['assignments']))
                                            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                                @foreach($awardData['available_levels'] as $index => $levelLabel)
                                                    @php
                                                        $level = $awardData['is_ranked'] ? $index + 1 : 0;
                                                        $assignment = $awardData['assignments'][$level] ?? null;
                                                    @endphp
                                                    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem;">
                                                        <x-filament::badge size="sm" :color="$assignment ? 'success' : 'gray'">
                                                            {{ $levelLabel }}
                                                        </x-filament::badge>
                                                        @if($assignment)
                                                            <span class="text-gray-700 dark:text-gray-300">
                                                                #{{ $assignment['team_number'] }} - {{ Str::limit($assignment['team_name'], 15) }}
                                                            </span>
                                                        @else
                                                            <span class="text-gray-400">Not assigned</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                                @foreach($awardData['available_levels'] as $levelLabel)
                                                    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem;">
                                                        <x-filament::badge size="sm" color="gray">
                                                            {{ $levelLabel }}
                                                        </x-filament::badge>
                                                        <span class="text-gray-400">Not assigned</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td style="padding: 0.75rem 0.5rem; text-align: center;">
                                        <a
                                            href="{{ route('filament.admin.pages.deliberation-dashboard') }}?event={{ $event->id }}&award={{ $awardId }}"
                                            style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.375rem 0.75rem; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; background: rgb(var(--primary-600)); color: white; text-decoration: none;"
                                            class="hover:bg-primary-500"
                                        >
                                            <x-filament::icon icon="heroicon-o-arrow-right" class="h-3 w-3" />
                                            Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @elseif($event)
            {{-- Empty State --}}
            <x-filament::section>
                <div style="text-align: center; padding: 3rem 0;">
                    <x-filament::icon
                        icon="heroicon-o-trophy"
                        class="h-12 w-12 text-gray-400 dark:text-gray-500"
                        style="margin: 0 auto 1rem auto;"
                    />
                    <p style="font-size: 1.125rem; font-weight: 500; margin: 0 0 0.5rem 0;" class="text-gray-500 dark:text-gray-400">No awards found</p>
                    <p style="font-size: 0.875rem; margin: 0;" class="text-gray-500 dark:text-gray-400">Initialize awards for this event first.</p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

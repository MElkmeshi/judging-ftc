<x-filament-panels::page>
    @php
        $awards = $this->getAwards();
        $judges = $this->getJudges();
    @endphp

    @if($judges->isEmpty())
        <x-filament::section>
            <div style="text-align: center; padding: 3rem 0;">
                <x-filament::icon
                    icon="heroicon-o-user-plus"
                    class="h-12 w-12 text-gray-400 dark:text-gray-500"
                    style="margin: 0 auto 1rem auto;"
                />
                <p style="font-size: 1.125rem; font-weight: 500; margin: 0 0 0.5rem 0;" class="text-gray-500 dark:text-gray-400">No judges assigned to this event</p>
                <p style="font-size: 0.875rem; margin: 0;" class="text-gray-500 dark:text-gray-400">
                    First, add judges to this event from the
                    <a href="{{ \App\Filament\Resources\Events\EventResource::getUrl('edit', ['record' => $record]) }}" class="text-primary-600 hover:underline">
                        Event Edit page
                    </a>.
                </p>
            </div>
        </x-filament::section>
    @elseif($awards->isEmpty())
        <x-filament::section>
            <div style="text-align: center; padding: 3rem 0;">
                <x-filament::icon
                    icon="heroicon-o-trophy"
                    class="h-12 w-12 text-gray-400 dark:text-gray-500"
                    style="margin: 0 auto 1rem auto;"
                />
                <p style="font-size: 1.125rem; font-weight: 500; margin: 0 0 0.5rem 0;" class="text-gray-500 dark:text-gray-400">No awards for this event</p>
                <p style="font-size: 0.875rem; margin: 0;" class="text-gray-500 dark:text-gray-400">
                    Initialize awards from the
                    <a href="{{ \App\Filament\Resources\Events\EventResource::getUrl('edit', ['record' => $record]) }}" class="text-primary-600 hover:underline">
                        Event Edit page
                    </a>.
                </p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">
                Judge Assignment Matrix
            </x-slot>

            <x-slot name="description">
                Click on a cell to toggle the assignment. Use the row/column buttons to assign or remove all at once.
            </x-slot>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--fi-color-gray-200);">
                            <th style="text-align: left; padding: 0.75rem 0.5rem; font-weight: 600; position: sticky; left: 0; background: var(--fi-color-gray-50); z-index: 20; min-width: 200px;" class="dark:bg-gray-800 text-gray-950 dark:text-white">
                                Award / Judge
                            </th>
                            @foreach($judges as $judge)
                                <th style="text-align: center; padding: 0.75rem 0.5rem; font-weight: 600; min-width: 120px;" class="text-gray-950 dark:text-white">
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                        <span style="font-size: 0.75rem; line-height: 1.2;">{{ $judge->name }}</span>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <button
                                                type="button"
                                                wire:click="assignAllToJudge({{ $judge->id }})"
                                                title="Assign to all awards"
                                                style="padding: 0.25rem; border-radius: 0.25rem; border: 1px solid var(--fi-color-gray-300); background: transparent; cursor: pointer;"
                                                class="hover:bg-success-50 hover:border-success-500 dark:hover:bg-success-900/20"
                                            >
                                                <x-filament::icon icon="heroicon-o-check" class="h-3 w-3 text-gray-500 hover:text-success-600" />
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="removeAllFromJudge({{ $judge->id }})"
                                                title="Remove from all awards"
                                                style="padding: 0.25rem; border-radius: 0.25rem; border: 1px solid var(--fi-color-gray-300); background: transparent; cursor: pointer;"
                                                class="hover:bg-danger-50 hover:border-danger-500 dark:hover:bg-danger-900/20"
                                            >
                                                <x-filament::icon icon="heroicon-o-x-mark" class="h-3 w-3 text-gray-500 hover:text-danger-600" />
                                            </button>
                                        </div>
                                    </div>
                                </th>
                            @endforeach
                            <th style="text-align: center; padding: 0.75rem 0.5rem; font-weight: 600; min-width: 80px;" class="text-gray-950 dark:text-white">
                                Count
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($awards as $award)
                            @php
                                $assignedCount = count($assignments[$award->id] ?? []);
                            @endphp
                            <tr style="border-bottom: 1px solid var(--fi-color-gray-200);" class="hover:bg-gray-50 dark:hover:bg-white/5">
                                {{-- Award Name --}}
                                <td style="padding: 0.75rem 0.5rem; position: sticky; left: 0; background: inherit; z-index: 10;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                        <div>
                                            <span style="font-weight: 600;" class="text-gray-950 dark:text-white">
                                                {{ $award->name }}
                                            </span>
                                            @if($award->code)
                                                <span style="font-size: 0.75rem; margin-left: 0.25rem;" class="text-gray-500 dark:text-gray-400">
                                                    ({{ $award->code }})
                                                </span>
                                            @endif
                                        </div>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <button
                                                type="button"
                                                wire:click="assignAllToAward({{ $award->id }})"
                                                title="Assign all judges"
                                                style="padding: 0.25rem; border-radius: 0.25rem; border: 1px solid var(--fi-color-gray-300); background: transparent; cursor: pointer;"
                                                class="hover:bg-success-50 hover:border-success-500 dark:hover:bg-success-900/20"
                                            >
                                                <x-filament::icon icon="heroicon-o-check" class="h-3 w-3 text-gray-500 hover:text-success-600" />
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="removeAllFromAward({{ $award->id }})"
                                                title="Remove all judges"
                                                style="padding: 0.25rem; border-radius: 0.25rem; border: 1px solid var(--fi-color-gray-300); background: transparent; cursor: pointer;"
                                                class="hover:bg-danger-50 hover:border-danger-500 dark:hover:bg-danger-900/20"
                                            >
                                                <x-filament::icon icon="heroicon-o-x-mark" class="h-3 w-3 text-gray-500 hover:text-danger-600" />
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                {{-- Checkboxes for Each Judge --}}
                                @foreach($judges as $judge)
                                    @php
                                        $isAssigned = in_array($judge->id, $assignments[$award->id] ?? []);
                                    @endphp
                                    <td style="padding: 0.5rem; text-align: center;">
                                        <button
                                            type="button"
                                            wire:click="toggleAssignment({{ $award->id }}, {{ $judge->id }})"
                                            style="width: 2rem; height: 2rem; border-radius: 0.375rem; border: 2px solid {{ $isAssigned ? 'rgb(var(--success-500))' : 'var(--fi-color-gray-300)' }}; background: {{ $isAssigned ? 'rgb(var(--success-500))' : 'transparent' }}; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s;"
                                            class="hover:border-primary-500"
                                        >
                                            @if($isAssigned)
                                                <x-filament::icon icon="heroicon-o-check" class="h-4 w-4 text-white" />
                                            @endif
                                        </button>
                                    </td>
                                @endforeach

                                {{-- Count --}}
                                <td style="padding: 0.75rem 0.5rem; text-align: center;">
                                    <x-filament::badge :color="$assignedCount > 0 ? 'success' : 'gray'">
                                        {{ $assignedCount }} / {{ $judges->count() }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Summary --}}
            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--fi-color-gray-200);">
                <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Total Awards:</span>
                        <span style="font-weight: 600; margin-left: 0.25rem;" class="text-gray-950 dark:text-white">{{ $awards->count() }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Total Judges:</span>
                        <span style="font-weight: 600; margin-left: 0.25rem;" class="text-gray-950 dark:text-white">{{ $judges->count() }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Total Assignments:</span>
                        <span style="font-weight: 600; margin-left: 0.25rem;" class="text-gray-950 dark:text-white">
                            {{ collect($assignments)->flatten()->count() }}
                        </span>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>

<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Northern Regional Championship'),

                                TextInput::make('code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('e.g., NRC-2026')
                                    ->helperText('Unique identifier for this event'),
                            ]),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Brief description of the event...'),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('event_date')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('M d, Y'),

                                TextInput::make('location')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Springfield Arena, IL'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->required()
                                    ->options([
                                        'planning' => 'Planning',
                                        'registration' => 'Registration Open',
                                        'judging' => 'Judging Phase',
                                        'deliberation' => 'Deliberation',
                                        'completed' => 'Completed',
                                        'archived' => 'Archived',
                                    ])
                                    ->default('planning')
                                    ->native(false),

                                Toggle::make('is_active')
                                    ->default(true)
                                    ->helperText('Inactive events are hidden from judges'),
                            ]),
                    ]),
            ]);
    }
}

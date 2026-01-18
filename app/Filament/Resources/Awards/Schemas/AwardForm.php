<?php

namespace App\Filament\Resources\Awards\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AwardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Award Details')
                    ->description('Awards are typically created by initializing an event with templates')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('event_id')
                                    ->relationship('event', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Select the event for this award'),

                                Select::make('award_template_id')
                                    ->relationship('awardTemplate', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Optional: Link to FTC award template'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Inspire Award'),

                                TextInput::make('code')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., inspire')
                                    ->helperText('Unique identifier for this award'),
                            ]),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Brief description of the award criteria...'),
                    ]),

                Section::make('Award Configuration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_ranked')
                                    ->default(true)
                                    ->helperText('Ranked awards have 1st/2nd/3rd places'),

                                Toggle::make('is_hierarchical')
                                    ->default(false)
                                    ->helperText('Hierarchical awards (Inspire) have dynamic levels based on team count'),
                            ]),
                    ]),

                Section::make('Judging Status')
                    ->description('Control scoring and finalization')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_locked')
                                    ->default(false)
                                    ->helperText('Lock to prevent judges from scoring')
                                    ->live(),

                                Toggle::make('is_finalized')
                                    ->default(false)
                                    ->helperText('Finalize after winners are assigned')
                                    ->disabled(fn ($get) => ! $get('is_locked'))
                                    ->helperText('Must be locked before finalizing'),
                            ]),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Teams\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Team Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('events')
                                    ->relationship('events', 'name')
                                    ->multiple()
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Select the events this team is participating in')
                                    ->pivotData([
                                        'is_active' => true,
                                    ]),

                                TextInput::make('team_number')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(99999)
                                    ->placeholder('e.g., 12345')
                                    ->helperText('FTC team number'),
                            ]),

                        TextInput::make('team_name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Robo Warriors')
                            ->columnSpanFull(),
                    ]),

                Section::make('Organization Details')
                    ->schema([
                        TextInput::make('school_organization')
                            ->maxLength(255)
                            ->placeholder('e.g., Springfield High School')
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('city')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Springfield'),

                                TextInput::make('state_province')
                                    ->maxLength(255)
                                    ->placeholder('e.g., IL'),

                                TextInput::make('country')
                                    ->maxLength(255)
                                    ->default('USA')
                                    ->placeholder('e.g., USA'),
                            ]),
                    ]),

                Section::make('Team Status')
                    ->schema([
                        Toggle::make('is_rookie')
                            ->default(false)
                            ->helperText('First year competing in FTC'),
                    ]),
            ]);
    }
}

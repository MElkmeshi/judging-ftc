<?php

namespace App\Filament\Resources\AwardTemplates\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AwardTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Details')
                    ->description('Define the award template that can be applied to events')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Inspire Award'),

                                TextInput::make('code')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., inspire')
                                    ->helperText('Unique identifier for this template'),
                            ]),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Brief description of the award...'),

                        Textarea::make('judging_guidelines')
                            ->rows(5)
                            ->columnSpanFull()
                            ->placeholder('Guidelines for judges evaluating this award...'),
                    ]),

                Section::make('Award Configuration')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_ranked')
                                    ->default(true)
                                    ->helperText('Ranked awards have 1st/2nd/3rd places'),

                                Toggle::make('is_hierarchical')
                                    ->default(false)
                                    ->helperText('Hierarchical awards have dynamic levels'),

                                Toggle::make('is_active')
                                    ->default(true)
                                    ->helperText('Inactive templates won\'t appear when creating events'),
                            ]),

                        TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ]),
            ]);
    }
}

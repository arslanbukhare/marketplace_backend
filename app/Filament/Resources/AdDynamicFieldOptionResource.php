<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdDynamicFieldOptionResource\Pages;
use App\Filament\Resources\AdDynamicFieldOptionResource\RelationManagers;
use App\Models\AdDynamicFieldOption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class AdDynamicFieldOptionResource extends Resource
{
    protected static ?string $model = AdDynamicFieldOption::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('field_id')
                    ->relationship('field', 'field_name')
                    ->searchable()
                    ->label('Related Dynamic Field')
                    ->required(),

                TextInput::make('value')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('value')
                    ->label('Option Value')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('field.field_name')
                    ->label('Related Field')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('field.category.name')
                    ->label('Category')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdDynamicFieldOptions::route('/'),
            'create' => Pages\CreateAdDynamicFieldOption::route('/create'),
            'edit' => Pages\EditAdDynamicFieldOption::route('/{record}/edit'),
        ];
    }
}

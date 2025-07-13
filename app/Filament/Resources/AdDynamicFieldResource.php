<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdDynamicFieldResource\Pages;
use App\Filament\Resources\AdDynamicFieldResource\RelationManagers;
use App\Models\AdDynamicField;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class AdDynamicFieldResource extends Resource
{
    protected static ?string $model = AdDynamicField::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->required(),

                    TextInput::make('field_name')
                        ->label('Field Name')
                        ->required()
                        ->maxLength(255),

                    Select::make('field_type')
                        ->options([
                            'text' => 'Text',
                            'number' => 'Number',
                            'select' => 'Select',
                            'checkbox' => 'Checkbox',
                        ])
                        ->required(),

                    Toggle::make('is_required')
                        ->label('Is Required?'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('field_name')
                    ->label('Field Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('field_type')
                    ->label('Type')
                    ->sortable(),

                IconColumn::make('is_required')
                    ->label('Required?')
                    ->boolean(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
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
            'index' => Pages\ListAdDynamicFields::route('/'),
            'create' => Pages\CreateAdDynamicField::route('/create'),
            'edit' => Pages\EditAdDynamicField::route('/{record}/edit'),
        ];
    }
}

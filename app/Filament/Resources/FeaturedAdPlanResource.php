<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeaturedAdPlanResource\Pages;
use App\Filament\Resources\FeaturedAdPlanResource\RelationManagers;
use App\Models\FeaturedAdPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FeaturedAdPlanResource extends Resource
{
    protected static ?string $model = FeaturedAdPlan::class;
    

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Ads Management';
    protected static ?string $label = 'Featured Ad Plan';
    protected static ?string $pluralLabel = 'Featured Ad Plans';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric(),

                Forms\Components\Select::make('currency')
                    ->label('Currency')
                    ->required()
                    ->options([
                        'AED' => 'AED (د.إ)',
                        'USD' => 'USD ($)',
                        'EUR' => 'EUR (€)',
                        'PKR' => 'PKR (₨)',
                        'GBP' => 'GBP (£)',
                    ])
                    ->default('AED'),

                Forms\Components\TextInput::make('duration_days')
                    ->required()
                    ->numeric()
                    ->label('Duration (days)'),

                Forms\Components\TextInput::make('stripe_price_id')
                    ->label('Stripe Price ID')
                    ->maxLength(255)
                    ->placeholder('Optional'),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        $symbols = [
                            'AED' => 'د.إ',
                            'USD' => '$',
                            'EUR' => '€',
                            'PKR' => '₨',
                            'GBP' => '£',
                        ];
                        $symbol = $symbols[$record->currency] ?? $record->currency;
                        return $symbol . ' ' . number_format($record->price, 2);
                    }),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Currency')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Duration (days)')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stripe_price_id')
                    ->label('Stripe ID')
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->label('Created'),

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
            'index' => Pages\ListFeaturedAdPlans::route('/'),
            'create' => Pages\CreateFeaturedAdPlan::route('/create'),
            'edit' => Pages\EditFeaturedAdPlan::route('/{record}/edit'),
        ];
    }
}

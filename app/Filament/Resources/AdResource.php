<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdResource\Pages;
use App\Filament\Resources\AdResource\RelationManagers;
use App\Models\Ad;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use App\Models\DynamicField;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Fieldset;


class AdResource extends Resource
{
    protected static ?string $model = Ad::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Toggle::make('is_affiliate')
                ->label('Is Affiliate Ad')
                ->default(true)
                ->disabled()
                ->dehydrated(),

            TextInput::make('title')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->maxLength(1000)
                ->rows(4),

            TextInput::make('price')
                ->numeric()
                ->required(),

            TextInput::make('affiliate_url')
                ->url()
                ->required()
                ->visible(fn ($get) => $get('is_affiliate')),

            TextInput::make('affiliate_source')
                ->visible(fn ($get) => $get('is_affiliate')),

            Select::make('category_id')
                ->label('Category')
                ->relationship('category', 'name')
                ->required()
                ->reactive(),

            Select::make('subcategory_id')
                ->label('Subcategory')
                ->relationship('subcategory', 'name')
                ->searchable(),

            Fieldset::make('Custom Fields')
                ->visible(fn ($get) => $get('category_id'))
                ->schema(function (callable $get) {
                    $categoryId = $get('category_id');
                    if (! $categoryId) return [];

                    $fields = \App\Models\AdDynamicField::with('options')
                        ->where('category_id', $categoryId)
                        ->get();

                    return $fields->map(function ($field) {
                        $fieldName = "field_{$field->id}";

                        $component = match ($field->field_type) {
                            'select' => Select::make($fieldName)
                                ->label($field->field_name)
                                ->options($field->options->pluck('value', 'value')->toArray())
                                ->required($field->is_required),

                            'textarea' => Textarea::make($fieldName)
                                ->label($field->field_name)
                                ->required($field->is_required),

                            default => TextInput::make($fieldName)
                                ->label($field->field_name)
                                ->required($field->is_required),
                        };

                        return $component->afterStateHydrated(function ($component, $state, $record) use ($field) {
                            if (! $record) return;

                            $value = \App\Models\AdDynamicValue::where('ad_id', $record->id)
                                ->where('field_id', $field->id)
                                ->value('value');

                            if ($value !== null) {
                                $component->state($value);
                            }
                        });
                    })->toArray();
                })
                ->columns(1)
                ->reactive(),

            TextInput::make('city')
                ->required(),

            TextInput::make('address')
                ->visible(fn ($get) => !$get('is_affiliate')),

            TextInput::make('contact_number')
                ->tel()
                ->visible(fn ($get) => !$get('is_affiliate')),

            Toggle::make('show_contact_number')
                ->default(true)
                ->visible(fn ($get) => !$get('is_affiliate')),

            FileUpload::make('images')
                ->label('Ad Images')
                ->multiple()
                ->directory('ads/images')
                ->reorderable()
                ->preserveFilenames()
                ->maxSize(1024)
                ->dehydrated()
                ->afterStateHydrated(function ($component, $state, $record) {
                    if (! $record) return;
                    $component->state(
                        $record->images->pluck('image_path')->toArray()
                    );
                }),

            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->default('pending')
                ->required(),
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                   TextColumn::make('title')
                ->searchable()
                ->sortable()
                ->limit(40),

            TextColumn::make('category.name')
                ->sortable()
                ->label('Category'),

            TextColumn::make('subcategory.name')
                ->sortable()
                ->label('Subcategory'),

            TextColumn::make('user.name')
                ->sortable()
                ->label('Posted By'),

            ToggleColumn::make('status')
                ->label('Active?')
                ->sortable(),

            ImageColumn::make('images.0.image_path')
                ->label('Thumbnail')
                ->circular()
                ->size(40)
                ->defaultImageUrl(url('/default-ad-thumb.png')), // fallback if no image
        ])
        ->filters([
            SelectFilter::make('category_id')
                ->label('Category')
                ->relationship('category', 'name'),

            SelectFilter::make('subcategory_id')
                ->label('Subcategory')
                ->relationship('subcategory', 'name'),

            SelectFilter::make('status')
                ->label('Status')
                ->options([
                    1 => 'Active',
                    0 => 'Inactive',
                ]),
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
            ]) 
        ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAds::route('/'),
            'create' => Pages\CreateAd::route('/create'),
            'edit' => Pages\EditAd::route('/{record}/edit'),
        ];
    }
}

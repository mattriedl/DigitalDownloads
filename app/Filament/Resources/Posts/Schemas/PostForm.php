<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Support\SeoFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(2)->schema([
                Section::make()->columns(2)->schema([
                    TextInput::make('title')->required()->maxLength(255)->columnSpanFull()->rule(SeoFields::noYearsRule()),
                    TextInput::make('slug')->alphaDash()->maxLength(255)->unique(ignoreRecord: true)
                        ->prefix('/blog/')->helperText('Leave blank to create it from the title.')->columnSpanFull(),
                    Textarea::make('excerpt')->rows(2)->maxLength(300)->columnSpanFull(),
                    RichEditor::make('body')
                        ->helperText('Start with a table of contents and add internal links to related downloads.')
                        ->columnSpanFull(),
                ]),
                Section::make('FAQ')
                    ->description('Shown at the end of the article and marked up for Google rich results.')
                    ->collapsible()
                    ->schema([
                        Repeater::make('faq')->hiddenLabel()->addActionLabel('Add question')->defaultItems(0)
                            ->itemLabel(fn (array $state) => $state['question'] ?? null)
                            ->collapsible()
                            ->schema([
                                TextInput::make('question')->required()->maxLength(255),
                                Textarea::make('answer')->required()->rows(3),
                            ]),
                    ]),
                SeoFields::section(),
            ]),
            Grid::make(1)->columnSpan(1)->schema([
                Section::make('Publishing')->schema([
                    ToggleButtons::make('status')->inline()->required()->live()
                        ->options(['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled'])
                        ->colors(['draft' => 'gray', 'published' => 'success', 'scheduled' => 'warning'])
                        ->default('draft'),
                    DateTimePicker::make('published_at')->seconds(false)
                        ->required(fn (Get $get) => $get('status') !== 'draft')
                        ->default(now()),
                    Select::make('blog_category_id')->label('Category')->relationship('category', 'name')
                        ->searchable()->preload()
                        ->createOptionForm([TextInput::make('name')->required()]),
                    Select::make('author_id')->label('Author')->relationship('author', 'name', fn ($query) => $query->whereIn('role', ['admin', 'editor']))
                        ->default(fn () => auth()->id()),
                ]),
                Section::make('Featured image')->schema([
                    FileUpload::make('image')->hiddenLabel()->image()->imageEditor()->disk('public')->directory('blog')->maxSize(4096),
                    TextInput::make('image_credit')->placeholder('e.g. Photo: Jane Doe / Wikimedia Commons, CC BY-SA 4.0')->maxLength(255),
                ]),
            ]),
        ]);
    }
}

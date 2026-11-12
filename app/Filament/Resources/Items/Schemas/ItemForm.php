<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Filament\Support\SeoFields;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemLink;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->columns(2)->schema([
                        TextInput::make('title')->required()->maxLength(255)->columnSpanFull()
                            ->rule(SeoFields::noYearsRule()),
                        Select::make('category_id')
                            ->label('Category')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(fn () => self::categoryOptions()),
                        TextInput::make('slug')
                            ->helperText('Leave blank to create it from the title. Changing it later breaks old links unless you add a redirect.')
                            ->alphaDash()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Textarea::make('short_description')
                            ->rows(2)
                            ->maxLength(300)
                            ->helperText('One or two sentences for listings and search results.')
                            ->columnSpanFull(),
                        RichEditor::make('description')->columnSpanFull(),
                    ]),

                    Section::make('Download links')
                        ->description('Most items link out to the publisher or another download site. Visitors see the countdown page first, then go to the link.')
                        ->schema([
                            Repeater::make('links')
                                ->relationship()
                                ->orderColumn('sort_order')
                                ->hiddenLabel()
                                ->addActionLabel('Add another link')
                                ->defaultItems(1)
                                ->itemLabel(fn (array $state) => $state['label'] ?? 'Link')
                                ->collapsible()
                                ->columns(6)
                                ->schema([
                                    TextInput::make('label')->default('Download')->required()->maxLength(255)->columnSpan(2),
                                    Select::make('type')
                                        ->options([ItemLink::TYPE_EXTERNAL => 'External link', ItemLink::TYPE_HOSTED => 'File on this site'])
                                        ->default(ItemLink::TYPE_EXTERNAL)
                                        ->required()
                                        ->live()
                                        ->columnSpan(2),
                                    TextInput::make('file_size')->placeholder('24 MB')->maxLength(30)->columnSpan(1),
                                    Toggle::make('is_active')->label('Active')->default(true)->inline(false)->columnSpan(1),
                                    TextInput::make('url')
                                        ->label('URL')
                                        ->url()
                                        ->required(fn (Get $get) => $get('type') === ItemLink::TYPE_EXTERNAL)
                                        ->visible(fn (Get $get) => $get('type') === ItemLink::TYPE_EXTERNAL)
                                        ->maxLength(2000)
                                        ->columnSpanFull(),
                                    FileUpload::make('file_path')
                                        ->label('File')
                                        ->disk('local')
                                        ->directory('downloads')
                                        ->preserveFilenames()
                                        ->maxSize(512 * 1024)
                                        ->required(fn (Get $get) => $get('type') === ItemLink::TYPE_HOSTED)
                                        ->visible(fn (Get $get) => $get('type') === ItemLink::TYPE_HOSTED)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Section::make('Details')->columns(3)->collapsible()->schema([
                        TextInput::make('version')->maxLength(50),
                        TextInput::make('file_size')->placeholder('e.g. 24 MB')->maxLength(30),
                        TextInput::make('file_type')->placeholder('e.g. EXE, ZIP, PDF')->maxLength(50),
                        TextInput::make('license')->placeholder('e.g. Freeware, Open source')->maxLength(100),
                        TextInput::make('publisher')->maxLength(255),
                        TextInput::make('publisher_url')->url()->maxLength(255),
                        TextInput::make('platforms')->placeholder('e.g. Windows 10/11, macOS')->maxLength(255),
                        TextInput::make('requirements')->placeholder('e.g. 4 GB RAM')->maxLength(255)->columnSpan(2),
                        Textarea::make('changelog')->rows(3)->columnSpanFull(),
                    ]),

                    Section::make('Screenshots')->collapsible()->schema([
                        Repeater::make('screenshots')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->hiddenLabel()
                            ->addActionLabel('Add screenshot')
                            ->defaultItems(0)
                            ->grid(3)
                            ->schema([
                                FileUpload::make('path')->hiddenLabel()->image()->disk('public')->directory('screenshots')->maxSize(4096)->required(),
                                TextInput::make('caption')->placeholder('Caption (optional)')->hiddenLabel()->maxLength(255),
                            ]),
                    ]),

                    SeoFields::section(),
                ]),

                Grid::make(1)->columnSpan(1)->schema([
                    Section::make('Publishing')->schema([
                        ToggleButtons::make('status')
                            ->options([
                                Item::STATUS_DRAFT => 'Draft',
                                Item::STATUS_PUBLISHED => 'Published',
                                Item::STATUS_SCHEDULED => 'Scheduled',
                            ])
                            ->colors([Item::STATUS_DRAFT => 'gray', Item::STATUS_PUBLISHED => 'success', Item::STATUS_SCHEDULED => 'warning'])
                            ->default(Item::STATUS_DRAFT)
                            ->inline()
                            ->live()
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label(fn (Get $get) => $get('status') === Item::STATUS_SCHEDULED ? 'Publish on' : 'Published on')
                            ->required(fn (Get $get) => $get('status') === Item::STATUS_SCHEDULED)
                            ->helperText('Left blank, it is set to now when you publish.')
                            ->seconds(false),
                        Toggle::make('is_featured')->label('Featured on homepage'),
                        Toggle::make('is_trending')->label('Trending'),
                        Toggle::make('allow_comments')->label('Allow comments')->default(true),
                        Toggle::make('is_members_only')->label('Members only')
                            ->helperText('Only matters once memberships are switched on.'),
                        Toggle::make('noindex')->label('Hide from search engines'),
                    ]),

                    Section::make('Image')->schema([
                        FileUpload::make('thumbnail')
                            ->hiddenLabel()
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('items')
                            ->maxSize(4096),
                        TextInput::make('thumbnail_credit')->label('Image credit')->maxLength(255),
                    ]),

                    Section::make('Media and tags')->schema([
                        TextInput::make('video_url')->label('Video URL')->url()->placeholder('YouTube link')->maxLength(255),
                        TextInput::make('audio_url')->label('Audio preview URL')->url()->maxLength(255),
                        Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([TextInput::make('name')->required()->maxLength(100)]),
                    ]),

                    Section::make('Stats')
                        ->visibleOn('edit')
                        ->schema([
                            TextInput::make('download_count')->label('Downloads')->disabled()->dehydrated(false),
                            TextInput::make('view_count')->label('Views')->disabled()->dehydrated(false),
                            TextInput::make('rating_avg')->label('Rating')
                                ->formatStateUsing(fn ($state, ?Item $record) => $record ? "{$record->rating_avg} ({$record->rating_count} reviews)" : null)
                                ->disabled()->dehydrated(false),
                        ]),
                ]),
            ]);
    }

    /** Categories as "Parent › Child" so editors pick the right one. */
    public static function categoryOptions(): array
    {
        return Category::with('parent')->orderBy('name')->get()
            ->mapWithKeys(fn (Category $c) => [$c->id => $c->parent ? "{$c->parent->name} › {$c->name}" : $c->name])
            ->sort()
            ->all();
    }
}

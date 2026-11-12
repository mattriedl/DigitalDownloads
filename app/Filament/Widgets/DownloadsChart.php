<?php

namespace App\Filament\Widgets;

use App\Models\Download;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DownloadsChart extends ChartWidget
{
    protected ?string $heading = 'Downloads, last 30 days';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $start = today()->subDays(29);
        $counts = Download::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = collect(range(0, 29))->map(fn ($i) => $start->copy()->addDays($i));

        return [
            'datasets' => [[
                'label' => 'Downloads',
                'data' => $days->map(fn (Carbon $d) => (int) ($counts[$d->toDateString()] ?? 0))->all(),
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => $days->map(fn (Carbon $d) => $d->format('M j'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}

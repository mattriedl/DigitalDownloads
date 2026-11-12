<?php

namespace App\Filament\Resources\Ads\Widgets;

use App\Models\AdStat;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/** Daily ad impressions and clicks across all ads, shown above the Ads list. */
class AdPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Ad impressions and clicks, last 30 days';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '240px';

    protected function getData(): array
    {
        $start = today()->subDays(29);
        $rows = AdStat::query()
            ->where('date', '>=', $start->toDateString())
            ->selectRaw('date, SUM(impressions) as impressions, SUM(clicks) as clicks')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->getRawOriginal('date'))->toDateString());

        $days = collect(range(0, 29))->map(fn ($i) => $start->copy()->addDays($i));

        return [
            'datasets' => [
                [
                    'label' => 'Impressions',
                    'data' => $days->map(fn ($d) => (int) ($rows[$d->toDateString()]->impressions ?? 0))->all(),
                    'yAxisID' => 'y',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Clicks',
                    'data' => $days->map(fn ($d) => (int) ($rows[$d->toDateString()]->clicks ?? 0))->all(),
                    'yAxisID' => 'y1',
                    'tension' => 0.3,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $days->map(fn ($d) => $d->format('M j'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'position' => 'left', 'title' => ['display' => true, 'text' => 'Impressions'], 'ticks' => ['precision' => 0]],
                'y1' => ['beginAtZero' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false], 'title' => ['display' => true, 'text' => 'Clicks'], 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}

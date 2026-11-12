<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use App\Models\Download;
use App\Models\Item;
use App\Models\ItemLink;
use App\Models\Report;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $today = Download::where('created_at', '>=', today())->count();
        $last30 = Download::where('created_at', '>=', now()->subDays(30))->count();
        $prev30 = Download::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count();
        $trend = $prev30 > 0 ? round(($last30 - $prev30) / $prev30 * 100) : null;

        $daily = collect(range(13, 0))->map(fn ($d) => Download::whereDate('created_at', today()->subDays($d))->count())->all();

        $queue = Review::where('status', 'pending')->count() + Comment::where('status', 'pending')->count();
        $openReports = Report::where('status', 'open')->count();
        $openTickets = SupportTicket::whereIn('status', ['open', 'customer_reply'])->count();
        $broken = ItemLink::where('is_broken', true)->where('is_active', true)->count();

        return [
            Stat::make('Downloads today', number_format($today))
                ->description(number_format($last30).' in the last 30 days'.($trend !== null ? " ({$trend}%)" : ''))
                ->descriptionIcon($trend !== null && $trend < 0 ? Heroicon::ArrowTrendingDown : Heroicon::ArrowTrendingUp)
                ->color($trend !== null && $trend < 0 ? 'danger' : 'success')
                ->chart($daily),
            Stat::make('Published downloads', number_format(Item::published()->count()))
                ->description(Item::where('status', 'draft')->count().' drafts'),
            Stat::make('Users', number_format(User::count()))
                ->description(User::where('created_at', '>=', now()->subDays(30))->count().' joined in the last 30 days'),
            Stat::make('Needs attention', number_format($queue + $openReports + $openTickets + $broken))
                ->description("{$queue} to moderate · {$openReports} reports · {$openTickets} tickets · {$broken} broken links")
                ->color($queue + $openReports + $openTickets + $broken > 0 ? 'warning' : 'success'),
        ];
    }
}

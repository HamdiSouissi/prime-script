<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Call;
use App\Models\Stats;

/**
 * Tableau de bord administrateur : volumes, répartition, équipe, contenus.
 */
final class DashboardController extends AdminController
{
    public function index(): void
    {
        $counters = Stats::counters();
        $days = 14;

        $this->adminView('admin/dashboard', [
            'title'        => 'Tableau de bord',
            'counters'     => $counters,
            'results'      => Stats::byResult($days),
            'byUser'       => Stats::byUser($days),
            'byService'    => Stats::byService($days),
            'bySector'     => Stats::bySector($days),
            'week'         => Stats::lastSevenDays(),
            'content'      => Stats::contentCounters(),
            'blocksByType' => Stats::blocksByType(),
            'days'         => $days,
            'recentCalls'  => Call::search([], 8),
            'callbacks'    => Call::dueCallbacks(null, 5),
        ], 'dashboard');
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Http;
use App\Core\Sanitizer;
use App\Models\Call;
use App\Models\Sector;
use App\Models\Service;
use App\Models\User;

/**
 * Journal d'appels global (tous les commerciaux) avec export CSV.
 */
final class CallController extends AdminController
{
    private const PER_PAGE = 40;

    public function index(): void
    {
        $filters = $this->filters();
        $page = max(1, (int) Http::query('page', 1));
        $total = Call::countSearch($filters);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        $this->adminView('admin/calls/index', [
            'title'    => 'Journal d\'appels',
            'calls'    => Call::search($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'filters'  => $filters,
            'results'  => Call::results(),
            'users'    => User::options('name', false),
            'sectors'  => Sector::options('name', false),
            'services' => Service::options('name', false),
            'page'     => $page,
            'pages'    => $pages,
            'total'    => $total,
        ], 'calls');
    }

    /** Export CSV du journal complet (filtres appliqués). */
    public function export(): void
    {
        $calls = Call::search($this->filters(), 5000);
        $filename = 'journal-appels-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Nosniff: 1');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        if (fwrite($out, "\xEF\xBB\xBF") === false) {
            // Certains environnements tests n'autorisent pas l'écriture : on continue sans BOM.
        }
        fputcsv($out, ['Date', 'Heure', 'Commercial', 'Entreprise', 'Contact', 'Secteur', 'Étape', 'Service', 'Résultat', 'Note', 'Rappel'], ';', '"', chr(92));
        foreach ($calls as $call) {
            fputcsv($out, [
                date('d/m/Y', strtotime((string) $call['created_at'])),
                date('H:i', strtotime((string) $call['created_at'])),
                (string) $call['user_name'],
                (string) $call['entreprise_client'],
                (string) $call['contact'],
                (string) ($call['sector_name'] ?? ''),
                (string) ($call['stage_name'] ?? ''),
                (string) ($call['service_name'] ?? ''),
                Call::resultLabel((string) $call['result']),
                (string) $call['note'],
                $call['callback_date'] ? date('d/m/Y', strtotime((string) $call['callback_date'])) : '',
            ], ';', '"', chr(92));
        }
        fclose($out);
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        $userFilter = '';
        $userId = Sanitizer::id(Http::query('user_id', 0));
        if ($userId > 0) {
            $userFilter = (string) $userId;
        }

        return [
            'user_id'    => $userFilter,
            'result'     => $this->queryList('result', array_keys(Call::results()), ''),
            'service_id' => Sanitizer::id(Http::query('service_id', 0)) ?: '',
            'sector_id'  => Sanitizer::id(Http::query('sector_id', 0)) ?: '',
            'from'       => (string) Sanitizer::date(Http::query('from')) ?? '',
            'to'         => (string) Sanitizer::date(Http::query('to')) ?? '',
            'q'          => Http::text('q', 80),
        ];
    }
}

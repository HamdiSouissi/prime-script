<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Sanitizer;
use App\Models\Call;
use App\Models\Service;

/**
 * « Mes appels » : historique personnel du commercial, avec export CSV.
 */
final class HistoryController extends Controller
{
    private const PER_PAGE = 25;

    public function index(): void
    {
        $user = Auth::user();
        $filters = $this->filters(['user_id' => (int) $user['id']]);
        $page = max(1, (int) Http::query('page', 1));
        $total = Call::countSearch($filters);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        $calls = Call::search($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE);
        $stats = Call::statsForUser((int) $user['id']);
        $callbacks = Call::dueCallbacks((int) $user['id'], 6);

        $this->view('history/index', [
            'title'      => 'Mes appels',
            'calls'      => $calls,
            'filters'    => $filters,
            'results'    => Call::results(),
            'services'   => Service::active(),
            'stats'      => $stats,
            'callbacks'  => $callbacks,
            'page'       => $page,
            'pages'      => $pages,
            'total'      => $total,
            'perPage'    => self::PER_PAGE,
        ]);
    }

    /** Export CSV de mes appels (respecte les filtres). */
    public function export(): void
    {
        $user = Auth::user();
        $filters = $this->filters(['user_id' => (int) $user['id']]);
        $calls = Call::search($filters, 5000);

        $filename = 'mes-appels-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        // BOM UTF-8 pour Excel (accents corrects).
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Date', 'Heure', 'Entreprise', 'Contact', 'Secteur', 'Étape', 'Service', 'Résultat', 'Ne plus rappeler', 'Note', 'Rappel'], ';', '"', chr(92));
        foreach ($calls as $call) {
            fputcsv($out, [
                date('d/m/Y', strtotime((string) $call['created_at'])),
                date('H:i', strtotime((string) $call['created_at'])),
                (string) $call['entreprise_client'],
                (string) $call['contact'],
                (string) ($call['sector_name'] ?? ''),
                (string) ($call['stage_name'] ?? ''),
                (string) ($call['service_name'] ?? ''),
                Call::resultLabel((string) $call['result']),
                Call::isDoNotCall($call) ? 'Oui' : 'Non',
                (string) $call['note'],
                $call['callback_date'] ? date('d/m/Y', strtotime((string) $call['callback_date'])) : '',
            ], ';', '"', chr(92));
        }
        fclose($out);
        exit;
    }

    /**
     * Filtres depuis la query string.
     *
     * @param array<string, mixed> $base
     * @return array<string, mixed>
     */
    private function filters(array $base = []): array
    {
        return $base + [
            'result'     => $this->queryList('result', array_keys(Call::results()), ''),
            'service_id' => (string) Sanitizer::id(Http::query('service_id', 0)) ?: '',
            'from'       => (string) Sanitizer::date(Http::query('from')) ?? '',
            'to'         => (string) Sanitizer::date(Http::query('to')) ?? '',
            'q'          => (string) Http::text('q', 80),
        ];
    }
}

<?php
/**
 * « Mes appels » — historique personnel.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;
use App\Models\Call;

$calls = $calls ?? [];
$filters = $filters ?? [];
$results = $results ?? [];
$services = $services ?? [];
$stats = $stats ?? [];
$callbacks = $callbacks ?? [];
$page = (int) ($page ?? 1);
$pages = (int) ($pages ?? 1);
$total = (int) ($total ?? 0);
$queryBase = array_filter([
    'result'     => $filters['result'] ?? '',
    'service_id' => $filters['service_id'] ?? '',
    'from'       => $filters['from'] ?? '',
    'to'         => $filters['to'] ?? '',
    'q'          => $filters['q'] ?? '',
], static fn ($v) => $v !== '' && $v !== null);
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('clock', '', 14) ?> Suivi</span>
        <h1>Mes appels</h1>
        <p class="page-head__sub">Vos <?= $total ?> appel<?= $total > 1 ? 's' : '' ?> enregistré<?= $total > 1 ? 's' : '' ?> et vos rappels à venir.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/historique/export.csv' . ($queryBase ? '?' . http_build_query($queryBase) : ''))) ?>">
            <?= Icon::svg('download') ?> Exporter en CSV
        </a>
        <a class="btn" href="<?= Sanitizer::e(View::url('/')) ?>">
            <?= Icon::svg('phone') ?> Nouvel appel
        </a>
    </div>
</div>

<div class="stats mb-4">
    <div class="stat">
        <p class="stat__label">Aujourd'hui</p>
        <p class="stat__value"><?= (int) ($stats['today'] ?? 0) ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">7 jours</p>
        <p class="stat__value"><?= (int) ($stats['week'] ?? 0) ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">30 jours</p>
        <p class="stat__value"><?= (int) ($stats['month'] ?? 0) ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">RDV (30 j)</p>
        <p class="stat__value text-ok"><?= (int) ($stats['rdv_month'] ?? 0) ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Devis (30 j)</p>
        <p class="stat__value" style="color:var(--info)"><?= (int) ($stats['devis_month'] ?? 0) ?></p>
    </div>
</div>

<?php if ($callbacks !== []): ?>
    <div class="card mb-4">
        <div class="card__head">
            <div>
                <h2 class="card__title"><?= Icon::svg('bell', '', 18) ?> Rappels à traiter</h2>
                <p class="card__sub">Ces prospects attendent votre appel.</p>
            </div>
            <span class="badge badge--warn"><?= count($callbacks) ?></span>
        </div>
        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date prévue</th>
                        <th>Entreprise</th>
                        <th>Contact</th>
                        <th>Service</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($callbacks as $callback):
                        $isLate = $callback['callback_date'] < date('Y-m-d');
                        ?>
                        <tr>
                            <td class="is-nowrap">
                                <span class="badge badge--<?= $isLate ? 'err' : 'warn' ?>">
                                    <?= Sanitizer::e(View::dateFr((string) $callback['callback_date'])) ?>
                                </span>
                            </td>
                            <td><?= Sanitizer::e($callback['entreprise_client'] !== '' ? $callback['entreprise_client'] : '—') ?></td>
                            <td><?= Sanitizer::e($callback['contact'] !== '' ? $callback['contact'] : '—') ?></td>
                            <td><?= Sanitizer::e((string) ($callback['service_name'] ?? '—')) ?></td>
                            <td class="text-muted"><?= Sanitizer::e((string) $callback['note']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<form method="get" action="<?= Sanitizer::e(View::url('/historique')) ?>" class="filters" aria-label="Filtrer mes appels">
    <div class="field">
        <label for="f-result">Résultat</label>
        <select id="f-result" name="result">
            <option value="">Tous</option>
            <?php foreach ($results as $key => $label): ?>
                <option value="<?= Sanitizer::e($key) ?>" <?= ($filters['result'] ?? '') === $key ? 'selected' : '' ?>><?= Sanitizer::e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-service">Service</label>
        <select id="f-service" name="service_id">
            <option value="">Tous</option>
            <?php foreach ($services as $service): ?>
                <option value="<?= (int) $service['id'] ?>" <?= (string) ($filters['service_id'] ?? '') === (string) $service['id'] ? 'selected' : '' ?>>
                    <?= Sanitizer::e($service['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-from">Du</label>
        <input type="date" id="f-from" name="from" value="<?= Sanitizer::e($filters['from'] ?? '') ?>">
    </div>
    <div class="field">
        <label for="f-to">Au</label>
        <input type="date" id="f-to" name="to" value="<?= Sanitizer::e($filters['to'] ?? '') ?>">
    </div>
    <div class="field">
        <label for="f-q">Entreprise / note</label>
        <input type="search" id="f-q" name="q" value="<?= Sanitizer::e($filters['q'] ?? '') ?>" placeholder="Rechercher…">
    </div>
    <div class="filters__actions">
        <button type="submit" class="btn btn--sm"><?= Icon::svg('filter') ?> Filtrer</button>
        <?php if ($queryBase !== []): ?>
            <a class="btn btn--sm btn--ghost" href="<?= Sanitizer::e(View::url('/historique')) ?>">Réinitialiser</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($calls === []): ?>
    <div class="card empty">
        <span class="empty__icon"><?= Icon::svg('phone-out') ?></span>
        <h3>Aucun appel <?= $queryBase !== [] ? 'ne correspond à ces filtres' : 'pour le moment' ?></h3>
        <p><?= $queryBase !== [] ? 'Essayez d\'élargir la période ou de retirer un filtre.' : 'Votre premier appel apparaîtra ici dès qu\'il sera enregistré.' ?></p>
        <?php if ($queryBase !== []): ?>
            <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/historique')) ?>">Voir tous mes appels</a>
        <?php else: ?>
            <a class="btn" href="<?= Sanitizer::e(View::url('/')) ?>"><?= Icon::svg('phone') ?> Démarrer un appel</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="table">
                <caption class="visually-hidden">Historique de mes appels</caption>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Entreprise / contact</th>
                        <th>Secteur</th>
                        <th>Service</th>
                        <th>Résultat</th>
                        <th>Note</th>
                        <th>Rappel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calls as $call): ?>
                        <tr>
                            <td class="is-nowrap">
                                <strong style="display:block;font-size:var(--fs-sm)"><?= Sanitizer::e(View::dateFr((string) $call['created_at'])) ?></strong>
                                <span class="text-xs text-muted-2"><?= Sanitizer::e(date('H:i', strtotime((string) $call['created_at']))) ?></span>
                            </td>
                            <td>
                                <strong style="display:block"><?= Sanitizer::e($call['entreprise_client'] !== '' ? $call['entreprise_client'] : '—') ?></strong>
                                <span class="text-xs text-muted"><?= Sanitizer::e((string) $call['contact']) ?></span>
                            </td>
                            <td class="text-sm"><?= Sanitizer::e((string) ($call['sector_name'] ?? '—')) ?></td>
                            <td class="text-sm"><?= Sanitizer::e((string) ($call['service_name'] ?? '—')) ?></td>
                            <td class="is-nowrap">
                                <span class="badge badge--<?= Sanitizer::e(Call::resultTone((string) $call['result'])) ?>">
                                    <?= Sanitizer::e(Call::resultLabel((string) $call['result'])) ?>
                                </span>
                            </td>
                            <td class="text-sm text-muted" style="max-width:16rem"><?= Sanitizer::e((string) $call['note']) ?></td>
                            <td class="is-nowrap text-sm">
                                <?= $call['callback_date'] ? Sanitizer::e(View::dateFr((string) $call['callback_date'])) : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php if ($page > 1): ?>
                <a class="pagination__link" href="?<?= Sanitizer::e(http_build_query($queryBase + ['page' => $page - 1])) ?>" aria-label="Page précédente"><?= Icon::svg('chevron-left') ?></a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                <a class="pagination__link<?= $i === $page ? ' is-active' : '' ?>" href="?<?= Sanitizer::e(http_build_query($queryBase + ['page' => $i])) ?>"
                   <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
                <a class="pagination__link" href="?<?= Sanitizer::e(http_build_query($queryBase + ['page' => $page + 1])) ?>" aria-label="Page suivante"><?= Icon::svg('chevron-right') ?></a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

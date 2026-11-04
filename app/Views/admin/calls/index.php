<?php
/**
 * Administration — journal d'appels filtrable.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;
use App\Models\Call;

$calls = $calls ?? [];
$filters = $filters ?? [];
$results = $results ?? [];
$users = $users ?? [];
$sectors = $sectors ?? [];
$services = $services ?? [];
$page = (int) ($page ?? 1);
$pages = (int) ($pages ?? 1);
$total = (int) ($total ?? 0);
$queryBase = array_filter([
    'user_id'    => $filters['user_id'] ?? '',
    'result'     => $filters['result'] ?? '',
    'service_id' => $filters['service_id'] ?? '',
    'sector_id'  => $filters['sector_id'] ?? '',
    'from'       => $filters['from'] ?? '',
    'to'         => $filters['to'] ?? '',
    'q'          => $filters['q'] ?? '',
], static fn ($v) => $v !== '' && $v !== null);
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('history', '', 14) ?> Administration</span>
        <h1>Journal d'appels</h1>
        <p class="page-head__sub"><?= $total ?> appel<?= $total > 1 ? 's' : '' ?> correspondant<?= $total > 1 ? 'aux' : ' au' ?> filtre<?= $total > 1 ? 's' : '' ?>.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/admin/appels/export.csv' . ($queryBase ? '?' . http_build_query($queryBase) : ''))) ?>">
            <?= Icon::svg('download') ?> Exporter en CSV
        </a>
    </div>
</div>

<form method="get" action="<?= Sanitizer::e(View::url('/admin/appels')) ?>" class="filters" aria-label="Filtrer le journal d'appels">
    <div class="field">
        <label for="f-user">Commercial</label>
        <select id="f-user" name="user_id">
            <option value="">Tous</option>
            <?php foreach ($users as $id => $name): ?>
                <option value="<?= (int) $id ?>" <?= (string) ($filters['user_id'] ?? '') === (string) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
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
            <?php foreach ($services as $id => $name): ?>
                <option value="<?= (int) $id ?>" <?= (string) ($filters['service_id'] ?? '') === (string) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-sector">Secteur</label>
        <select id="f-sector" name="sector_id">
            <option value="">Tous</option>
            <?php foreach ($sectors as $id => $name): ?>
                <option value="<?= (int) $id ?>" <?= (string) ($filters['sector_id'] ?? '') === (string) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
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
        <label for="f-q">Recherche</label>
        <input type="search" id="f-q" name="q" value="<?= Sanitizer::e($filters['q'] ?? '') ?>" placeholder="Entreprise, contact, note…">
    </div>
    <div class="filters__actions">
        <button type="submit" class="btn btn--sm"><?= Icon::svg('filter') ?> Filtrer</button>
        <?php if ($queryBase !== []): ?>
            <a class="btn btn--sm btn--ghost" href="<?= Sanitizer::e(View::url('/admin/appels')) ?>">Réinitialiser</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($calls === []): ?>
    <div class="card empty">
        <span class="empty__icon"><?= Icon::svg('history') ?></span>
        <h3>Aucun appel trouvé</h3>
        <p>Modifiez la période ou retirez un filtre pour voir davantage de résultats.</p>
        <?php if ($queryBase !== []): ?>
            <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/admin/appels')) ?>">Tout afficher</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="table">
                <caption class="visually-hidden">Journal d'appels</caption>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Commercial</th>
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
                            <td class="text-sm is-nowrap"><?= Sanitizer::e((string) $call['user_name']) ?></td>
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
                                <?php if (Call::isDoNotCall($call)): ?>
                                    <span class="badge badge--danger" title="Le prospect a demandé à ne plus être rappelé">
                                        <?= Icon::svg('phone-off', '', 12) ?> Ne pas rappeler
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm text-muted" style="max-width:18rem"><?= Sanitizer::e((string) $call['note']) ?></td>
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

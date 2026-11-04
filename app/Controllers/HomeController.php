<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Icon;
use App\Models\Call;
use App\Models\Objection;
use App\Models\Sector;
use App\Models\Service;
use App\Models\Stage;
use App\Services\CallSession;
use App\Services\SpeechRenderer;

/**
 * Accueil = écran 1 du parcours : « Nouvel appel ».
 * Choix du secteur, de l'étape, champs facultatifs, puis bouton « Commencer ».
 */
final class HomeController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $sectors = Sector::active();
        $stages = Stage::active();
        $services = Service::active();
        $defaultStage = Stage::default();

        $stats = Call::statsForUser((int) $user['id']);
        $recent = Call::recentForUser((int) $user['id'], 4);

        // Ancienne saisie conservée en cas d'erreur de validation.
        $errors = $this->takeErrors();
        $old = $this->takeOld();

        // Objections disponibles dès l'écran 1 (panneau accessible partout).
        $objections = Objection::forContext(null, null);
        foreach ($objections as &$objection) {
            $objection['answer_html'] = SpeechRenderer::render((string) $objection['answer'], []);
        }
        unset($objection);

        $this->view('call/start', [
            'title'        => 'Nouvel appel',
            'sectors'      => $sectors,
            'stages'       => $stages,
            'services'     => $services,
            'defaultStage' => $defaultStage,
            'errors'       => $errors,
            'old'          => $old,
            'stats'        => $stats,
            'recent'       => $recent,
            'context'      => CallSession::isStarted() ? CallSession::context() : null,
            'icons'        => Icon::class,
            'objections'   => $objections,
        ]);
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Services\CallSession;
use App\Services\SpeechRenderer;

/**
 * Guide « Bien démarrer » : méthode d'appel, dialogue d'exemple, glossaire
 * et modèles de mail prêts à copier.
 *
 * Le contenu vit dans app/Content/Guide.php et app/Content/Templates.php :
 * aucun contenu n'est stocké en base, donc aucune migration nécessaire.
 */
final class GuideController extends Controller
{
    /** Page complète : /guide */
    public function index(): void
    {
        /** @var array<string, mixed> $guide */
        $guide = require BASE_PATH . '/app/Content/Guide.php';
        /** @var array<string, mixed> $templates */
        $templates = require BASE_PATH . '/app/Content/Templates.php';

        $context = CallSession::isStarted() ? CallSession::context() : [];
        $variables = $this->variables($context);

        $this->view('guide/index', [
            'title'     => (string) $guide['title'],
            'guide'     => $guide,
            'mailTemplates' => $this->renderTemplates($templates['templates'] ?? [], $variables),
            'variables' => $variables,
            'hasContext' => $context !== [],
        ]);
    }

    /** Modèles de mail seuls : /guide/modeles (utile depuis mobile, sans faire défiler) */
    public function templates(): void
    {
        /** @var array<string, mixed> $templates */
        $templates = require BASE_PATH . '/app/Content/Templates.php';
        $context = CallSession::isStarted() ? CallSession::context() : [];

        $this->view('guide/templates', [
            'title'     => 'Modèles de mail',
            'subtitle'  => (string) ($templates['subtitle'] ?? ''),
            'mailTemplates' => $this->renderTemplates($templates['templates'] ?? [], $this->variables($context)),
        ]);
    }

    /**
     * Variables du contexte d'appel en cours, complétées par des exemples lisibles
     * quand l'appel n'a pas encore commencé (sinon les mails seraient troués).
     *
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    private function variables(array $context): array
    {
        $fromCall = CallSession::variables($context, Auth::firstName());

        // Hors appel en cours, les modèles restent lisibles : on met des exemples.
        return [
            'contact'           => $fromCall['contact'] !== '' ? $fromCall['contact'] : 'Madame, Monsieur',
            'entreprise_client' => $fromCall['entreprise_client'] !== '' ? $fromCall['entreprise_client'] : 'votre entreprise',
            'prenom_commercial' => $fromCall['prenom_commercial'],
            'service'           => $fromCall['service'] !== '' ? $fromCall['service'] : 'votre projet web',
            'secteur'           => $fromCall['secteur'],
        ];
    }

    /**
     * Prépare les modèles : variables remplacées, texte prêt à copier.
     *
     * @param array<int, array<string, mixed>> $templates
     * @param array<string, string>            $variables
     * @return array<int, array<string, string>>
     */
    private function renderTemplates(array $templates, array $variables): array
    {
        $out = [];
        foreach ($templates as $template) {
            $subject = SpeechRenderer::replaceVariables((string) ($template['subject'] ?? ''), $variables);
            $body = SpeechRenderer::replaceVariables((string) ($template['body'] ?? ''), $variables);
            $out[] = [
                'key'     => (string) ($template['key'] ?? ''),
                'when'    => (string) ($template['when'] ?? ''),
                'subject' => trim(str_replace('<', '&lt;', $subject)),
                'body'    => trim($body),
            ];
        }
        return $out;
    }
}

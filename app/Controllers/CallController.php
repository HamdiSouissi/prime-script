<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Sanitizer;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Call;
use App\Models\Objection;
use App\Models\Sector;
use App\Models\Service;
use App\Models\SpeechBlock;
use App\Models\Stage;
use App\Services\CallSession;
use App\Services\SpeechRenderer;

/**
 * Parcours d'appel du commercial : contexte → présentation → service → speech → fin d'appel.
 * Le panneau Objections est disponible sur tous les écrans (fetch + repli sans JS).
 */
final class CallController extends Controller
{
    /** Écran 1 → démarre le contexte. */
    public function start(): void
    {
        Http::verifyCsrf();

        $sectorId = Http::id('sector_id');
        $stageId = Http::id('stage_id');
        $entreprise = Http::text('entreprise_client', 160);
        $contact = Http::text('contact', 120);

        $validator = new Validator([
            'sector_id'         => 'Le secteur d\'activité',
            'stage_id'          => 'L\'étape de suivi',
            'entreprise_client' => 'Le nom de l\'entreprise',
            'contact'           => 'Le nom du contact',
        ]);
        $validator->required('sector_id', $sectorId)
            ->required('stage_id', $stageId)
            ->maxLength('entreprise_client', $entreprise, 160)
            ->maxLength('contact', $contact, 120)
            ->check('sector_id', Sector::find($sectorId) !== null && (int) Sector::find($sectorId)['is_active'] === 1,
                'Ce secteur n\'est plus disponible. Choisissez-en un autre.')
            ->check('stage_id', Stage::find($stageId) !== null && (int) Stage::find($stageId)['is_active'] === 1,
                'Cette étape n\'est plus disponible. Choisissez-en une autre.');

        if ($validator->fails()) {
            $this->backWithErrors('/', $validator->errors(), [
                'sector_id'         => $sectorId,
                'stage_id'          => $stageId,
                'entreprise_client' => $entreprise,
                'contact'           => $contact,
            ]);
        }

        CallSession::start([
            'sector_id'         => $sectorId,
            'stage_id'          => $stageId,
            'entreprise_client' => $entreprise,
            'contact'           => $contact,
        ]);

        // Conformité : une opposition déjà enregistrée est rappelée avant de commencer.
        if (Call::hasOptOut($entreprise)) {
            Session::flash('warn', sprintf(
                '%s a demandé à ne plus être rappelé(e). N\'appelez pas ce prospect : l\'opposition figure dans le journal.',
                $entreprise
            ));
        }

        $this->redirect('/appel/presentation');
    }

    /** Écran 2 : présentation + accroches. */
    public function presentation(): void
    {
        $context = $this->requireContext();
        $variables = $this->variables($context);

        $blocks = SpeechBlock::forContext(
            ['presentation', 'accroche', 'barrage', 'repondeur'],
            (int) $context['sector_id'],
            (int) $context['stage_id'],
            null
        );

        $presentations = $this->prepare($blocks['presentation'] ?? [], $variables);
        $hooks = $this->prepare($blocks['accroche'] ?? [], $variables);
        $gatekeepers = $this->prepare($blocks['barrage'] ?? [], $variables);
        $voicemails = $this->prepare($blocks['repondeur'] ?? [], $variables);

        $this->view('call/presentation', [
            'title'         => 'Présentation & accroches',
            'context'       => $context,
            'presentations' => $presentations,
            'hooks'         => $hooks,
            'gatekeepers'   => $gatekeepers,
            'voicemails'    => $voicemails,
            'objections'    => $this->objectionsFor($context),
            'step'          => 2,
        ]);
    }

    /** Écran 3 : choix du service. */
    public function services(): void
    {
        $context = $this->requireContext();
        $services = Service::active();

        $this->view('call/services', [
            'title'      => 'Choisir un service',
            'context'    => $context,
            'services'   => $services,
            'objections' => $this->objectionsFor($context),
            'step'       => 3,
        ]);
    }

    /** Écran 4 : speech du service. */
    public function speech(): void
    {
        $context = $this->requireContext();
        $services = Service::active();

        $serviceId = Http::id('service');
        if ($serviceId > 0 && Service::existsActive($serviceId)) {
            CallSession::setService($serviceId);
            $context = CallSession::context();
        } elseif ((int) $context['service_id'] === 0) {
            Session::flash('warn', 'Choisissez d\'abord un service pour afficher son speech.');
            $this->redirect('/appel/services');
        }

        $serviceId = (int) $context['service_id'];
        $variables = $this->variables($context);

        $selection = SpeechBlock::forContext(
            SpeechBlock::CALL_TYPES,
            (int) $context['sector_id'],
            (int) $context['stage_id'],
            $serviceId,
            true
        );

        $sections = [];
        foreach (SpeechBlock::CALL_TYPES as $type) {
            $sections[$type] = $this->prepare($selection[$type] ?? [], $variables);
        }

        // Les réponses passent par le même rendu que les blocs : variables remplacées
        // ({contact}, {entreprise_client}…) et HTML assaini.
        $objections = $this->objectionsFor($context);

        $callService = Service::find($serviceId);

        $this->view('call/speech', [
            'title'      => $callService['name'] ?? 'Speech',
            'context'    => $context,
            'services'   => $services,
            'sections'   => $sections,
            'objections' => $objections,
            'step'       => 4,
        ]);
    }

    /**
     * Panneau Objections (AJAX JSON, avec repli page complète si JavaScript est désactivé).
     */
    public function objections(): void
    {
        $context = CallSession::context();
        $objections = $this->objectionsFor($context);

        if (Http::wantsJson()) {
            $html = App::render('call/partials/objections', [
                'objections' => $objections,
                'context'    => $context,
            ]);
            Http::json(['ok' => true, 'html' => $html, 'count' => count($objections)]);
        }

        $this->view('call/objections', [
            'title'      => 'Objections',
            'objections' => $objections,
            'context'    => $context,
            'step'       => 4,
        ]);
    }

    /** Écran 5 : formulaire de fin d'appel. */
    public function showEnd(): void
    {
        $context = $this->requireContext();
        if ((int) $context['service_id'] === 0) {
            Session::flash('warn', 'Choisissez un service avant de terminer l\'appel.');
            $this->redirect('/appel/services');
        }

        // Phrases de conclusion : une par résultat possible, pour ne pas chercher ses mots
        // au moment de raccrocher. Elles vivent dans les blocs « conclusion ».
        $variables = $this->variables($context);
        $conclusions = $this->prepare(SpeechBlock::byType('conclusion', true), $variables);
        $conclusions = $this->withResultCode($conclusions);

        $this->view('call/end', [
            'title'       => 'Fin d\'appel',
            'context'     => $context,
            'results'     => Call::results(),
            'services'    => Service::active(),
            'objections'  => $this->objectionsFor($context),
            'conclusions' => $conclusions,
            'step'        => 5,
            'errors'      => $this->takeErrors(),
            'old'         => $this->takeOld(),
        ]);
    }

    /** Enregistre l'appel (moins de 20 secondes à remplir). */
    public function end(): void
    {
        Http::verifyCsrf();
        $context = $this->requireContext();

        // On garde la valeur brute : c'est le validateur qui juge la longueur et le format,
        // sinon une note trop longue serait silencieusement tronquée au lieu d'être refusée.
        $result = Http::text('result', 30);
        $note = trim((string) Http::raw('note', ''));
        $callback = Http::raw('callback_date');
        $serviceId = Http::id('service_id') ?: (int) $context['service_id'];
        $note = str_replace("\0", '', mb_convert_encoding($note, 'UTF-8', 'UTF-8'));

        $validator = new Validator([
            'result'        => 'Le résultat de l\'appel',
            'note'          => 'La note',
            'callback_date' => 'La date de rappel',
            'service_id'    => 'Le service',
        ]);
        $validator->required('result', $result)
            ->inList('result', $result, Call::resultKeys())
            ->maxLength('note', $note, 600)
            ->optionalDate('callback_date', $callback)
            ->optionalId('service_id', $serviceId)
            ->check('service_id', $serviceId === 0 || Service::find($serviceId) !== null, 'Service inconnu.')
            ->check('callback_date', $result !== 'rappeler' || Sanitizer::date($callback) !== null,
                'Indiquez une date de rappel pour un appel « à rappeler ».');

        if ($validator->fails()) {
            Session::set('_errors', $validator->errors());
            Session::setOld(['result' => $result, 'note' => $note, 'callback_date' => (string) $callback, 'service_id' => $serviceId]);
            if (Http::wantsJson()) {
                Http::json(['ok' => false, 'message' => $validator->firstError()], 422);
            }
            $this->redirect('/appel/terminer');
        }

        // « Ne plus rappeler » : opposition enregistrée, aucun rappel proposé ensuite.
        $doNotCall = $result === 'ne_plus_rappeler' ? 1 : 0;

        $callId = Call::record([
            'user_id'           => (int) Auth::user()['id'],
            'sector_id'         => (int) $context['sector_id'],
            'stage_id'          => (int) $context['stage_id'],
            'service_id'        => $serviceId,
            'entreprise_client' => (string) $context['entreprise_client'],
            'contact'           => (string) $context['contact'],
            'result'            => $result,
            'note'              => $note,
            'callback_date'     => Sanitizer::date($callback),
            'do_not_call'       => $doNotCall,
        ]);

        CallSession::clear();

        $message = match ($result) {
            'rdv'           => 'Rendez-vous noté. Pensez à envoyer la confirmation au prospect.',
            'devis'         => 'Devis demandé : à préparer et à envoyer sous 48 h.',
            'rappeler'      => 'Rappel programmé. Il apparaîtra dans « Mes appels ».',
            'pas_interesse' => 'Appel enregistré. Aucun suivi nécessaire.',
            'ne_plus_rappeler' => 'Opposition enregistrée : cette entreprise ne sera plus rappelée.',
            default         => 'Appel enregistré.',
        };
        Session::flash('success', $message);

        if (Http::wantsJson()) {
            Http::json([
                'ok'       => true,
                'call_id'  => $callId,
                'message'  => $message,
                'redirect' => Http::url('/appel/enregistre?id=' . $callId),
            ], 201);
        }
        $this->redirect('/appel/enregistre?id=' . $callId);
    }

    /** Écran de confirmation après enregistrement (retour en 1 clic vers un nouvel appel). */
    public function saved(): void
    {
        $id = Http::id('id');
        $call = null;
        if ($id > 0) {
            $call = Call::find($id);
            // Un commercial ne consulte que ses propres appels.
            if ($call !== null && (int) $call['user_id'] !== (int) Auth::user()['id'] && !Auth::isAdmin()) {
                $call = null;
            }
        }

        $this->view('call/saved', [
            'title'  => 'Appel enregistré',
            'call'   => $call,
            'result' => $call ? Call::resultLabel((string) $call['result']) : 'Enregistré',
            'tone'   => $call ? Call::resultTone((string) $call['result']) : 'success',
        ]);
    }

    /** Réinitialise le contexte (bouton « Nouvel appel »). */
    public function reset(): void
    {
        Http::verifyCsrf();
        CallSession::clear();
        $this->redirect('/');
    }

    // ------------------------------------------------------------------ Helpers

    /**
     * Exige un contexte de départ ; sinon retour à l'écran 1.
     *
     * @return array<string, mixed>
     */
    private function requireContext(): array
    {
        if (!CallSession::isStarted()) {
            Session::flash('warn', 'Commencez par choisir le secteur et l\'étape du prospect.');
            $this->redirect('/');
        }
        return CallSession::context();
    }

    /**
     * Objections du contexte, rendues (utilisées par le volet latéral de chaque écran).
     *
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function objectionsFor(array $context): array
    {
        $variables = $this->variables($context);
        $objections = Objection::forContext((int) $context['sector_id'], (int) $context['service_id']);
        foreach ($objections as &$objection) {
            $objection['answer_html'] = SpeechRenderer::render((string) $objection['answer'], $variables);
        }
        unset($objection);
        return $objections;
    }

    /**
     * Variables de speech du contexte courant.
     *
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    private function variables(array $context): array
    {
        return CallSession::variables($context, Auth::firstName());
    }

    /**
     * Ajoute à chaque phrase de conclusion la clé du résultat qu'elle illustre.
     * Le rattachement se fait sur le titre (le bloc n'a pas de champ dédié) :
     * « Rendez-vous pris » → rdv, « Devis demandé » → devis, etc.
     *
     * @param array<int, array<string, mixed>> $blocks
     * @return array<int, array<string, mixed>>
     */
    private function withResultCode(array $blocks): array
    {
        // Le code enregistré fait foi (il est stable et sans accent).
        // Le repli sur le titre sert aux blocs saisis à la main dans l'admin :
        // on retire alors les accents et on teste le libellé le plus long d'abord
        // (« ne plus rappeler » avant « rappeler »).
        $map = [
            'ne plus rappeler' => 'ne_plus_rappeler',
            'rendez-vous'      => 'rdv',
            'devis'            => 'devis',
            'rappeler'         => 'rappeler',
            'pas interesse'    => 'pas_interesse',
            'injoignable'      => 'injoignable',
        ];
        foreach ($blocks as &$block) {
            $code = (string) ($block['code'] ?? '');
            if (str_starts_with($code, 'fin-')) {
                continue;
            }
            $title = str_replace(
                ['à', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'î', 'ï', 'ô', 'ö', 'ù', 'û', 'ü', 'ç'],
                ['a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u', 'u', 'c'],
                mb_strtolower((string) ($block['title'] ?? ''))
            );
            $block['code'] = '';
            foreach ($map as $needle => $key) {
                if (str_contains($title, $needle)) {
                    $block['code'] = 'fin-' . $key;
                    break;
                }
            }
        }
        unset($block);
        return $blocks;
    }

    /**
     * Prépare les blocs pour l'affichage : variables remplacées, HTML assaini, puces extraites.
     *
     * @param array<int, array<string, mixed>> $blocks
     * @param array<string, string>            $variables
     * @return array<int, array<string, mixed>>
     */
    private function prepare(array $blocks, array $variables): array
    {
        foreach ($blocks as &$block) {
            $rendered = SpeechRenderer::render((string) $block['content'], $variables);
            $block['html'] = $rendered;
            $block['bullets'] = SpeechRenderer::toBullets($rendered);
            $block['type_label'] = SpeechBlock::typeLabelFor((string) $block['type'], $block);
            // Bloc générique (sans secteur) : signalé dans les listes pour guider le choix.
            $block['_generic'] = ($block['sector_id'] ?? null) === null;
            $block['icon'] = SpeechBlock::TYPE_ICONS[(string) $block['type']] ?? 'info';
        }
        unset($block);
        return $blocks;
    }
}

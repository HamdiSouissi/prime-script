<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Models\Sector;
use App\Models\Service;
use App\Models\Stage;

/**
 * Contexte de l'appel en cours (stocké en session).
 *
 * Le commercial choisit un secteur, une étape, éventuellement un service :
 * ces choix sont conservés pendant tout l'appel, y compris après un changement de service.
 */
final class CallSession
{
    private const KEY = '_call';

    /**
     * Contexte courant.
     *
     * @return array{sector_id: int, stage_id: int, service_id: int, entreprise_client: string, contact: string, started_at: int}
     */
    public static function get(): array
    {
        $data = Session::get(self::KEY, []);
        if (!is_array($data) || !isset($data['sector_id'])) {
            return [
                'sector_id'         => 0,
                'stage_id'          => 0,
                'service_id'        => 0,
                'entreprise_client' => '',
                'contact'            => '',
                'started_at'        => 0,
            ];
        }
        return array_merge([
            'sector_id'         => 0,
            'stage_id'          => 0,
            'service_id'        => 0,
            'entreprise_client' => '',
            'contact'            => '',
            'started_at'        => 0,
        ], $data);
    }

    /** Vrai si un contexte a été démarré (secteur + étape). */
    public static function isStarted(): bool
    {
        $ctx = self::get();
        return $ctx['sector_id'] > 0 && $ctx['stage_id'] > 0;
    }

    /**
     * Démarre un nouvel appel.
     *
     * @param array<string, mixed> $data
     */
    public static function start(array $data): void
    {
        Session::set(self::KEY, [
            'sector_id'         => (int) $data['sector_id'],
            'stage_id'          => (int) $data['stage_id'],
            'service_id'        => (int) ($data['service_id'] ?? 0),
            'entreprise_client' => (string) ($data['entreprise_client'] ?? ''),
            'contact'            => (string) ($data['contact'] ?? ''),
            'started_at'        => time(),
        ]);
    }

    /** Change le service sans perdre le contexte (1 clic). */
    public static function setService(int $serviceId): void
    {
        $ctx = self::get();
        $ctx['service_id'] = $serviceId;
        Session::set(self::KEY, $ctx);
    }

    /** Réinitialise le contexte (après enregistrement d'un appel ou bouton « Nouvel appel »). */
    public static function clear(): void
    {
        Session::forget(self::KEY);
    }

    /**
     * Contexte enrichi avec les entités (secteur, étape, service) et les variables de speech.
     *
     * @return array<string, mixed>
     */
    public static function context(): array
    {
        $ctx = self::get();
        $sector = $ctx['sector_id'] > 0 ? Sector::find((int) $ctx['sector_id']) : null;
        $stage = $ctx['stage_id'] > 0 ? Stage::find((int) $ctx['stage_id']) : null;
        $service = $ctx['service_id'] > 0 ? Service::find((int) $ctx['service_id']) : null;

        return $ctx + [
            'sector'      => $sector,
            'stage'       => $stage,
            'service'     => $service,
            'sector_name' => $sector['name'] ?? '',
            'stage_name'  => $stage['name'] ?? '',
            'service_name'=> $service['name'] ?? '',
            // Utilisée par la phrase de conclusion « je vous rappelle {quand} ».
            'callback_label' => (string) ($ctx['callback_label'] ?? ''),
        ];
    }

    /**
     * Variables disponibles dans les speechs pour ce contexte.
     *
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    public static function variables(array $context, ?string $commercialFirstName = null): array
    {
        return SpeechRenderer::variablesFor([
            'entreprise_client' => (string) ($context['entreprise_client'] ?? ''),
            'contact'           => (string) ($context['contact'] ?? ''),
            'sector_name'       => (string) ($context['sector_name'] ?? ''),
            'commercial_name'   => (string) ($commercialFirstName ?? ''),
            'service_name'      => (string) ($context['service_name'] ?? ''),
            'quand'             => (string) ($context['callback_label'] ?? ''),
        ]);
    }
}

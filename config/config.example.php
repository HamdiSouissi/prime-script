<?php
/**
 * Configuration de Prime Script.
 *
 * Installation : copiez ce fichier en config/config.php puis adaptez les valeurs.
 *   cp config/config.example.php config/config.php
 *
 * ⚠️ Le fichier config.php ne doit JAMAIS être versionné ni exposé par le serveur web.
 */
return [
    // ---------------------------------------------------------------- Application
    'app' => [
        'name'        => 'Prime Script',
        'env'         => 'production',   // 'development' affiche les erreurs détaillées, 'production' non
        'debug'       => false,          // true = trace des exceptions dans storage/logs
        'timezone'    => 'Europe/Paris',
        'charset'     => 'UTF-8',
        // URL de base, sans slash final. Laissez vide pour une détection automatique (utile en mutualisé).
        'base_url'    => '',
    ],

    // ------------------------------------------------------------------- Base MySQL
    'database' => [
        'driver'   => 'mysql',            // mysql (production) | sqlite (démonstration locale, outils/tools uniquement)
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'prime_script',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
        // Chemin du fichier SQLite utilisé uniquement en mode driver=sqlite.
        'sqlite_path' => __DIR__ . '/../storage/prime-script.sample.sqlite',
        // Table prefix (laisser vide sauf mutualisé imposant un préfixe).
        'prefix'   => '',
    ],

    // ----------------------------------------------------------------- Sessions
    'session' => [
        'name'             => 'PRIMESCRIPT_SESSION',
        // Changez cette clé en production (chaîne aléatoire d'au moins 32 caractères).
        'key'              => 'changez-moi-avec-une-chaine-aleatoire-de-64-caracteres-minimum-0000',
        'lifetime'         => 60 * 60 * 8, // 8 heures
        'cookie_secure'    => 'auto',      // 'auto' = true si HTTPS, 'true' / 'false' pour forcer
        'cookie_samesite'  => 'Lax',       // Lax | Strict
    ],

    // ----------------------------------------------------------------- Connexion
    'security' => [
        // Nombre de tentatives de connexion échouées avant blocage temporaire.
        'login_max_attempts' => 5,
        // Durée du blocage, en minutes.
        'login_lock_minutes' => 15,
        // Longueur minimale des mots de passe (admin + réinitialisation).
        'password_min_length' => 10,
        // Durée de validité d'un jeton CSRF (heures).
        'csrf_ttl_hours'     => 4,
    ],

    // ------------------------------------------------------------------ Métier
    'calls' => [
        // Résultats possibles d'un appel (clé technique => libellé + style).
        'results' => [
            'rdv'        => ['label' => 'Rendez-vous pris',  'tone' => 'success'],
            'devis'      => ['label' => 'Devis demandé',     'tone' => 'info'],
            'rappeler'   => ['label' => 'À rappeler',        'tone' => 'warn'],
            'pas_interesse' => ['label' => 'Pas intéressé',  'tone' => 'muted'],
            'injoignable'=> ['label' => 'Injoignable',       'tone' => 'danger'],
            // Opposition au démarchage : le prospect a demandé à ne plus être rappelé.
            'ne_plus_rappeler' => ['label' => 'Ne plus rappeler', 'tone' => 'danger'],
        ],
    ],
];

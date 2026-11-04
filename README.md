# Prime Script

**L'application d'appel en direct de Prime Vision Solutions.**
Le bon argument, au bon moment, même quand on débute au téléphone.

> Agence digitale pour TPE / PME — [primevisionsolutions.fr](https://primevisionsolutions.fr)
> Application interne : **aucune inscription publique**, les comptes sont créés par un administrateur.

---

## 1. Ce que fait l'application

Un commercial choisit un **secteur**, une **étape** et (facultativement) le nom de l'entreprise,
puis suit un parcours en 5 écrans qui affiche le discours à lire à voix haute :

| # | Écran | Contenu |
|---|-------|---------|
| 1 | **Nouvel appel** | 4 secteurs, 3 étapes, entreprise / contact optionnels |
| 2 | **Présentation et accroches** | présentation adaptée + 2 à 4 accroches à lire |
| 3 | **Choix du service** | 4 grandes cartes avec nom, icône, description, tarif dès X € HT |
| 4 | **Speech du service** | blocs ordonnés : découverte, argumentaire, preuve, tarif, appel à l'action |
| 5 | **Fin d'appel** | résultat, note courte, date de rappel optionnelle — en moins de 20 s |

Pendant tout l'appel :

- le panneau **Objections** est accessible partout (bouton flottant ou `Échap`) :
  cliquez sur « C'est trop cher » → la réponse courte s'affiche ;
- la **barre d'étapes** reste visible, avec `Retour` et `Nouvel appel` ;
- un changement de service au dernier écran conserve le contexte (secteur, étape, entreprise).

Côté administration : contenus modulaires (CRUD, duplication, ordre, activation),
**aperçu** identique à ce que voit le commercial, **historique des versions avec restauration**,
gestion des objections, des secteurs / étapes / services, de l'équipe et **journal d'appels filtrable + export CSV**.

---

## 2. Prérequis

- **PHP 8.0 ou supérieur** (testé jusqu'à 8.5) avec les extensions `pdo_mysql`, `mbstring`, `json`
- **MySQL 5.7+** ou **MariaDB 10.3+** (jeu de caractères `utf8mb4`)
- **Apache 2.4** avec `mod_rewrite` et `mod_headers` (ou Nginx, voir § 8)
- Aucun Composer, aucun `node_modules`, aucun build : le CSS et le JS sont servis tels quels.

---

## 3. Installation pas à pas

### 3.1 Récupérer les fichiers

```bash
git clone <url-du-dépôt> prime-script
cd prime-script
```

### 3.2 Créer la base de données

```bash
mysql -u root -p
```

```sql
CREATE DATABASE prime_script CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'prime_script'@'localhost' IDENTIFIED BY 'un-mot-de-passe-solide';
GRANT ALL PRIVILEGES ON prime_script.* TO 'prime_script'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3.3 Importer le schéma puis les données de démonstration

```bash
mysql -u prime_script -p prime_script < database/schema.sql
mysql -u prime_script -p prime_script < database/seed.sql
```

`seed.sql` installe 3 comptes, 4 secteurs, 3 étapes, 4 services, 50 blocs de discours,
15 objections et 20 appels de démonstration (utiles pour visualiser le tableau de bord).

> En production, vous pouvez importer `schema.sql` seul, créer le premier administrateur
> avec le script SQL fourni en fin de `seed.sql`, puis supprimer les données de démonstration.

### 3.4 Configurer l'application

```bash
cp config/config.example.php config/config.php
```

Éditez `config/config.php` :

| Clé | À renseigner |
|-----|--------------|
| `database.host` / `port` / `name` / `user` / `password` | vos identifiants MySQL |
| `session.key` | chaîne aléatoire **d'au moins 64 caractères** (`php -r "echo bin2hex(random_bytes(32));"`) |
| `app.env` | `production` en ligne, `development` en local |
| `app.base_url` | `https://votre-domaine.fr` (vide = détection automatique) |

Le fichier `config/config.php` est **ignoré par Git** et interdit d'accès par `.htaccess`.

### 3.5 Pointer le serveur web sur `public/`

**Idéal** — le document root de l'hébergement pointe sur le dossier `public/`.
C'est la configuration la plus sûre : `app/`, `config/`, `database/` et `storage/` sont hors racine web.

Si vous ne pouvez pas changer le document root, le `.htaccess` de la racine redirige
automatiquement toutes les requêtes vers `public/` et bloque l'accès aux dossiers applicatifs.

Vérifiez que `mod_rewrite` est actif :

```bash
sudo a2enmod rewrite headers && sudo systemctl reload apache2
```

### 3.6 Vérifier les droits d'écriture

```bash
chmod -R 775 storage
chmod 664 config/config.php
```

`storage/logs/` reçoit les traces d'erreurs en production ; `storage/cache/` est réservé aux évolutions.

### 3.7 Se connecter

Ouvrez `https://votre-domaine.fr/login`.

**Comptes de démonstration (à changer immédiatement) :**

| Rôle | E-mail | Mot de passe |
|------|--------|--------------|
| Administrateur | `admin@primevisionsolutions.fr` | `AdminPrime2026!` |
| Commercial | `hamdi@primevisionsolutions.fr` | `Commercial2026!` |
| Commercial | `leila@primevisionsolutions.fr` | `Commercial2026!` |

> ⚠️ Avant la mise en service : connectez-vous en administrateur, ouvrez **Équipe**,
> puis réinitialisez chaque mot de passe (ou créez vos propres comptes et désactivez les comptes de démo).

### 3.8 Test de bonne installation

```bash
php -l public/index.php                  # vérifie la syntaxe
curl -I https://votre-domaine.fr/login   # doit répondre 200
```

Un script de fumée optionnel est fourni pour un environnement local. Il parcourt
41 vérifications : connexion, parcours complet d'un appel, objections en JSON,
enregistrement, historique, export CSV et contrôle d'accès administrateur.

```bash
BASE=http://localhost:8099 bash tools/smoke-test.sh
# si PHP n'est pas dans le PATH de la machine qui lance le test :
PHP_CLI=/usr/bin/php8.2 BASE=http://localhost:8099 bash tools/smoke-test.sh
```

Le script réinitialise d'abord les compteurs de tentatives de connexion
(`tools/clear-login-attempts.php`) pour ne pas buter sur le blocage de sécurité
après une série de mots de passe erronés. Cette commande est également utilisable seule :

```bash
php tools/clear-login-attempts.php
```

---

## 4. Arborescence

```
prime-script/
├── public/                     ← seule racine web exposée
│   ├── index.php               front controller (toutes les requêtes)
│   ├── .htaccess               réécriture + en-têtes de sécurité + cache
│   └── assets/
│       ├── css/tokens.css      variables de design (:root), thèmes clair/sombre
│       ├── css/app.css         composants et mises en page
│       ├── js/app.js           interactions (drawer, accordéons, confirmations, recherche)
│       ├── img/                logos Prime Vision + sprite SVG icons.svg
│       └── fonts/              Bricolage Grotesque + Public Sans (woff2, auto-hébergées)
├── app/
│   ├── bootstrap.php           chargement, configuration, erreurs, session, routes
│   ├── Core/                   noyau maison (aucune dépendance externe)
│   │   ├── App.php             erreurs, journalisation, en-têtes HTTP
│   │   ├── Auth.php            connexion, rôles, limitation des tentatives
│   │   ├── Config.php          lecture de config/config.php
│   │   ├── Db.php              PDO, requêtes préparées, dialecte MySQL/SQLite
│   │   ├── Http.php            entrées nettoyées, CSRF, redirections
│   │   ├── Icon.php            sprite SVG et jeux d'icônes
│   │   ├── Router.php          routes + garde de rôle
│   │   ├── Sanitizer.php       échappement, e-mail, dates, prix, rich text
│   │   ├── Session.php         session sécurisée, messages flash, CSRF
│   │   ├── Validator.php       validation serveur
│   │   └── View.php            rendu des vues, helpers url/e/date/prix/icônes
│   ├── Controllers/
│   │   ├── AuthController.php      connexion / déconnexion
│   │   ├── HomeController.php      écran 1 — Nouvel appel
│   │   ├── CallController.php      écrans 2 à 5 + objections
│   │   ├── HistoryController.php   mes appels + export CSV
│   │   ├── ProfileController.php   mon compte / mot de passe
│   │   └── Admin/                  tableau de bord, blocs, objections, taxonomies,
│   │                               utilisateurs, journal d'appels
│   ├── Models/                 une classe par table + Stats (agrégats SQL)
│   ├── Services/
│   │   ├── CallSession.php     contexte de l'appel en session
│   │   └── SpeechRenderer.php  variables {…}, puces, assainissement du rendu
│   └── Views/
│       ├── layouts/            app.php (connecté), auth.php (connexion)
│       ├── partials/           stepper, call-context, objections-drawer, admin-subnav
│       ├── call/               start, presentation, services, speech, end, saved, objections
│       ├── admin/              dashboard, blocks/, objections/, taxonomy/, users/, calls/
│       ├── auth/login.php  ·  history/  ·  profile/  ·  errors/{403,404,500}
├── config/
│   ├── config.example.php      modèle à copier
│   └── config.php              vos identifiants (ignoré par Git)
├── database/
│   ├── schema.sql              9 tables, clés étrangères, index, utf8mb4
│   └── seed.sql                données de démonstration complètes
├── storage/                    logs, cache — inaccessibles depuis le web
├── tools/                      scripts de développement (non publics)
└── README.md
```

---

## 5. Base de données

9 tables, `utf8mb4_unicode_ci`, clés étrangères `ON DELETE SET NULL` pour préserver l'historique.

| Table | Rôle | Points clés |
|-------|------|-------------|
| `users` | comptes internes | `role` (`commercial`/`admin`), `is_active`, `last_login_at`, `password_hash` |
| `sectors` | 4 secteurs | `name`, `icon`, `description`, `position`, `is_active` |
| `stages` | 3 étapes | idem |
| `services` | 4 services vendus | `description`, `icon`, `price_from` (tarif dès X € HT : site 790 €, application 2 900 €, automatisation et chatbot 990 €) |
| `speech_blocks` | discours modulaires | `type` ∈ présentation, accroche, découverte, argumentaire, preuve, tarif, appel_action, objection ; liens **optionnels** secteur / étape / service |
| `speech_block_versions` | historique | instantané avant chaque modification, `change_note`, restauration en un clic |
| `objections` | objections fréquentes | question + réponse courte, secteur / service optionnels |
| `calls` | journal d'appels | commercial, secteur, étape, service, résultat, note, `callback_date` |
| `login_attempts` | anti-force brute | e-mail haché + IP, index unique, purge automatique |

**Règle de sélection des blocs** : un bloc est retenu s'il est actif et si son secteur / étape / service
correspond au contexte **ou** est vide. Les blocs les plus spécifiques passent devant, les génériques
complètent, le tout trié par `position`. Une phrase de présentation générique apparaît donc
même quand un secteur dispose de ses propres accroches.

---

## 6. Guide — commercial

1. **Démarrer** : connectez-vous, l'écran **Nouvel appel** s'ouvre.
2. **Contexte** : cliquez sur un secteur, une étape. Facultatif : entreprise et contact
   (ils remplacent `{entreprise_client}` et `{contact}` dans le discours). Puis **Commencer**.
3. **Présentation** : lisez la présentation, puis choisissez une accroche. **Passer à la proposition**.
4. **Service** : cliquez la carte du service à proposer (tarif indicatif affiché).
5. **Speech** : déroulez chaque bloc dans l'ordre (découverte → argumentaire → preuve → tarif → action).
   Vous pouvez changer de service sans perdre le contexte.
6. **Objection** : à tout moment, bouton **Objections** en bas à droite (ou touche `Échap`).
   Cherchez l'objection, lisez la réponse courte, refermez.
7. **Fin d'appel** : résultat, note en une phrase, date de rappel si besoin → **Enregistrer**.
   Vous revenez à l'écran 1 pour l'appel suivant.
8. **Mes appels** : votre historique et l'export CSV de vos appels.

Raccourcis : `←` / `→` pour naviguer entre les blocs, `Échap` pour fermer le panneau, `?` pour l'aide clavier.

---

## 7. Guide — administrateur

| Section | Ce que vous pouvez faire |
|---------|--------------------------|
| **Tableau de bord** | appels du jour / semaine / mois, répartition des résultats, appels par commercial, services et secteurs les plus proposés, taux de rendez-vous et de devis |
| **Blocs de discours** | filtrer (type, secteur, étape, service, actif, recherche), créer, modifier, dupliquer, réordonner, activer/désactiver, supprimer, **prévisualiser** |
| **Historique** (dans un bloc) | voir chaque version, son auteur, sa note de changement, **restaurer** |
| **Objections** | ajouter / modifier une objection et sa réponse courte, la lier à un secteur ou un service |
| **Secteurs / Étapes / Services** | créer, renommer, changer l'icône, le tarif, l'ordre, activer/désactiver |
| **Équipe** | créer un compte (rôle commercial ou admin), changer le rôle, désactiver, réinitialiser le mot de passe |
| **Journal d'appels** | filtrer par commercial, résultat, service, secteur, période, texte libre ; **export CSV** |

### Publier un contenu

1. **Blocs de discours** → *Nouveau bloc* (ou *Modifier*).
2. Choisissez le type, le titre, rédigez le contenu :
   - `- texte` en début de ligne crée une puce ;
   - `**mot**` (ou `<strong>`) met en valeur une phrase clé ;
   - `{entreprise_client}`, `{contact}`, `{secteur}`, `{prenom_commercial}`, `{service}` sont remplacés à l'appel.
     Une variable vide disparaît proprement (pas de parenthèse ni de virgule orpheline).
3. Laissez **Secteur / Étape / Service** vides pour un bloc valable partout.
4. **Aperçu** montre exactement le rendu commercial — y compris avec un exemple de contexte.
5. **Enregistrer** : le contenu est visible immédiatement. L'ancienne version est archivée
   automatiquement dans **Historique**, où un clic sur **Restaurer** revient en arrière.

> Les blocs désactivés ne s'affichent jamais côté commercial ; les versions archivées non plus.
> La suppression d'un secteur / service utilisé(e) par un bloc ou un appel est refusée :
> désactivez-le plutôt.

---

## 8. Nginx (alternative à Apache)

```nginx
root /var/www/prime-script/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}

# Dossiers applicatifs jamais servis
location ~ ^/(app|config|database|storage|tools)/ { deny all; }
location ~* \.(sql|md|log|sh)$ { deny all; }
```

---

## 9. Sécurité

- **PDO + requêtes préparées** exclusivement — aucune concaténation de valeur dans le SQL.
- **CSRF** : jeton en session, vérifié sur **tous** les `POST` (`Http::verifyCsrf()`).
- **Rôles** : `commercial` et `admin`, garde par route (`['auth']` / `['admin']`) ;
  un commercial qui force `/admin/...` reçoit une page **403**, jamais la donnée.
- **Mots de passe** : `password_hash()` bcrypt coût 12, vérification `password_verify()`,
  politique de longueur minimale, changement par l'utilisateur avec ancien mot de passe exigé.
- **Sessions** : cookie `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS, identifiant de session
  régénéré à la connexion, expiration après 8 h d'inactivité.
- **Force brute** : 5 échecs → blocage 15 minutes, par e-mail **et** par IP, compteur en base.
- **Échappement** : toute sortie passe par `Sanitizer::e()` / `View::e()` ;
  le contenu riche des blocs est filtré par une liste blanche (balises + attributs autorisés).
- **Erreurs** : en production, aucune trace PHP à l'écran — message générique + journal dans `storage/logs/`.
- **Données sensibles** : `config/`, `app/`, `database/`, `storage/`, `tools/` bloqués par `.htaccess`
  et placés hors du document root lorsque c'est possible.

---

## 10. Exploitation et évolutions

**Sauvegarde** (quotidienne recommandée) :

```bash
mysqldump -u prime_script -p --single-transaction prime_script > sauvegarde-$(date +%F).sql
```

**Mise à jour du code** : déposez les fichiers, vérifiez `php -l` sur les fichiers modifiés,
rechargez PHP-FPM. Aucune migration automatique n'est nécessaire pour cette version
(schéma inchangé depuis `schema.sql`).

**Développement local sans MySQL** (démonstration uniquement) :

```bash
cp config/config.example.php config/config.php
# mettez driver => 'sqlite', env => 'development'
php tools/make-sample-db.php                 # génère storage/prime-script.sample.sqlite
php -S localhost:8099 -t public               # http://localhost:8099
```

**Périmètre v1 volontairement exclu** (l'architecture reste ouverte) :
téléphonie / CRM, enregistrement des appels, jeu de rôle, génération de contenu par IA,
application native, multilingue.

**Pistes d'amélioration naturelles** : duplication d'un contexte d'appel complet,
statistiques par période exportables, notifications de rappel arrivé à échéance,
journal d'audit administrateur, tests automatisés PHPUnit, recherche plein texte
sur les objections, mode hors ligne (PWA) pour les zones sans réseau.

---

## 11. Licence et contact

Usage interne — Prime Vision Solutions.
Contact : [contact@primevisionsolutions.fr](mailto:contact@primevisionsolutions.fr) · 01 88 31 89 12

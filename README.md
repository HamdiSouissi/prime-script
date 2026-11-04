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

### 3.3 Importer le schéma, les comptes puis le contenu

Trois fichiers, dans cet ordre :

```bash
mysql -u prime_script -p prime_script < database/schema.sql     # structure
mysql -u prime_script -p prime_script < database/seed.sql       # comptes, secteurs, étapes, services
mysql -u prime_script -p prime_script < database/content.sql    # contenu prêt à l'emploi (167 textes)
```

| Fichier | Contenu |
|---------|---------|
| `schema.sql` | Tables, index, contraintes. Aucune donnée. |
| `seed.sql` | 3 comptes, 4 secteurs, 3 étapes, 4 services et 20 appels de démonstration (pour visualiser le journal). Commence par des `TRUNCATE` : à jouer **une seule fois**, sur une base vide. |
| `content.sql` | Le script d'appel complet : 123 blocs et 44 objections. `INSERT … ON DUPLICATE KEY UPDATE` sur une colonne `code` : réexécutable sans créer de doublon. |

`content.sql` ne touche jamais aux comptes ni au journal d'appels. Vous pouvez donc le
rejouer après une mise à jour du contenu pour récupérer les textes d'origine.

> En production, si vous ne voulez pas des 20 appels de démonstration, importez
> `schema.sql` puis `content.sql`, puis créez le premier administrateur avec le script SQL
> fourni en fin de `seed.sql`.

### 3.3 bis  (alternative) Rejouer l'import depuis l'application

L'application sait faire le même import toute seule, sans accès `mysql` :

```bash
php tools/import-content.php --dry-run   # contrôle les fichiers, n'écrit rien
php tools/import-content.php             # importe ou met à jour le contenu
php tools/import-content.php --purge     # retire le contenu livré (code non vide)
```

L'import lit `database/content/*.sql` (un enregistrement JSON par ligne : lisible,
versionnable, modifiable sans toucher au code). Chaque bloc mis à jour archive sa version
précédente dans l'historique. En administration, le même import est disponible en un clic :
**Contenus → Blocs de speech → « Restaurer le contenu d'origine »**.

Deux contrôles utiles après une modification du contenu :

```bash
php tools/content-check.php   # les 48 combinaisons secteur × service × étape affichent-elles tout ?
php tools/content-audit.php   # expressions à éviter, doublons, longueur à l'oral, variables
```

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

> **Installation dans un sous-dossier** (`https://exemple.fr/prime-script/`) : renseignez
> `app.base_url` avec l'URL complète, **sous-dossier compris**. C'est la seule façon fiable
> de connaître le préfixe — certains serveurs (serveur intégré `php -S`, certaines
> passerelles FastCGI) renseignent `SCRIPT_NAME` avec le chemin *demandé* et non le script
> exécuté, ce qui fausserait la détection automatique.

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
66 vérifications : connexion, parcours complet d'un appel, objections en JSON,
enregistrement, historique, export CSV, navigation transversale (aucun lien interne
mort, y compris sur les URL à plusieurs segments) et contrôle d'accès administrateur.

```bash
BASE=http://localhost:8099 bash tools/smoke-test.sh
# si PHP n'est pas dans le PATH de la machine qui lance le test :
PHP_CLI=/usr/bin/php8.2 BASE=http://localhost:8099 bash tools/smoke-test.sh
```

La détection du chemin d'installation se teste séparément, sans serveur (8 configurations
de déploiement : racine, sous-dossier, document root au-dessus de `public/`, passerelle
qui renseigne `SCRIPT_NAME` avec le chemin demandé…) :

```bash
php tools/test-urls.php
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
│       ├── img/                logos Prime Vision (voir § 4.1) + sprite SVG icons.svg
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
│   │   ├── GuideController.php     « Bien démarrer » + modèles de mails
│   │   ├── HistoryController.php   mes appels + export CSV
│   │   ├── ProfileController.php   mon compte / mot de passe
│   │   └── Admin/                  tableau de bord, blocs, objections, taxonomies,
│   │                               utilisateurs, journal d'appels
│   ├── Content/                Guide.php et Templates.php : textes de « Bien démarrer »
│   ├── Models/                 une classe par table + Stats (agrégats SQL)
│   ├── Services/
│   │   ├── CallSession.php     contexte de l'appel en session
│   │   ├── ContentImporter.php import rejouable du contenu livré
│   │   └── SpeechRenderer.php  variables {…}, puces, assainissement du rendu
│   └── Views/
│       ├── layouts/            app.php (connecté), auth.php (connexion)
│       ├── partials/           stepper, call-context, objections-drawer, admin-subnav
│       ├── call/               start, presentation, services, speech, end, saved, objections
│       ├── guide/              index (Bien démarrer), templates (mails de suivi)
│       ├── admin/              dashboard, blocks/, objections/, taxonomy/, users/, calls/
│       ├── auth/login.php  ·  history/  ·  profile/  ·  errors/{403,404,500}
├── config/
│   ├── config.example.php      modèle à copier
│   └── config.php              vos identifiants (ignoré par Git)
├── database/
│   ├── schema.sql              9 tables, clés étrangères, index, utf8mb4
│   ├── seed.sql                comptes, secteurs, étapes, services, appels de démo
│   ├── content.sql             contenu prêt à l'emploi (généré, réexécutable)
│   ├── content/                source du contenu : 00-structure + 11 fichiers JSON
│   └── migrations/             migration pour une base déjà installée
├── storage/                    logs, cache — inaccessibles depuis le web
├── tools/                      scripts de développement (non publics)
└── README.md
```

---

### 4.1 Identité visuelle (logos et icônes)

Tous les fichiers sont générés à partir du **logo officiel Prime Vision Solutions** (le
verrou vertical fourni : marque RV au-dessus de « PRIME VISION / SOLUTIONS »). Fonds
transparents, tailles calées sur l'usage réel.

| Fichier | Taille | Usage |
|---------|--------|-------|
| `logo-horizontal.png` | 504 × 96 | en-tête de l'application, carte de connexion |
| `logo-horizontal-dark.png` | 504 × 96 | même usage en thème sombre (bleu nuit → blanc cassé) |
| `logo-full.png` | 555 × 300 | verrou vertical, documents et pages hors application |
| `logo-full-dark.png` | 555 × 300 | idem sur fond sombre |
| `logo-mark.png` | 371 × 240 | marque seule (RV), espaces étroits |
| `logo-mark-dark.png` | 371 × 240 | marque seule sur fond sombre |
| `favicon.png` / `favicon-32.png` | 64 / 32 px | onglet du navigateur |
| `apple-touch-icon.png` | 180 px | écran d'accueil iOS / Android |

**Pourquoi deux jeux de couleurs ?** Le bleu nuit du logo (`#05132A`) disparaît sur le fond
sombre de l'application (`#050b1e`). La version « dark » reprend la marque en blanc cassé et
éclaircit le dégradé bleu du V. Le basculement se fait en CSS (`data-logo-light` /
`data-logo-dark`), sans JavaScript : le bon fichier est déjà dans la page, aucun clignotement.

**Remplacer le logo** : déposez les nouveaux fichiers sous ces mêmes noms, en conservant les
proportions (5,25:1 pour la version horizontale). Les attributs `width` / `height` des balises
`<img>` évitent tout décalage de mise en page au chargement.

Pour régénérer tous les fichiers depuis le visuel carré d'origine :

```bash
python3 tools/build-logo.py        # nécessite Pillow : pip install pillow
```

Le script détoure le fond uni, recompose la version horizontale à partir de la marque et du
nom, produit les variantes sombres et les icônes. La source est `tools/brand/logo-source.png`.

Les couleurs de la marque sont centralisées dans `public/assets/css/tokens.css`
(`--brand-night`, `--brand-blue`, `--brand-cyan`), relevées sur le logo : `#05132A`,
`#0267D0`, `#2F8BF0`.

## 5. Base de données

9 tables, `utf8mb4_unicode_ci`, clés étrangères `ON DELETE SET NULL` pour préserver l'historique.

| Table | Rôle | Points clés |
|-------|------|-------------|
| `users` | comptes internes | `role` (`commercial`/`admin`), `is_active`, `last_login_at`, `password_hash` |
| `sectors` | 4 secteurs | `name`, `icon`, `description`, `position`, `is_active` |
| `stages` | 3 étapes | idem |
| `services` | 4 services vendus | `description`, `icon`, `price_from` (tarif dès X € HT : site 790 €, application 2 900 €, automatisation et chatbot 990 €) |
| `speech_blocks` | discours modulaires | `type` ∈ présentation, accroche, barrage, repondeur, découverte, argumentaire, preuve, tarif, appel_action, conclusion, objection ; `code` (unique, import rejouable) ; liens **optionnels** secteur / étape / service |
| `speech_block_versions` | historique | instantané avant chaque modification, `change_note`, restauration en un clic |
| `objections` | objections fréquentes | question + réponse courte, `code` (unique), secteur / service optionnels |
| `calls` | journal d'appels | commercial, secteur, étape, service, résultat (`rdv`, `devis`, `rappeler`, `pas_interesse`, `injoignable`, `ne_plus_rappeler`), note, `callback_date`, `do_not_call` |
| `login_attempts` | anti-force brute | e-mail haché + IP, index unique, purge automatique |

**Règle de sélection des blocs** : un bloc est retenu s'il est actif et si son secteur / étape / service
correspond au contexte **ou** est vide. Les blocs les plus spécifiques passent devant, les génériques
complètent, le tout trié par `position`. Une phrase de présentation générique apparaît donc
même quand un secteur dispose de ses propres accroches.

### Le contenu prêt à l'emploi

Le script livré se trouve dans `database/content/`, un fichier par famille de textes :

| Fichier | Contenu | Volume |
|---------|---------|--------|
| `01-presentations.sql` | présentation par secteur × étape (20 à 30 s) | 12 |
| `02-accroches-generiques.sql` | accroches toutes activités × étape | 12 |
| `03-accroches-secteurs.sql` | 3 accroches par secteur × étape | 36 |
| `04-barrage-et-repondeur.sql` | formules pour le standard, messages de répondeur | 8 |
| `05-decouverte.sql` | questions de découverte + leur raison d'être (4 services, 4 secteurs, relance, closing) | 10 |
| `06-argumentaires.sql` | 4 à 5 puces par service × secteur | 16 |
| `07-preuves.sql` | éléments de preuve honnêtes, par service | 4 |
| `08-tarifs.sql` | tarif de départ + passage à la suite, par service | 4 |
| `09-appels-a-l-action.sql` | appel à l'action par service × étape | 15 |
| `10-conclusions.sql` | quoi dire pour chaque résultat d'appel | 6 |
| `11-objections.sql` | 44 objections en quatre temps, dont la réponse « ne plus être rappelé » | 44 |

Les objections sont réparties en 18 générales, 22 liées à un service et 4 à un secteur :
chaque combinaison secteur × service en affiche entre 33 et 36.

Chaque texte se termine par une ligne `Conseil : …` (affichée en italique) qui explique
comment le dire. Les variables `{entreprise_client}`, `{contact}`, `{prenom_commercial}`,
`{service}` et `{secteur}` sont remplacées ; une variable vide disparaît proprement
(« Bonjour, de Prime Vision Solutions. » plutôt que « Bonjour , de… »).

Pour modifier le contenu : éditez les fichiers puis `php tools/import-content.php`.
Pour modifier un texte depuis l'interface : **Contenus → Blocs de speech** (l'import
ne réécrit jamais un texte identique, et archive la version précédente en cas de changement).

---

## 6. Guide — commercial

1. **Démarrer** : connectez-vous, l'écran **Nouvel appel** s'ouvre.
2. **Contexte** : cliquez sur un secteur, une étape. Facultatif : entreprise et contact
   (ils remplacent `{entreprise_client}` et `{contact}` dans le discours). Puis **Commencer**.
3. **Présentation** : lisez la présentation, puis choisissez une accroche.
   Deux boutons pour les cas courants : **Passer le standard** et **Répondeur** (message à laisser).
4. **Service** : cliquez la carte du service à proposer (tarif indicatif affiché).
5. **Speech** : déroulez chaque bloc dans l'ordre (découverte → argumentaire → preuve → tarif → action).
   Vous pouvez changer de service sans perdre le contexte.
6. **Objection** : à tout moment, bouton **Objections** en bas à droite (ou touche `O`).
   Cherchez l'objection, lisez la réponse courte, refermez.
7. **Fin d'appel** : choisissez le résultat, la phrase de conclusion du résultat s'affiche.
   Ajoutez une note en une phrase, une date de rappel si besoin → **Enregistrer**.
   Vous revenez à l'écran 1 pour l'appel suivant.
8. **Mes appels** : votre historique et l'export CSV de vos appels.

Si le prospect demande à **ne plus être rappelé** : dites la phrase prévue, choisissez le
résultat « Ne plus rappeler ». L'appel est marqué dans le journal, l'entreprise est signalée
« Ne pas rappeler » et l'application vous en avertit au prochain appel.

**Bien démarrer** (menu principal → *Bien démarrer*) : les règles d'or, la structure d'un
appel en cinq temps, les dix erreurs à éviter, un dialogue complet de deux minutes et un
glossaire. Un second onglet donne trois **modèles de mails de suivi** à copier.

Raccourcis : `←` / `→` pour naviguer entre les blocs, `Échap` pour fermer le panneau, `?` pour l'aide clavier.

---

## 7. Guide — administrateur

| Section | Ce que vous pouvez faire |
|---------|--------------------------|
| **Tableau de bord** | appels du jour / semaine / mois, répartition des résultats, appels par commercial, services et secteurs les plus proposés, taux de rendez-vous et de devis |
| **Blocs de discours** | filtrer (type, secteur, étape, service, actif, recherche), créer, modifier, dupliquer, réordonner, activer/désactiver, supprimer, **prévisualiser**, **restaurer le contenu d'origine** en un clic |
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
   - `{entreprise_client}`, `{contact}`, `{secteur}`, `{prenom_commercial}`, `{service}`, `{quand}` sont remplacés à l'appel.
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

> Avec le serveur intégré, `SCRIPT_NAME` vaut le chemin demandé (et non `index.php`) :
> l'application en tient compte depuis la v1.0.1, mais si vous installez le site dans un
> sous-dossier, renseignez toujours `app.base_url` (voir §3.4).

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

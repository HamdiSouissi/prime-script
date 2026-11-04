-- =====================================================================
--  Prime Script — Données de démonstration
--  À importer APRÈS database/schema.sql.
--
--  ⚠️  Les mots de passe ci-dessous sont des mots de passe de DÉMONSTRATION.
--      Changez-les immédiatement après l'installation (Admin → Équipe → mot de passe).
--
--    Administrateur : admin@primevisionsolutions.fr / AdminPrime2026!
--    Commercial 1   : hamdi@primevisionsolutions.fr / Commercial2026!
--    Commercial 2   : leila@primevisionsolutions.fr / Commercial2026!
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE speech_block_versions;
TRUNCATE TABLE calls;
TRUNCATE TABLE objections;
TRUNCATE TABLE speech_blocks;
TRUNCATE TABLE services;
TRUNCATE TABLE stages;
TRUNCATE TABLE sectors;
TRUNCATE TABLE login_attempts;
TRUNCATE TABLE users;

-- ---------------------------------------------------------------- Utilisateurs
-- Hachages bcrypt (cost 12) des mots de passe indiqués ci-dessus.
INSERT INTO users (id, name, email, password_hash, role, is_active, created_at) VALUES
(1, 'Sonia Mercier', 'admin@primevisionsolutions.fr', '$2y$12$BT1pf1kOEduyMf0xu4zYOesOhDf/OxXLMf6/Cuzi6p84IMFmozz5C', 'admin', 1, NOW()),
(2, 'Hamdi Souissi', 'hamdi@primevisionsolutions.fr', '$2y$12$fdRokuySp.nCPPRpWMD9UeaAqTv2Y4lmUNKRcgh6SSPXn2slcCg06', 'commercial', 1, NOW()),
(3, 'Leïla Benali', 'leila@primevisionsolutions.fr', '$2y$12$fdRokuySp.nCPPRpWMD9UeaAqTv2Y4lmUNKRcgh6SSPXn2slcCg06', 'commercial', 1, NOW());

-- ---------------------------------------------------------------- Secteurs
INSERT INTO sectors (id, name, slug, description, icon, position, is_active) VALUES
(1, 'Architectes et bureaux d''études', 'architectes', 'Agences d''architecture, maîtrise d''œuvre, bureaux d''études', 'building', 1, 1),
(2, 'Artisans, BTP et rénovation', 'artisans-btp', 'Plomberie, électricité, menuiserie, rénovation tous corps d''état', 'hammer', 2, 1),
(3, 'Restauration', 'restauration', 'Restaurants, traiteurs, cafés, food trucks', 'utensils', 3, 1),
(4, 'Services B2B', 'services-b2b', 'Conseil, formation, recrutement, services aux entreprises', 'handshake', 4, 1);

-- ---------------------------------------------------------------- Étapes de suivi
INSERT INTO stages (id, name, slug, description, color, position, is_active) VALUES
(1, 'Premier appel', 'premier-appel', 'Prise de contact : découvrir le besoin et ouvrir la porte', 'blue', 1, 1),
(2, 'Relance', 'relance', 'Le prospect a déjà eu le devis ou la présentation', 'amber', 2, 1),
(3, 'Closing', 'closing', 'Décision proche : lever les derniers doutes et fixer la suite', 'green', 3, 1);

-- ---------------------------------------------------------------- Services
INSERT INTO services (id, name, slug, description, icon, price_from, position, is_active) VALUES
(1, 'Site web', 'site-web', 'Un site rapide et clair qui présente votre activité et génère des demandes de devis.', 'monitor', 790, 1, 1),
(2, 'Application web / mobile', 'application', 'Une application sur mesure pour remplacer les tableurs et automatiser votre quotidien.', 'smartphone-app', 2900, 2, 1),
(3, 'Automatisation IA', 'automatisation-ia', 'Des assistants IA et des automatisations qui suppriment les tâches répétitives.', 'cpu', 990, 3, 1),
(4, 'Chatbot IA', 'chatbot-ia', 'Un assistant sur votre site qui répond 24 h/24 et capture les demandes de devis.', 'bot', 990, 4, 1);

-- =====================================================================
--  BLOCS DE SPEECH
--  NULL = « s'applique à tous ». Les blocs spécifiques sont prioritaires.
-- =====================================================================

-- ------------------------------------------------------ PRESENTATIONS
INSERT INTO speech_blocks (id, type, sector_id, stage_id, service_id, title, content, position, is_active) VALUES
(1, 'presentation', NULL, NULL, NULL, 'Prime Vision Solutions, en 20 secondes',
'<p>Je suis <strong>{prenom_commercial}</strong> de <strong>Prime Vision Solutions</strong>. Nous sommes une agence digitale qui travaille uniquement avec des TPE et des PME.</p><ul><li>Un <strong>seul interlocuteur</strong> du premier échange à la livraison</li><li>Des <strong>prix annoncés</strong>, pas de surprise</li><li>Un <strong>devis écrit sans engagement</strong></li><li>Le <strong>code et les accès remis au client</strong> à la livraison</li></ul>', 1, 1),

(2, 'presentation', 1, NULL, NULL, 'Pour une agence d''architecture',
'<p>Je suis <strong>{prenom_commercial}</strong> de Prime Vision Solutions. Nous accompagnons des agences d''architecture et des bureaux d''études sur leurs sites et leurs outils internes.</p><ul><li>Des sites qui <strong>valorisent vos réalisations</strong> avec de belles photos</li><li>Des outils pour <strong>suivre vos dossiers</strong> sans ressaisie</li><li>Un seul interlocuteur, un devis écrit, pas de jargon</li></ul>', 1, 1),

(3, 'presentation', 2, NULL, NULL, 'Pour un artisan ou une entreprise du bâtiment',
'<p>Je suis <strong>{prenom_commercial}</strong> de Prime Vision Solutions. Nous aidons des artisans et des entreprises de rénovation à <strong>être trouvés</strong> et à <strong>répondre plus vite</strong> à leurs demandes.</p><ul><li>Un site qui montre vos chantiers et rassure vos clients</li><li>Des devis et relances automatisés, moins de temps perdu</li><li>Prix annoncés, devis écrit, code remis au client</li></ul>', 1, 1),

(4, 'presentation', 3, NULL, NULL, 'Pour un restaurant',
'<p>Je suis <strong>{prenom_commercial}</strong> de Prime Vision Solutions. Nous travaillons avec des restaurants et des traiteurs pour <strong>remplir les tables</strong> et <strong>gagner du temps</strong> au service.</p><ul><li>Un site simple : menu, réservation, horaires toujours à jour</li><li>Un assistant qui répond aux questions 24 h/24</li><li>Prix annoncés, devis écrit sans engagement</li></ul>', 1, 1),

(5, 'presentation', 4, NULL, NULL, 'Pour une entreprise de services B2B',
'<p>Je suis <strong>{prenom_commercial}</strong> de Prime Vision Solutions. Nous équipons des entreprises de services avec des <strong>sites qui convertissent</strong> et des <strong>outils qui font gagner du temps</strong>.</p><ul><li>Un site clair, orienté prise de rendez-vous</li><li>Des automatisations sur vos devis, relances et rendez-vous</li><li>Un seul interlocuteur, un devis écrit sans engagement</li></ul>', 1, 1),

(6, 'presentation', NULL, 3, NULL, 'Rappel de contexte (closing)',
'<p>Bonjour, <strong>{prenom_commercial}</strong> de Prime Vision Solutions — nous nous étions parlé de votre projet.</p><ul><li>Je ne reviens pas sur tout : juste les <strong>deux points qui restaient à valider</strong></li><li>On peut démarrer en <strong>48 h</strong> après votre accord</li><li>Le devis est écrit, sans engagement</li></ul>', 2, 1);

-- ------------------------------------------------------ ACCROCHES
INSERT INTO speech_blocks (id, type, sector_id, stage_id, service_id, title, content, position, is_active) VALUES
(10, 'accroche', 1, NULL, NULL, 'Le carnet de commandes',
'<p>{contact}, je vous appelle parce que la plupart des agences d''architecture que nous rencontrons ont un problème commun : <strong>beaucoup de bouche-à-oreille, mais aucune trace visible en ligne</strong>. Est-ce que c''est aussi votre cas ?</p>', 1, 1),

(11, 'accroche', 1, NULL, NULL, 'Le temps perdu sur les dossiers',
'<p>{contact}, une question rapide : combien de temps passez-vous chaque semaine à retrouver des documents ou à ressaisir des informations de dossier ?</p>', 2, 1),

(12, 'accroche', 2, NULL, NULL, 'Les demandes perdues',
'<p>{contact}, je vous appelle parce qu''en ce moment beaucoup d''artisans reçoivent leurs demandes par téléphone et les perdent un peu. Vous aussi, il vous arrive de ne pas pouvoir rappeler quelqu''un ?</p>', 1, 1),

(13, 'accroche', 2, NULL, NULL, 'Les concurrents mieux placés',
'<p>{contact}, quand un particulier cherche un artisan chez vous, qu''est-ce qu''il trouve en premier ? Souvent, ce sont les entreprises qui ont un vrai site qui remportent la demande.</p>', 2, 1),

(14, 'accroche', 3, NULL, NULL, 'Les questions au téléphone',
'<p>{contact}, est-ce que vous passez beaucoup de temps au téléphone pour des questions simples : horaires, carte, réservation ?</p>', 1, 1),

(15, 'accroche', 3, NULL, NULL, 'La réservation en ligne',
'<p>{contact}, je vous appelle parce que de plus en plus de clients réservent en ligne, souvent le soir. Et vous, la réservation se fait comment chez vous aujourd''hui ?</p>', 2, 1),

(16, 'accroche', 4, NULL, NULL, 'Le pipeline invisible',
'<p>{contact}, la plupart des entreprises de services que nous accompagnons ont un point commun : elles savent qu''elles perdent des opportunités, mais elles ne savent pas <strong>où exactement</strong>. Ça vous parle ?</p>', 1, 1),

(17, 'accroche', 4, NULL, NULL, 'Le temps commercial',
'<p>{contact}, vos commerciaux passent-ils du temps à faire du suivi administratif au lieu de vendre ? C''est souvent là que se cachent deux jours par mois.</p>', 2, 1),

(18, 'accroche', NULL, NULL, NULL, 'Accroche générique',
'<p>Bonjour {contact}, <strong>{prenom_commercial}</strong> de Prime Vision Solutions. Je ne vous appelle pas pour vous vendre un site : je vous appelle pour comprendre comment vous recevez vos demandes de devis aujourd''hui. Vous avez deux minutes ?</p>', 5, 1),

(19, 'accroche', NULL, 2, NULL, 'Accroche de relance',
'<p>{contact}, je reviens vers vous comme convenu. Est-ce que vous avez pu jeter un œil au devis ? Je peux vous expliquer les deux points qui posent souvent question.</p>', 6, 1),

(20, 'accroche', NULL, 3, NULL, 'Accroche de closing',
'<p>{contact}, on y est presque : il reste une question à trancher. C''est plutôt le <strong>délai de démarrage</strong> ou le <strong>paiement en plusieurs fois</strong> ?</p>', 7, 1);

-- ------------------------------------------------------ DECOUVERTE
INSERT INTO speech_blocks (id, type, sector_id, stage_id, service_id, title, content, position, is_active) VALUES
(30, 'decouverte', NULL, NULL, NULL, 'Questions de découverte — les 4 indispensables',
'<ul><li>« Aujourd''hui, comment vos clients vous trouvent-ils ? »</li><li>« Combien de demandes recevez-vous par mois, à peu près ? »</li><li>« Qu''est-ce qui vous fait perdre le plus de temps dans la semaine ? »</li><li>« Si vous pouviez régler un seul de ces points, ce serait lequel ? »</li></ul>', 1, 1),

(31, 'decouverte', 1, NULL, NULL, 'Découverte — agence d''architecture',
'<ul><li>« Combien de projets en cours avez-vous en ce moment ? »</li><li>« Où sont vos réalisations aujourd''hui : Instagram, un site, un PDF ? »</li><li>« Comment un nouveau client vous contacte-t-il après avoir vu une de vos réalisations ? »</li><li>« Sur un projet, combien de temps passez-vous à chercher des documents ? »</li></ul>', 1, 1),

(32, 'decouverte', 2, NULL, NULL, 'Découverte — artisan / BTP',
'<ul><li>« Combien de demandes de devis par semaine ? »</li><li>« Vous y répondez sous quel délai, en moyenne ? »</li><li>« Est-ce que vous relancez les devis qui n''ont pas de réponse ? »</li><li>« Vous travaillez dans un rayon de combien de kilomètres ? »</li></ul>', 1, 1),

(33, 'decouverte', 3, NULL, NULL, 'Découverte — restaurant',
'<ul><li>« Vous recevez combien d''appels par jour pour des réservations ? »</li><li>« Quelles questions reviennent le plus souvent : horaires, carte, groupes ? »</li><li>« Combien de couverts par service en moyenne ? »</li><li>« Vous avez une page en ligne à jour : site, Google, réseaux ? »</li></ul>', 1, 1),

(34, 'decouverte', 4, NULL, NULL, 'Découverte — services B2B',
'<ul><li>« Combien de rendez-vous qualifiés par mois ? »</li><li>« D''où viennent vos prospects aujourd''hui ? »</li><li>« Combien de temps entre le premier contact et la signature ? »</li><li>« Qui suit les devis, et avec quel outil ? »</li></ul>', 1, 1),

(35, 'decouverte', NULL, 2, NULL, 'Découverte — relance',
'<ul><li>« Est-ce que le devis correspond à ce que vous attendiez ? »</li><li>« Qu''est-ce qui vous freine encore : le budget, le délai, la technique ? »</li><li>« Qui d''autre doit valider ? »</li><li>« Si on commence la semaine prochaine, c''est jouable ? »</li></ul>', 2, 1),

(36, 'decouverte', NULL, NULL, 2, 'Découverte — application sur mesure',
'<ul><li>« Comment vous organisez-vous aujourd''hui : tableur, cahier, logiciel ? »</li><li>« Qui doit avoir accès aux informations ? »</li><li>« Qu''est-ce qui vous ferait gagner une heure par jour ? »</li><li>« Avez-vous déjà un outil qui doit rester en place ? »</li></ul>', 1, 1),

(37, 'decouverte', NULL, NULL, 3, 'Découverte — automatisation IA',
'<ul><li>« Quelles tâches vous prennent le plus de temps chaque semaine ? »</li><li>« Combien de demandes arrivent par e-mail / formulaire / téléphone ? »</li><li>« Qui traite ces demandes, et à quel moment ? »</li><li>« Que se passe-t-il si une demande attend 48 h ? »</li></ul>', 1, 1),

(38, 'decouverte', NULL, NULL, 4, 'Découverte — chatbot IA',
'<ul><li>« Quelles questions reviennent le plus souvent de la part de vos clients ? »</li><li>« Combien d''appels par jour pour ces questions ? »</li><li>« Que se passe-t-il le soir et le week-end ? »</li><li>« Qui répond aux messages sur le site aujourd''hui ? »</li></ul>', 1, 1);

-- ------------------------------------------------------ ARGUMENTAIRES
INSERT INTO speech_blocks (id, type, sector_id, stage_id, service_id, title, content, position, is_active) VALUES
(50, 'argumentaire', NULL, NULL, 1, 'Argumentaire — site web',
'<ul><li><strong>Être trouvé</strong> : un site clair et rapide, prêt pour Google</li><li><strong>Rassurer</strong> : vos réalisations, vos avis, vos garanties</li><li><strong>Générer des demandes</strong> : un appel à l''action visible sur chaque page</li><li><strong>Modifiable par vous</strong> : textes et photos sans passer par nous</li><li><strong>Vous êtes propriétaire</strong> : code et accès remis à la livraison</li></ul>', 1, 1),

(51, 'argumentaire', NULL, NULL, 2, 'Argumentaire — application sur mesure',
'<ul><li><strong>Un seul outil</strong> au lieu de trois tableurs</li><li><strong>Fini la ressaisie</strong> : les informations circulent d''un écran à l''autre</li><li><strong>Accessible partout</strong> : ordinateur, tablette, téléphone</li><li><strong>Simple</strong> : conçu avec vos équipes, pas contre elles</li><li><strong>Évolutif</strong> : on ajoute des modules quand le besoin arrive</li></ul>', 1, 1),

(52, 'argumentaire', NULL, NULL, 3, 'Argumentaire — automatisation IA',
'<ul><li><strong>Moins de tâches répétitives</strong> : relances, relèves, comptes rendus</li><li><strong>Réponse plus rapide</strong> : chaque demande est traitée le jour même</li><li><strong>Zéro oubli</strong> : rien ne reste au fond d''une boîte mail</li><li><strong>Contrôle humain</strong> : vous validez ce qui part</li><li><strong>Rentabilisé en quelques semaines</strong></li></ul>', 1, 1),

(53, 'argumentaire', NULL, NULL, 4, 'Argumentaire — chatbot IA',
'<ul><li><strong>Répond 24 h/24</strong>, même quand vous êtes fermé</li><li><strong>Ne raconte rien de faux</strong> : il ne répond qu''à partir de vos informations validées</li><li><strong>Capture les demandes</strong> : nom, besoin, coordonnées</li><li><strong>Passe la main</strong> à un humain quand il faut</li><li><strong>Installé en quelques jours</strong> sur votre site</li></ul>', 1, 1),

(54, 'argumentaire', 1, NULL, NULL, 'Argumentaire — secteur architecture',
'<ul><li>Un site qui met vos projets en valeur, avec de vraies belles images</li><li>Une fiche par projet : programme, lieu, coût, photos</li><li>Un espace client pour partager plans et documents sans e-mail</li><li>Un suivi des dossiers en temps réel</li></ul>', 2, 1),

(55, 'argumentaire', 2, NULL, NULL, 'Argumentaire — secteur artisanat',
'<ul><li>Un site qui montre vos chantiers avant / après</li><li>Un formulaire de demande de devis en 30 secondes</li><li>Des relances automatiques sur les devis non signés</li><li>Les avis clients affichés pour rassurer</li></ul>', 2, 1),

(56, 'argumentaire', 3, NULL, NULL, 'Argumentaire — secteur restauration',
'<ul><li>Carte et menu toujours à jour, sans réimprimer</li><li>Réservation en ligne, y compris le soir</li><li>Un assistant qui répond aux questions d''horaires et d''accès</li><li>Moins d''appels pendant le service</li></ul>', 2, 1),

(57, 'argumentaire', 4, NULL, NULL, 'Argumentaire — secteur services B2B',
'<ul><li>Un site orienté prise de rendez-vous</li><li>Des devis générés en deux minutes</li><li>Des relances automatiques sur chaque devis</li><li>Un tableau de bord pour suivre le pipeline</li></ul>', 2, 1),

(58, 'argumentaire', NULL, 3, NULL, 'Argumentaire — closing',
'<ul><li><strong>On démarre en 48 h</strong> après accord, pas dans trois mois</li><li><strong>Paiement en 3 fois</strong> possible pour les projets</li><li><strong>Livraison par étapes</strong> : vous validez à chaque étape</li><li><strong>Vous restez libre</strong> : code et accès remis à la livraison</li></ul>', 3, 1);

-- ------------------------------------------------------ PREUVES
INSERT INTO speech_blocks (id, type, sector_id, stage_id, service_id, title, content, position, is_active) VALUES
(70, 'preuve', NULL, NULL, NULL, 'Une réalisation, concrètement',
'<ul><li><strong>Atelier Lumen</strong>, agence d''architecture à Paris 15ᵉ</li><li>Site refait en 3 semaines : galerie de projets, pages par réalisation</li><li>Résultat : <strong>2 à 3 demandes qualifiées par semaine</strong> arrivées du site</li><li>Le gérant met ses photos à jour lui-même</li></ul>', 1, 1),

(71, 'preuve', 1, NULL, NULL, 'Référence — agence d''architecture',
'<ul><li><strong>Atelier Lumen</strong> (Paris) : site + espace client</li><li>Galerie de projets filtrable par type de mission</li><li>Documents partagés avec les maîtres d''ouvrage, sans e-mail</li><li>Le gérant publie ses nouveaux projets en 5 minutes</li></ul>', 1, 1),

(72, 'preuve', 2, NULL, NULL, 'Référence — artisanat',
'<ul><li><strong>Rénov''Hauss</strong> (rénovation, 78) : site + devis automatisés</li><li>Demandes de devis directement dans une application</li><li>Relance automatique au bout de 7 jours</li><li>Résultat : <strong>+30 % de devis signés</strong> en trois mois</li></ul>', 1, 1),

(73, 'preuve', 3, NULL, NULL, 'Référence — restauration',
'<ul><li><strong>Maison Tilleul</strong> (bistrot de quartier) : site + réservation</li><li>Assistant IA : horaires, groupes, accessibilité</li><li>Résultat : <strong>moins d''appels pendant le service</strong>, plus de réservations le soir</li><li>Mise en ligne en 10 jours</li></ul>', 1, 1),

(74, 'preuve', 4, NULL, NULL, 'Référence — services B2B',
'<ul><li><strong>Cabinet Aldeva</strong> (conseil RH) : site + automatisation</li><li>Prise de rendez-vous puis relances automatiques</li><li>Résultat : <strong>2 jours par mois récupérés</strong> par consultant</li><li>Migration des données existantes incluse</li></ul>', 1, 1);

-- ------------------------------------------------------ TARIFS
INSERT INTO speech_blocks (id, type, sector_id, stage_id, service_id, title, content, position, is_active) VALUES
(80, 'tarif', NULL, NULL, NULL, 'Tarifs de départ (HT)',
'<ul><li><strong>Site web</strong> : à partir de <strong>790 €</strong></li><li><strong>Application web</strong> : à partir de <strong>2 900 €</strong></li><li><strong>Application mobile</strong> : à partir de <strong>3 900 €</strong></li><li><strong>Assistants IA et automatisation</strong> : à partir de <strong>990 €</strong></li></ul><p><strong>Toujours un devis écrit, détaillé et sans engagement.</strong> Le prix final dépend de ce qu''on met dedans — pas de surprise.</p>', 1, 1),

(81, 'tarif', NULL, NULL, 1, 'Tarif — site web',
'<ul><li>Site vitrine <strong>à partir de 790 € HT</strong></li><li>5 pages essentielles : accueil, services, réalisations, à propos, contact</li><li>Formulaire de contact et référencement de base inclus</li><li>Hébergement et nom de domaine en option</li></ul><p><strong>Devis écrit sous 48 h, sans engagement.</strong></p>', 1, 1),

(82, 'tarif', NULL, NULL, 2, 'Tarif — application sur mesure',
'<ul><li>Application web <strong>à partir de 2 900 € HT</strong></li><li>Application mobile iOS / Android <strong>à partir de 3 900 € HT</strong></li><li>Périmètre défini ensemble, livré par étapes</li><li>Formation de vos équipes incluse</li></ul><p><strong>Devis écrit détaillé, paiement en 3 fois possible.</strong></p>', 1, 1),

(83, 'tarif', NULL, NULL, 3, 'Tarif — automatisation IA',
'<ul><li>Automatisation de tâches <strong>à partir de 990 € HT</strong></li><li>Forfait découverte pour identifier les gains</li><li>Mise en production en 2 à 4 semaines</li><li>Accompagnement de vos équipes inclus</li></ul><p><strong>Devis écrit, sans engagement. Gains chiffrés avant de commencer.</strong></p>', 1, 1),

(84, 'tarif', NULL, NULL, 4, 'Tarif — chatbot IA',
'<ul><li>Assistant IA sur votre site <strong>à partir de 990 € HT</strong></li><li>Formation sur <em>vos</em> informations, pas sur internet</li><li>Passage à un humain en un clic</li><li>Maintenance à partir de <strong>49 € HT/mois</strong></li></ul><p><strong>Mise en ligne en quelques jours, devis écrit sans engagement.</strong></p>', 1, 1),

(85, 'tarif', NULL, 3, NULL, 'Tarif — closing : conditions de démarrage',
'<ul><li>Le devis reste <strong>valable 30 jours</strong></li><li><strong>Paiement en 3 fois</strong> : 30 % à la commande, 40 % à mi-projet, 30 % à la livraison</li><li><strong>Démarrage sous 48 h</strong> après accord</li><li>Aucun frais caché : ce qui est dans le devis est dans la facture</li></ul>', 2, 1);

-- ------------------------------------------------------ APPELS A L'ACTION
INSERT INTO speech_blocks (id, type, sector_id, stage_id, service_id, title, content, position, is_active) VALUES
(90, 'appel_action', NULL, NULL, NULL, 'Prochaine étape : l''audit gratuit',
'<p>Je vous propose une chose simple : <strong>je regarde votre site (ou votre organisation) et je vous fais un retour en 10 minutes de visio</strong>. C''est gratuit et sans engagement.</p><ul><li>3 points forts à garder</li><li>3 points à corriger en priorité</li><li>Une estimation chiffrée si vous le souhaitez</li></ul><p><strong>On se cale sur quel jour cette semaine ?</strong></p>', 1, 1),

(91, 'appel_action', NULL, NULL, NULL, 'Prochaine étape : le devis écrit',
'<p>Je vous envoie un <strong>devis écrit, détaillé et sans engagement</strong>. Vous le recevez sous 48 h, vous prenez le temps de le lire.</p><ul><li>Prix précis, pas de fourchette floue</li><li>Délai de livraison indiqué</li><li>Vous décidez tranquillement, sans relance insistante</li></ul><p><strong>Je vous envoie ça par e-mail, à quelle adresse ?</strong></p>', 2, 1),

(92, 'appel_action', NULL, NULL, NULL, 'Prochaine étape : le rendez-vous',
'<p>Je vous propose <strong>un rendez-vous de 20 minutes</strong>, par téléphone ou en visio, pour regarder votre situation concrètement.</p><ul><li>Vous repartez avec des recommandations, même sans travailler avec nous</li><li>Aucun engagement, aucun frais</li><li>On fixe la date maintenant : plutôt mardi matin ou jeudi après-midi ?</li></ul>', 3, 1),

(93, 'appel_action', NULL, 3, NULL, 'Appel à l''action — closing',
'<p>Si tout est clair, je vous envoie le devis à signer électroniquement aujourd''hui. <strong>On démarre lundi.</strong></p><ul><li>Signature en ligne, 2 minutes</li><li>Facture d''acompte immédiate</li><li>Point d''avancement chaque semaine</li></ul>', 4, 1);



-- =====================================================================
--  OBJECTIONS ET RÉPONSES RECOMMANDÉES
--  Réponses courtes, en puces : le commercial lit la première phrase,
--  puis adapte. Les objections spécifiques remontent avant les générales.
-- =====================================================================
INSERT INTO objections (id, question, answer, service_id, sector_id, position, is_active) VALUES
(1, 'J''ai déjà un site', '<ul><li>Très bien, c''est un bon point de départ.</li><li>Est-ce qu''il vous amène des demandes de devis aujourd''hui ?</li><li>La plupart de nos clients avaient un site… mais pas à jour et non trouvable sur Google.</li><li>Je peux le regarder gratuitement et vous dire ce qui bloque.</li></ul>', NULL, NULL, 1, 1),

(2, 'C''est trop cher', '<ul><li>Je comprends, c''est un vrai sujet.</li><li>On commence par un site à partir de 790 € HT — pas 5 000 €.</li><li>Le devis est écrit et sans engagement : vous voyez le prix exact avant de décider.</li><li>Paiement en plusieurs fois possible.</li><li>Question : combien vous rapporte un client de plus par mois ?</li></ul>', NULL, NULL, 2, 1),

(3, 'Envoyez-moi un mail', '<ul><li>Avec plaisir. Pour que le mail soit utile : quel est votre plus gros frein aujourd''hui ?</li><li>Je vous envoie 2 pages maximum : ce qu''on ferait, le prix, le délai.</li><li>On se rappelle 15 minutes dans deux jours ?</li></ul>', NULL, NULL, 3, 1),

(4, 'Je n''ai pas le temps', '<ul><li>Je comprends, je vais droit au but.</li><li>Une question, 30 secondes : comment recevez-vous vos demandes de devis aujourd''hui ?</li><li>Si ce n''est pas le moment, je vous rappelle. Plutôt demain matin ou après 18 h ?</li></ul>', NULL, NULL, 4, 1),

(5, 'Je dois en parler à mon associé', '<ul><li>C''est normal, c''est une décision à deux.</li><li>Je vous prépare un récapitulatif d''une page, clair et chiffré.</li><li>On peut faire un point à trois de 20 minutes : mardi ou jeudi ?</li></ul>', NULL, NULL, 5, 1),

(6, 'Je ne suis pas intéressé', '<ul><li>Pas de souci, je ne veux pas vous faire perdre de temps.</li><li>Juste pour ma compréhension : vous avez déjà tout ce qu''il faut, ou ce n''est pas le moment ?</li><li>Si ce n''est pas le moment, quand serait-il plus opportun de vous rappeler ?</li></ul>', NULL, NULL, 6, 1),

(7, 'Je travaille déjà avec quelqu''un', '<ul><li>Très bien, c''est rassurant d''avoir un partenaire.</li><li>Vous êtes satisfait de ce qui est fait aujourd''hui ?</li><li>Nous intervenons souvent en second : pour l''IA, le tracking ou la maintenance, sans toucher à l''existant.</li></ul>', NULL, NULL, 7, 1),

(8, 'Je gère tout avec un tableur', '<ul><li>Ça marche… jusqu''au jour où deux personnes modifient la même ligne.</li><li>Un outil sur mesure coûte dès 2 900 € HT, et supprime les doubles saisies.</li><li>Je peux vous montrer un exemple similaire en 15 minutes de visio.</li></ul>', NULL, NULL, 8, 1),

(9, 'L''IA, ça me fait un peu peur', '<ul><li>C''est une réaction saine.</li><li>L''assistant ne répond <strong>qu''à partir de vos informations validées</strong>, rien d''inventé.</li><li>Vous gardez la main : un humain reprend la conversation quand il faut.</li><li>On commence sur un périmètre restreint, on élargit ensuite.</li></ul>', NULL, NULL, 9, 1),

(10, 'Il faut que je compare les devis', '<ul><li>Excellente idée, et c''est gratuit.</li><li>Ce qui compte : le prix final, le délai et qui vous avez au téléphone après la livraison.</li><li>Chez nous : prix annoncé, un seul interlocuteur, code remis à la livraison.</li><li>Je vous rappelle dans 5 jours pour en parler ?</li></ul>', NULL, NULL, 10, 1),

(11, 'Pour un restaurant : j''ai déjà TheFork / Instagram', '<ul><li>Les plateformes sont utiles, mais vous ne possédez ni la visibilité ni les données.</li><li>Un site simple vous rend propriétaire de votre fiche, de votre carte et de vos réservations.</li><li>Et l''assistant répond aux questions d''horaires et de groupes le soir, quand la salle est pleine.</li></ul>', NULL, 3, 11, 1),

(12, 'Pour un artisan : les clients me trouvent par bouche-à-oreille', '<ul><li>C''est le meilleur signe de qualité… et le plus fragile.</li><li>Aujourd''hui, 8 particuliers sur 10 vérifient sur Google avant d''appeler.</li><li>Un site simple à partir de 790 € HT rend ce bouche-à-oreille visible et vérifiable.</li></ul>', NULL, 2, 12, 1),

(13, 'Application : on n''a pas les moyens', '<ul><li>Je comprends. Souvent, on ne parle pas du même budget.</li><li>Un site générique coûte 0 € mais ne fait pas votre métier.</li><li>On peut démarrer par un module unique à 2 900 € HT, puis ajouter le reste.</li></ul>', 2, NULL, 13, 1),

(14, 'Chatbot : j''ai peur que ça réponde mal aux clients', '<ul><li>C''est le risque n°1, et il se règle par le périmètre.</li><li>L''assistant répond uniquement dans son domaine : horaires, services, tarifs connus.</li><li>Dès qu''il ne sait pas, il propose de passer la main à un humain.</li><li>Dès 990 € HT, on peut tester sur un seul sujet.</li></ul>', 4, NULL, 14, 1),

(15, 'Architecte : mes photos sont déjà sur Instagram', '<ul><li>Instagram est parfait pour la découverte.</li><li>Un site structure vos projets par mission, avec les documents et les résultats.</li><li>Les maîtres d''ouvrage cherchent souvent sur Google, pas sur Instagram.</li></ul>', NULL, 1, 15, 1);



-- =====================================================================
--  APPELS DE DÉMONSTRATION (30 jours glissants, pour les tableaux de bord)
-- =====================================================================
INSERT INTO calls (user_id, sector_id, stage_id, service_id, entreprise_client, contact, result, note, callback_date, created_at) VALUES
(2, 1, 1, 1, 'Atelier Lumen', 'Mme Duval', 'rdv', 'Intéressée par une refonte du site. RDV jeudi 10 h.', NULL, DATE_SUB(NOW(), INTERVAL 0 DAY)),
(2, 3, 1, 4, 'Maison Tilleul', 'M. Perrin', 'devis', 'Devis chatbot à envoyer. Questions sur les groupes.', DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 0 DAY)),
(2, 2, 1, 1, 'Rénov''Hauss', 'Karim B.', 'rappeler', 'Rappeler lundi matin, il est sur un chantier.', DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 4, 1, 3, 'Cabinet Aldeva', 'Mme Roche', 'rdv', 'Audit gratuit accepté, visio mardi 14 h.', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 1, 2, 2, 'Studio Merisier', 'M. Lamy', 'pas_interesse', 'A déjà un prestataire.', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 3, 2, 1, 'Le Comptoir du Port', 'Sandra', 'devis', 'Site 5 pages + réservation. Devis envoyé.', DATE_ADD(CURDATE(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 2, 1, 1, 'Toiture & Cie', 'M. Garnier', 'injoignable', 'Aucune réponse, rappeler.', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 4, 3, 2, 'Bureau Vallier', 'Mme Vidal', 'rdv', 'Closing prévu vendredi.', NULL, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 3, 1, 4, 'Traiteur Origan', 'Julien', 'rappeler', 'Rappeler après le week-end.', DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 1, 1, 1, 'Agence Kaléa', 'Mme Fontaine', 'devis', 'Refonte de site, devis à faire.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
(3, 4, 1, 3, 'Formation Nexa', 'M. Bertrand', 'devis', 'Automatisation des relances.', NULL, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(2, 2, 1, 1, 'Élec''Sud', 'M. Pastor', 'rdv', 'RDV pris pour la semaine prochaine.', NULL, DATE_SUB(NOW(), INTERVAL 9 DAY)),
(3, 1, 3, 2, 'Atelier Boréal', 'Mme Camus', 'rdv', 'Signature prochaine, devis validé.', NULL, DATE_SUB(NOW(), INTERVAL 11 DAY)),
(2, 3, 1, 1, 'Café Méridien', 'Le gérant', 'pas_interesse', 'Pas le moment, réouverture en septembre.', NULL, DATE_SUB(NOW(), INTERVAL 13 DAY)),
(3, 2, 2, 1, 'Menuiserie Fabre', 'M. Fabre', 'rappeler', 'Rappel après son chantier.', DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY)),
(2, 4, 1, 4, 'Conseil Veyra', 'Mme Sassi', 'rdv', 'Visio de présentation jeudi.', NULL, DATE_SUB(NOW(), INTERVAL 18 DAY)),
(3, 3, 2, 4, 'Bistrot Néro', 'M. Colin', 'devis', 'Chatbot + carte en ligne.', NULL, DATE_SUB(NOW(), INTERVAL 21 DAY)),
(2, 1, 2, 2, 'Cabinet Ostier', 'M. Ostier', 'pas_interesse', 'Budget reporté.', NULL, DATE_SUB(NOW(), INTERVAL 24 DAY)),
(3, 4, 3, 3, 'Groupe Solane', 'Mme Ben', 'rdv', 'Contrat maintenance signé.', NULL, DATE_SUB(NOW(), INTERVAL 27 DAY)),
(2, 2, 1, 1, 'Isolation Perrin', 'M. Perrin', 'injoignable', 'Numéro erroné.', NULL, DATE_SUB(NOW(), INTERVAL 29 DAY));

SET FOREIGN_KEY_CHECKS = 1;

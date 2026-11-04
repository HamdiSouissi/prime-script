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
(2, 'Application web / mobile', 'application', 'Une application sur mesure : site métier, espace client ou application mobile.', 'smartphone-app', 2900, 2, 1),
(3, 'Automatisation IA', 'automatisation-ia', 'Des assistants IA et des automatisations qui suppriment les tâches répétitives.', 'cpu', 990, 3, 1),
(4, 'Chatbot IA', 'chatbot-ia', 'Un assistant sur votre site qui répond 24 h/24 et capture les demandes de devis.', 'bot', 990, 4, 1);


-- =====================================================================
--  CONTENU DE TRAVAIL (blocs de speech et objections)
--
--  Il ne se trouve plus dans ce fichier : le contenu prêt à l'emploi est
--  livré séparément, dans database/content.sql (MySQL) ou
--  database/content/*.sql (sources, importées par tools/import-content.php).
--
--  Import complet d'une installation neuve :
--      mysql ... < database/schema.sql
--      mysql ... < database/seed.sql      (comptes, taxonomies, journal de démo)
--      mysql ... < database/content.sql   (contenu des appels)
-- =====================================================================

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

-- =====================================================================
--  Prime Script — Schéma de base de données (MySQL 8 / MariaDB 10.4+)
--  Encodage : utf8mb4 (accents, emojis dans les contenus)
--  Moteur   : InnoDB (clés étrangères, transactions)
-- =====================================================================
--  Installation :
--    mysql -u root -p
--    CREATE DATABASE prime_script CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--    USE prime_script;
--    SOURCE database/schema.sql;
--    SOURCE database/seed.sql;
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
--  Utilisateurs (commerciaux et administrateurs)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS speech_block_versions;
DROP TABLE IF EXISTS calls;
DROP TABLE IF EXISTS objections;
DROP TABLE IF EXISTS speech_blocks;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS stages;
DROP TABLE IF EXISTS sectors;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('commercial', 'admin') NOT NULL DEFAULT 'commercial',
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at   DATETIME NULL DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_active (role, is_active)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Secteurs d'activité du prospect (Architectes, Artisans, Restauration…)
-- ---------------------------------------------------------------------
CREATE TABLE sectors (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL,
    slug        VARCHAR(120) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    icon        VARCHAR(40)  NOT NULL DEFAULT 'building',   -- clé d'icône SVG interne (pas de HTML libre)
    position    INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sectors_slug (slug),
    KEY idx_sectors_order (is_active, position)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Étapes de suivi commercial (Premier appel, Relance, Closing)
-- ---------------------------------------------------------------------
CREATE TABLE stages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL,
    slug        VARCHAR(120) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    color       VARCHAR(20) NOT NULL DEFAULT 'blue',        -- jeton de couleur interne
    position    INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_stages_slug (slug),
    KEY idx_stages_order (is_active, position)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Services vendus (Site web, Application, Automatisation IA, Chatbot IA)
-- ---------------------------------------------------------------------
CREATE TABLE services (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(120) NOT NULL,
    slug         VARCHAR(120) NOT NULL,
    description  VARCHAR(255) NOT NULL DEFAULT '',          -- 1 phrase, affichée sous la carte
    icon         VARCHAR(40)  NOT NULL DEFAULT 'globe',
    price_from   INT UNSIGNED NULL DEFAULT NULL,            -- tarif indicatif HT en euros
    position     INT NOT NULL DEFAULT 0,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_services_slug (slug),
    KEY idx_services_order (is_active, position)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Blocs de speech modulaires
--  Un bloc avec sector_id / stage_id / service_id NULL s'applique à tout.
-- ---------------------------------------------------------------------
CREATE TABLE speech_blocks (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    type        ENUM('presentation','accroche','decouverte','argumentaire','preuve','tarif','appel_action','objection')
                NOT NULL,
    sector_id   INT UNSIGNED NULL DEFAULT NULL,
    stage_id    INT UNSIGNED NULL DEFAULT NULL,
    service_id  INT UNSIGNED NULL DEFAULT NULL,
    title       VARCHAR(160) NOT NULL DEFAULT '',
    content     MEDIUMTEXT NOT NULL,                         -- HTML enrichi simple, assaini
    position    INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_by  INT UNSIGNED NULL DEFAULT NULL,
    updated_by  INT UNSIGNED NULL DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_blocks_selection (type, is_active, position),
    KEY idx_blocks_sector (sector_id),
    KEY idx_blocks_stage (stage_id),
    KEY idx_blocks_service (service_id),
    CONSTRAINT fk_blocks_sector  FOREIGN KEY (sector_id)  REFERENCES sectors (id)  ON DELETE SET NULL,
    CONSTRAINT fk_blocks_stage   FOREIGN KEY (stage_id)   REFERENCES stages (id)   ON DELETE SET NULL,
    CONSTRAINT fk_blocks_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL,
    CONSTRAINT fk_blocks_author  FOREIGN KEY (created_by) REFERENCES users (id)    ON DELETE SET NULL,
    CONSTRAINT fk_blocks_editor  FOREIGN KEY (updated_by) REFERENCES users (id)    ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Historique des versions d'un bloc (restauration possible)
-- ---------------------------------------------------------------------
CREATE TABLE speech_block_versions (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    block_id      INT UNSIGNED NOT NULL,
    version_no    INT UNSIGNED NOT NULL,
    type          VARCHAR(40) NOT NULL,
    sector_id     INT UNSIGNED NULL DEFAULT NULL,
    stage_id      INT UNSIGNED NULL DEFAULT NULL,
    service_id    INT UNSIGNED NULL DEFAULT NULL,
    title         VARCHAR(160) NOT NULL DEFAULT '',
    content       MEDIUMTEXT NOT NULL,
    position      INT NOT NULL DEFAULT 0,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    change_note   VARCHAR(255) NOT NULL DEFAULT '',
    created_by    INT UNSIGNED NULL DEFAULT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_version (block_id, version_no),
    KEY idx_versions_block (block_id, created_at),
    CONSTRAINT fk_versions_block FOREIGN KEY (block_id) REFERENCES speech_blocks (id) ON DELETE CASCADE,
    CONSTRAINT fk_versions_user  FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Objections et réponses recommandées
-- ---------------------------------------------------------------------
CREATE TABLE objections (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    question   VARCHAR(255) NOT NULL,
    answer     MEDIUMTEXT NOT NULL,                          -- HTML enrichi simple (puces courtes)
    service_id INT UNSIGNED NULL DEFAULT NULL,
    sector_id  INT UNSIGNED NULL DEFAULT NULL,
    position   INT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_objections_order (is_active, position),
    KEY idx_objections_service (service_id),
    KEY idx_objections_sector (sector_id),
    CONSTRAINT fk_objections_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL,
    CONSTRAINT fk_objections_sector  FOREIGN KEY (sector_id)  REFERENCES sectors (id)  ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Journal des appels
-- ---------------------------------------------------------------------
CREATE TABLE calls (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id          INT UNSIGNED NOT NULL,
    sector_id        INT UNSIGNED NULL DEFAULT NULL,
    stage_id         INT UNSIGNED NULL DEFAULT NULL,
    service_id       INT UNSIGNED NULL DEFAULT NULL,
    entreprise_client VARCHAR(160) NOT NULL DEFAULT '',
    contact          VARCHAR(120) NOT NULL DEFAULT '',
    result           ENUM('rdv','devis','rappeler','pas_interesse','injoignable') NOT NULL,
    note             VARCHAR(600) NOT NULL DEFAULT '',
    callback_date    DATE NULL DEFAULT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_calls_user_date (user_id, created_at),
    KEY idx_calls_date (created_at),
    KEY idx_calls_result (result),
    KEY idx_calls_sector (sector_id),
    KEY idx_calls_service (service_id),
    KEY idx_calls_callback (callback_date),
    CONSTRAINT fk_calls_user    FOREIGN KEY (user_id)    REFERENCES users (id)    ON DELETE RESTRICT,
    CONSTRAINT fk_calls_sector  FOREIGN KEY (sector_id)  REFERENCES sectors (id)  ON DELETE SET NULL,
    CONSTRAINT fk_calls_stage   FOREIGN KEY (stage_id)   REFERENCES stages (id)   ON DELETE SET NULL,
    CONSTRAINT fk_calls_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Tentatives de connexion (limitation de la force brute)
-- ---------------------------------------------------------------------
CREATE TABLE login_attempts (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    throttle_key VARCHAR(191) NOT NULL,                      -- email|ip
    ip         VARCHAR(45) NOT NULL DEFAULT '',
    successful TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_key_date (throttle_key, created_at),
    KEY idx_attempts_date (created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

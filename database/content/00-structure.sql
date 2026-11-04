-- =====================================================================
--  Prime Script — Contenu prêt à l'emploi (00) : structure et colonne de suivi
--
--  Ce fichier est le prologue du contenu : il prépare la base avant l'import.
--  Il est repris automatiquement en tête de database/content.sql.
-- =====================================================================

SET NAMES utf8mb4;


-- ---------------------------------------------------------------------
--  1) Colonnes de suivi : sur une base neuve, database/schema.sql les crée
--     déjà (`code` sur speech_blocks et sur objections). Les instructions
--     ci-dessous ne servent qu'aux bases installées avant ce lot.
-- ---------------------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'speech_blocks' AND COLUMN_NAME = 'code'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE speech_blocks ADD COLUMN code VARCHAR(80) NULL DEFAULT NULL AFTER type, ADD UNIQUE KEY uq_blocks_code (code)',
    'SELECT ''colonne code déjà présente'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'objections' AND COLUMN_NAME = 'code'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE objections ADD COLUMN code VARCHAR(120) NULL DEFAULT NULL AFTER id, ADD UNIQUE KEY uq_objections_code (code)',
    'SELECT ''colonne code déjà présente sur objections'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
--  2) Mêmes deux ajouts que la migration : types de blocs et résultat
--     d'appel « Ne plus rappeler » (sans effet si déjà appliqués).
-- ---------------------------------------------------------------------
ALTER TABLE speech_blocks
    MODIFY COLUMN type ENUM(
        'presentation','accroche','barrage','repondeur','decouverte',
        'argumentaire','preuve','tarif','appel_action','conclusion','objection'
    ) NOT NULL;

ALTER TABLE calls
    MODIFY COLUMN result ENUM(
        'rdv','devis','rappeler','pas_interesse','injoignable','ne_plus_rappeler'
    ) NOT NULL;

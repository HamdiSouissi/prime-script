-- =====================================================================
--  Prime Script — Migration « contenu prêt à l'emploi »
--  À exécuter UNE FOIS sur une base installée avant ce lot :
--
--      mysql -u prime_script -p prime_script < database/migrations/2026_11_04_contenu_pret_a_l_emploi.sql
--
--  Elle ajoute deux types de blocs (passage du standard, message vocal)
--  et le résultat d'appel « Ne plus rappeler », sans rien supprimer.
--  Une base neuve créée avec database/schema.sql n'a pas besoin de cette
--  migration : elle contient déjà ces éléments.
-- =====================================================================

SET NAMES utf8mb4;

-- 1) Deux nouveaux types de blocs : barrage (standard / secrétariat) et
--    repondeur (message vocal à laisser sur une messagerie).
ALTER TABLE speech_blocks
    MODIFY COLUMN type ENUM(
        'presentation','accroche','barrage','repondeur','decouverte',
        'argumentaire','preuve','tarif','appel_action','conclusion','objection'
    ) NOT NULL;

-- 2) Résultat d'appel « Ne plus rappeler » + indicateur d'opposition.
ALTER TABLE calls
    MODIFY COLUMN result ENUM(
        'rdv','devis','rappeler','pas_interesse','injoignable','ne_plus_rappeler'
    ) NOT NULL;

ALTER TABLE calls
    ADD COLUMN do_not_call TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = le prospect a demandé à ne plus être rappelé' AFTER callback_date,
    ADD KEY idx_calls_dnc (do_not_call);

-- 3) Identifiant stable des blocs et objections livrés : rend l'import rejouable
--    (aucun doublon à la réexécution). Les lignes existantes restent avec code NULL
--    et ne sont donc jamais modifiées par l'import.
ALTER TABLE speech_blocks
    ADD COLUMN code VARCHAR(80) NULL DEFAULT NULL
        COMMENT 'identifiant du contenu livré (import rejouable)' AFTER type,
    ADD UNIQUE KEY uq_blocks_code (code);

ALTER TABLE objections
    ADD COLUMN code VARCHAR(80) NULL DEFAULT NULL
        COMMENT 'identifiant de l''objection livrée' AFTER id,
    ADD UNIQUE KEY uq_objections_code (code);

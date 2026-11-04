-- =====================================================================
--  Prime Script — Contenu prêt à l'emploi (10/12) : Phrases de fin d'appel (par résultat)
--
--  Ce fichier s'importe avec les autres fichiers de database/content/ :
--      php tools/import-content.php
--  (ou, en MySQL : mysql -u ... prime_script < database/content/content.sql
--   si ce fichier a été concaténé dans content.sql).
--
--  Format d'une ligne de contenu (JSON, une ligne par enregistrement) :
--    {"code": "...", "type": "...", "sector": "...", "stage": "...",
--     "service": "...", "title": "...", "position": 10, "content": "<p>…</p>"}
--
--    code     identifiant stable du bloc (réimport = mise à jour, jamais de doublon)
--    type     presentation | accroche | barrage | repondeur | decouverte |
--             argumentaire | preuve | tarif | appel_action | conclusion
--    sector   slug du secteur (architectes, artisans-btp, restauration, services-b2b)
--    stage    slug de l'étape (premier-appel, relance, closing)
--    service  slug du service (site-web, application, automatisation-ia, chatbot-ia)
--    content  HTML simple : <p>, <ul>, <li>, <strong>, <em>
--
--  Convention d'écriture : le texte à dire est en clair, l'aide au commercial
--  est sur une ligne « Conseil : … » en italique (<p><em>Conseil : …</em></p>).
-- =====================================================================

SET NAMES utf8mb4;

{"code": "fin-rdv", "type": "conclusion", "title": "Rendez-vous pris", "position": 1, "content": "<p>Parfait, {contact}. Je note notre rendez-vous et je vous envoie une confirmation par mail dans la journée.</p><ul><li>Récapitulez la date et l'heure à voix haute, une seule fois</li><li>Demandez qui d'autre doit être là</li><li>Terminez par un merci simple</li></ul><br><em>Conseil : un rendez-vous confirmé à l'oral est un rendez-vous tenu. Ne raccrochez pas sans avoir répété la date.</em>"}
{"code": "fin-devis", "type": "conclusion", "title": "Devis demandé", "position": 2, "content": "<p>Très bien, {contact}. Je vous prépare le devis écrit et je vous l'envoie aujourd'hui ou demain au plus tard.</p><ul><li>Confirmez l'adresse mail pendant l'appel</li><li>Annoncez ce que le devis contiendra, en deux points</li><li>Dites clairement qu'il est sans engagement</li></ul><br><em>Conseil : « sans engagement » rassure à ce moment précis. Placez-le dans la dernière phrase.</em>"}
{"code": "fin-rappeler", "type": "conclusion", "title": "À rappeler", "position": 3, "content": "<p>Pas de souci, {contact}. Je vous rappelle la semaine prochaine à la même heure. Si ce n'est pas bon, un mail suffit pour décaler.</p><ul><li>Notez la date dans le journal avant de raccrocher</li><li>Laissez le choix : « si ce n'est pas bon » évite le rendez-vous forcé</li></ul><br><em>Conseil : une date de rappel sans note est une promesse perdue. Le journal a un champ prévu pour ça.</em>"}
{"code": "fin-pas_interesse", "type": "conclusion", "title": "Pas intéressé", "position": 4, "content": "<p>C'est très clair, {contact}, et je vous remercie de me l'avoir dit franchement.</p><ul><li>Ne cherchez pas à convaincre : un non clair se respecte</li><li>Demandez simplement si vous pouvez reprendre contact dans un an</li><li>Remerciez et raccrochez</li></ul><br><em>Conseil : un refus bien accueilli revient souvent un an plus tard. Un refus forcé ne revient jamais.</em>"}
{"code": "fin-injoignable", "type": "conclusion", "title": "Injoignable", "position": 5, "content": "<p>Vous êtes bien sur la messagerie de {entreprise_client}. Je rappellerai un autre jour.</p><ul><li>N'insistez pas plus de deux fois sur la même semaine</li><li>Essayez un autre créneau : tôt le matin ou en fin de journée</li></ul><br><em>Conseil : un répondeur saturé ne sert à rien. Un message court et daté, oui.</em>"}
{"code": "fin-ne_plus_rappeler", "type": "conclusion", "title": "Ne plus rappeler", "position": 6, "content": "<p>Je comprends très bien, {contact}. Je note que vous ne souhaitez plus être contacté et je vous en fais la promesse : vous ne recevrez plus d'appel de notre part.</p><ul><li>Ne cherchez pas à obtenir une dernière information</li><li>Confirmez que l'opposition est enregistrée</li><li>Remerciez pour le temps accordé et raccrochez</li></ul><br><em>Conseil : cette phrase doit être dite lentement et sans amertume. C'est aussi ce qui protège l'entreprise.</em>"}

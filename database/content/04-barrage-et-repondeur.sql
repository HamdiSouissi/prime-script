-- =====================================================================
--  Prime Script — Contenu prêt à l'emploi (4/12) : Passer le standard et messages sur répondeur
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

{"code": "barr-1", "type": "barrage", "title": "Le bon interlocuteur, sans détour", "position": 10, "content": "Bonjour, {prenom_commercial} de Prime Vision Solutions. Je cherche la personne qui s'occupe de la partie site Internet chez {entreprise_client}. Vous savez qui c'est ?\n<br><em>Conseil : demandez le nom, pas la fonction. Un nom crée un lien direct pour la suite de l'appel.</em>"}
{"code": "barr-2", "type": "barrage", "title": "Le message clair", "position": 20, "content": "Bonjour, {prenom_commercial} de Prime Vision Solutions. Je vous appelle au sujet du site de {entreprise_client}. Vous pouvez me passer la direction, s'il vous plaît ?\n<br><em>Conseil : dites la raison de l'appel en une phrase. Un standard bloque ce qu'il ne comprend pas.</em>"}
{"code": "barr-3", "type": "barrage", "title": "Quand le barrage demande « c'est à quel sujet ? »", "position": 30, "content": "Bien sûr. Il s'agit de leur présence sur Internet : comment leurs clients les trouvent aujourd'hui. C'est un appel de deux minutes, sans vente forcée.\n<br><em>Conseil : « sans vente forcée » change le ton de l'échange. Le standard devient un allié.</em>"}
{"code": "barr-4", "type": "barrage", "title": "Le dirigeant absent", "position": 40, "content": "Merci. Du coup, il vaut mieux que je le rappelle. C'est plus simple pour lui à quel moment : le matin ou en fin de journée ?\n<br><em>Conseil : demandez le meilleur créneau, jamais l'heure à laquelle il est là « en général ».</em>"}
{"code": "barr-5", "type": "barrage", "title": "Le refus poli du standard", "position": 50, "content": "Je comprends, vous faites votre travail. Juste une chose : pouvez-vous me dire à qui envoyer un mail plutôt qu'appeler ? Je ne vais pas insister par téléphone.\n<br><em>Conseil : proposer le mail désamorce la méfiance et laisse une trace écrite de votre passage.</em>"}
{"code": "barr-6", "type": "barrage", "title": "Le retour après un premier échange", "position": 60, "content": "Bonjour, {prenom_commercial} de Prime Vision Solutions. J'ai déjà échangé avec {contact} au sujet de leur site, je rappelle comme convenu. Il est disponible ?\n<br><em>Conseil : citez le nom du contact dès la première phrase. C'est ce qui fait passer du statut d'inconnu à celui d'attendu.</em>"}
{"code": "repond-1", "type": "repondeur", "title": "Répondeur — premier appel", "position": 10, "content": "Bonjour, c'est {prenom_commercial} de Prime Vision Solutions. Je vous appelle au sujet du site de {entreprise_client}. Je rappelle demain matin. Bonne journée.\n<br><em>Conseil : 15 secondes, pas une de plus. Ne dites pas tout, le but est qu'on vous rappelle.</em>"}
{"code": "repond-2", "type": "repondeur", "title": "Répondeur — après un échange", "position": 20, "content": "Bonjour, {prenom_commercial} de Prime Vision Solutions. Je reviens vers vous comme convenu pour faire le point sur votre projet. Vous avez mon numéro. À bientôt.\n<br><em>Conseil : laissez le numéro une seule fois et lentement. Un numéro répété trois fois sonne comme un message publicitaire.</em>"}

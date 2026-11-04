-- =====================================================================
--  Prime Script — Contenu prêt à l'emploi (2/12) : Accroches génériques (secours)
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

{"code": "acc-premier-appel-1", "type": "accroche", "stage": "premier-appel", "title": "Ouverture — la question avant le produit", "position": 10, "content": "Bonjour {contact}, {prenom_commercial} de Prime Vision Solutions. Je ne vous appelle pas pour vous vendre un site.\n\nJe voudrais juste comprendre comment vous recevez vos demandes de devis aujourd'hui. Vous avez deux minutes ?\n<br><em>Conseil : cette accroche marche partout. Elle désarme la méfiance parce qu'elle annonce d'emblée qu'on ne vend rien.</em>"}
{"code": "acc-premier-appel-2", "type": "accroche", "stage": "premier-appel", "title": "Ouverture — le temps perdu", "position": 20, "content": "Bonjour {contact}, {prenom_commercial} de Prime Vision Solutions.\n\nUne question rapide : dans une semaine normale, combien de temps passez-vous à des tâches qui n'ont rien à voir avec votre métier ?\n<br><em>Conseil : laissez le silence après la question. La première réponse est souvent la vraie.</em>"}
{"code": "acc-premier-appel-3", "type": "accroche", "stage": "premier-appel", "title": "Ouverture — le client qui cherche", "position": 30, "content": "Bonjour {contact}, {prenom_commercial} de Prime Vision Solutions.\n\nQuand un client vous cherche sur Internet en ce moment, qu'est-ce qu'il trouve en premier ?\n<br><em>Conseil : notez la réponse mot pour mot. Elle vous servira trois phrases plus loin.</em>"}
{"code": "acc-premier-appel-4", "type": "accroche", "stage": "premier-appel", "title": "Ouverture — le rendez-vous direct", "position": 40, "content": "Bonjour {contact}, {prenom_commercial} de Prime Vision Solutions. On aide les TPE et PME à recevoir plus de demandes qualifiées.\n\nJe ne vais pas vous prendre plus de deux minutes maintenant. Est-ce qu'un point de quinze minutes cette semaine serait utile ?\n<br><em>Conseil : ne proposez jamais « quand vous voulez ». Proposez deux créneaux précis.</em>"}
{"code": "acc-relance-1", "type": "accroche", "stage": "relance", "title": "Relance — le rappel du contexte", "position": 10, "content": "{contact}, bonjour, {prenom_commercial} de Prime Vision Solutions. Je vous rappelle comme convenu au sujet de votre site.\n\nVous m'aviez dit que le sujet était plutôt le budget. On peut en parler très concrètement.\n<br><em>Conseil : rappelez la date exacte du premier échange. « Mardi dernier » prouve que vous avez noté.</em>"}
{"code": "acc-relance-2", "type": "accroche", "stage": "relance", "title": "Relance — la question simple", "position": 20, "content": "Bonjour {contact}, {prenom_commercial} de Prime Vision Solutions.\n\nJe ne vous appelle pas pour vous relancer, juste pour savoir si le sujet est toujours d'actualité de votre côté.\n<br><em>Conseil : cette formulation libère le prospect. Un « non » franc vaut mieux qu'un « peut-être » qui traîne.</em>"}
{"code": "acc-relance-3", "type": "accroche", "stage": "relance", "title": "Relance — le devis", "position": 30, "content": "Bonjour {contact}, {prenom_commercial} de Prime Vision Solutions.\n\nAvez-vous pu regarder le devis ? Si un point n'est pas clair, je préfère vous l'expliquer de vive voix.\n<br><em>Conseil : les trois questions de relance sont presque toujours le prix, le délai et la partie technique.</em>"}
{"code": "acc-relance-4", "type": "accroche", "stage": "relance", "title": "Relance — la porte ouverte", "position": 40, "content": "Bonjour {contact}, {prenom_commercial} de Prime Vision Solutions.\n\nJe vous avais laissé un message. Si le moment n'est pas bon, dites-le moi franchement et je vous rappelle quand ça vous arrange.\n<br><em>Conseil : accepter un report n'est pas perdre la vente. C'est éviter d'être celui qui insiste.</em>"}
{"code": "acc-closing-1", "type": "accroche", "stage": "closing", "title": "Closing — la dernière question", "position": 10, "content": "Bonjour {contact}, {prenom_commercial} de Prime Vision Solutions. On y est presque.\n\nIl reste une question à trancher : c'est plutôt le délai de démarrage ou le paiement en plusieurs fois ?\n<br><em>Conseil : en proposant deux options concrètes, vous transformez un « oui ou non » en « ceci ou cela ».</em>"}
{"code": "acc-closing-2", "type": "accroche", "stage": "closing", "title": "Closing — le récapitulatif", "position": 20, "content": "Bonjour {contact}, {prenom_commercial} de Prime Vision Solutions.\n\nJe vous résume en trois phrases : ce qu'on fait, pour combien, et à partir de quand. Vous me dites si j'ai bon.\n<br><em>Conseil : terminez le récapitulatif par une question fermée. « C'est bien ça ? » ouvre la décision.</em>"}
{"code": "acc-closing-3", "type": "accroche", "stage": "closing", "title": "Closing — la signature", "position": 30, "content": "Bonjour {contact}, {prenom_commercial} de Prime Vision Solutions.\n\nLe devis est prêt à signer. Je peux vous l'envoyer maintenant, ou vous le déposer en main propre si vous préférez.\n<br><em>Conseil : « envoyer maintenant » est plus simple que « quand vous aurez le temps ».</em>"}
{"code": "acc-closing-4", "type": "accroche", "stage": "closing", "title": "Closing — la porte de sortie honorable", "position": 40, "content": "Bonjour {contact}, {prenom_commercial} de Prime Vision Solutions.\n\nSi le projet n'est pas pour maintenant, dites-le moi : je clôture le dossier proprement, sans vous relancer.\n<br><em>Conseil : cette phrase fait revenir plus de clients qu'une relance forcée. Elle vous grandit.</em>"}

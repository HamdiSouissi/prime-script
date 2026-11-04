-- =====================================================================
--  Prime Script — Contenu prêt à l'emploi (7/12) : Preuves et crédibilité (par service)
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

{"code": "prv-site-web", "type": "preuve", "service": "site-web", "title": "Preuves — site web", "content": "<p>Trois choses vérifiables, sans chiffre inventé :</p><ul><li>Notre prix de départ est public : 790 € HT pour un site une page, 1 490 € HT pour un site professionnel. Vous pouvez le vérifier sur notre site avant même de nous appeler.<br><em>Conseil : être vérifiable est plus fort qu'être convaincant.</em></li><li>Le devis est écrit, détaillé, avec un prix ferme, et sans engagement. Rien ne démarre avant votre accord.<br><em>Conseil : trois mots à retenir : écrit, ferme, sans engagement.</em></li><li>À la livraison, vous repartez avec le code source, les contenus et tous les accès. Le site vous appartient vraiment.<br><em>Conseil : c'est la réponse à « et si je veux changer de prestataire ? ».</em></li></ul>"}
{"code": "prv-application", "type": "preuve", "service": "application", "title": "Preuves — application sur mesure", "content": "<p>Trois choses vérifiables, sans chiffre inventé :</p><ul><li>Les applications sur mesure démarrent à 2 900 € HT. Une application mobile iOS et Android démarre à 3 900 € HT, et une application mobile complète à 7 900 € HT.<br><em>Conseil : ces tarifs sont publics. Citez-les de mémoire, sans hésiter.</em></li><li>Chaque étape est validée avant la suivante : maquettes, prototype, puis développement. Vous voyez le projet avancer.<br><em>Conseil : une livraison par étapes rassure plus qu'une promesse de délai.</em></li><li>Sur notre site, le simulateur d'application mobile donne un ordre de prix immédiat, sans engagement.<br><em>Conseil : envoyez le lien : le prospect joue avec l'outil, il se projette.</em></li></ul>"}
{"code": "prv-automatisation-ia", "type": "preuve", "service": "automatisation-ia", "title": "Preuves — automatisation IA", "content": "<p>Trois choses vérifiables, sans chiffre inventé :</p><ul><li>Les automatisations démarrent à 1 500 € HT, les assistants connectés à vos documents à 2 490 € HT. Le devis final dépend uniquement de ce qu'on automatise.<br><em>Conseil : un tarif de départ annoncé évite la question « c'est combien » à contretemps.</em></li><li>Nous commençons par un audit du temps perdu : quelles tâches reviennent, combien de fois par semaine. On ne vend pas d'automatisation sans avoir mesuré.<br><em>Conseil : c'est la méthode, et elle est vraie : elle est décrite sur notre site.</em></li><li>Chaque automatisation a un contrôle humain avant l'envoi. Vous validez ce qui part.<br><em>Conseil : le mot « contrôle » désarme la peur de l'IA qui fait n'importe quoi.</em></li></ul>"}
{"code": "prv-chatbot-ia", "type": "preuve", "service": "chatbot-ia", "title": "Preuves — chatbot IA", "content": "<p>Trois choses vérifiables, sans chiffre inventé :</p><ul><li>Le chatbot démarre à 990 € HT. L'assistant connecté à vos documents et à votre agenda démarre à 2 490 € HT.<br><em>Conseil : 990 € HT est un point d'entrée accessible. Dites-le sans le minimiser.</em></li><li>Vous pouvez tester un assistant formé sur votre métier sur notre site : quatre démos, une par secteur, sans inscription.<br><em>Conseil : le meilleur argument est la démo. Proposez-la à la fin de chaque appel.</em></li><li>L'assistant ne répond que sur vos informations validées. Il ne promet jamais de prix et passe la main à un humain quand il faut.<br><em>Conseil : trois limites, dites dans cet ordre : c'est rassurant et honnête.</em></li></ul>"}

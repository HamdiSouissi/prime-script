-- =====================================================================
--  Prime Script — Contenu prêt à l'emploi (8/12) : Tarifs de départ et passage à la suite
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

{"code": "tar-site-web", "type": "tarif", "service": "site-web", "title": "Tarif — site web", "content": "<p>« Le point de départ, c'est <strong>790 € HT</strong>. C'est un tarif de départ, pas un prix au hasard : il correspond à un périmètre précis.</p><p><strong>Ce que ça comprend :</strong></p><ul><li>un site une page, prêt pour le mobile</li><li>le formulaire de contact qualifié</li><li>la mise en ligne et le nom de domaine</li></ul><p><strong>Ce que ça ne comprend pas :</strong></p><ul><li>un site professionnel de 6 pages démarre à 1 490 € HT</li><li>un site sur mesure, avec espace client ou blog, démarre à 2 900 € HT</li><li>l'hébergement et le nom de domaine sont chiffrés à part dans le devis, jamais cachés</li></ul><p><strong>Et le devis :</strong> écrit, détaillé, gratuit, sans engagement. Pour votre cas, je pense au forfait professionnel. Je vous l'envoie aujourd'hui, vous le lisez tranquillement.</p><br><em>Conseil : annoncez le prix sans vous excuser, puis taisez-vous. La première réaction n'est pas la décision.</em>"}
{"code": "tar-application", "type": "tarif", "service": "application", "title": "Tarif — application sur mesure", "content": "<p>« Le point de départ, c'est <strong>2900 € HT</strong>. C'est un tarif de départ, pas un prix au hasard : il correspond à un périmètre précis.</p><p><strong>Ce que ça comprend :</strong></p><ul><li>les maquettes et le prototype cliquable</li><li>le développement de la première version</li><li>la formation de vos équipes</li></ul><p><strong>Ce que ça ne comprend pas :</strong></p><ul><li>un site web métier complet démarre à 7 900 € HT</li><li>une application mobile iOS et Android démarre à 3 900 € HT</li><li>le paiement peut se faire en trois fois sur les projets</li></ul><p><strong>Et le devis :</strong> il est écrit, détaillé, gratuit et sans engagement. Le périmètre sera écrit noir sur blanc dans le devis, avec le prix exact. On peut commencer par un module seul, pour limiter le risque.</p><br><em>Conseil : annoncez le prix sans vous excuser, puis taisez-vous. La première réaction n'est pas la décision.</em>"}
{"code": "tar-automatisation-ia", "type": "tarif", "service": "automatisation-ia", "title": "Tarif — automatisation IA", "content": "<p>« Le point de départ, c'est <strong>1500 € HT</strong>. C'est un tarif de départ, pas un prix au hasard : il correspond à un périmètre précis.</p><p><strong>Ce que ça comprend :</strong></p><ul><li>l'audit des tâches répétitives</li><li>la mise en place de la première automatisation</li><li>la surveillance et les alertes en cas de panne</li></ul><p><strong>Ce que ça ne comprend pas :</strong></p><ul><li>les assistants connectés à vos documents démarrent à 2 490 € HT</li><li>les projets data démarrent à 1 900 € HT</li><li>la maintenance démarre à 49 € par mois, sans engagement</li></ul><p><strong>Et le devis :</strong> il est écrit, détaillé, gratuit et sans engagement. Avant de parler budget, on fait l'audit. Si l'automatisation ne se rentabilise pas, je vous le dirai franchement.</p><br><em>Conseil : annoncez le prix sans vous excuser, puis taisez-vous. La première réaction n'est pas la décision.</em>"}
{"code": "tar-chatbot-ia", "type": "tarif", "service": "chatbot-ia", "title": "Tarif — chatbot IA", "content": "<p>« Le point de départ, c'est <strong>990 € HT</strong>. C'est un tarif de départ, pas un prix au hasard : il correspond à un périmètre précis.</p><p><strong>Ce que ça comprend :</strong></p><ul><li>le chatbot installé sur votre site</li><li>les réponses aux questions fréquentes</li><li>l'historique des conversations</li></ul><p><strong>Ce que ça ne comprend pas :</strong></p><ul><li>l'assistant connecté à vos documents et à votre agenda démarre à 2 490 € HT</li><li>l'automatisation des tâches démarre à 1 500 € HT</li><li>la mise en service se fait en une à deux semaines pour le forfait de départ</li></ul><p><strong>Et le devis :</strong> il est écrit, détaillé, gratuit et sans engagement. 990 € HT, c'est le point de départ. Vous pouvez tester une démo gratuite avant même de décider. Ça vous dit que je vous envoie le lien ?</p><br><em>Conseil : annoncez le prix sans vous excuser, puis taisez-vous. La première réaction n'est pas la décision.</em>"}

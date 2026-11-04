# À vérifier — contenu prêt à l'emploi

Tout ce qui suit a été rédigé **sans inventer** : seules les informations publiques du site
Prime Vision Solutions et les éléments du prompt ont été utilisés. Les points ci-dessous
demandent une validation humaine avant la mise en service. Chaque ligne indique le fichier
concerné et ce qu'il faut confirmer.

## 1. Tarifs

| Où | Ce qui est écrit | À confirmer |
|----|------------------|-------------|
| `08-tarifs.sql` | Site web : **790 € HT** (une page, formulaire qualifié, mise en ligne, nom de domaine), 1 490 € HT (6 pages), 2 900 € HT (sur mesure) | conformité avec la page Tarifs au jour de la mise en ligne |
| `08-tarifs.sql` | Application : **2 900 € HT** (mobile 3 900 €, complète 7 900 €) | idem |
| `08-tarifs.sql` | Chatbot IA : **990 € HT** ; assistant connecté 2 490 € ; automatisation 1 500 € | idem |
| `08-tarifs.sql` | Maintenance : **49 €/mois** sans engagement | idem |
| partout | Formulation « à partir de … € HT » | vérifier qu'aucun texte n'annonce un prix ferme |

Les frais de tiers (hébergement, nom de domaine, licences) sont annoncés « chiffrés à part
dans le devis ». Aucun montant précis n'est donné : c'est volontaire.

## 2. Réalisations et preuves

| Où | Ce qui est écrit | À confirmer |
|----|------------------|-------------|
| `07-preuves.sql` | Les seuls noms cités sont **AUTOCARE CONNECT**, **ATELIER NOVA**, **RESTOPILOT** | accord du client pour citer ces noms à l'oral |
| `07-preuves.sql` | Outils cités : audit gratuit (score /100), simulateur de budget, démonstrations `/demo-ia`, `/configurateur-style`, `/simulateur-mobile` | vérifier que les trois démos sont toujours en ligne |
| `06-argumentaires.sql`, `07-preuves.sql` | Aucun chiffre de résultat (pas de « +30 % de devis », pas de nombre de clients) | à conserver : ne pas ajouter de chiffre non mesuré |

## 3. Engagements et délais

| Où | Ce qui est écrit | À confirmer |
|----|------------------|-------------|
| `01-presentations.sql` | « un seul interlocuteur du premier échange à la mise en ligne », « vous gardez la propriété du code et des accès » | engagements déjà publics sur le site : à maintenir |
| partout | Aucun délai de livraison chiffré | volontaire : les textes renvoient au devis écrit |
| `10-conclusions.sql` | « je vous envoie le devis aujourd'hui » | vérifier que l'équipe tient cet engagement le jour même |
| `10-conclusions.sql` | « je vous rappelle la semaine prochaine à la même heure » | formulation neutre ; la date réelle est injectée par l'écran de fin d'appel |

## 4. Informations internes à compléter

| Sujet | État actuel | Ce qu'il faut faire |
|-------|-------------|---------------------|
| Téléphone et e-mail | non cités dans le script (le commercial donne son prénom et « Prime Vision Solutions ») | ajouter le numéro si vous voulez qu'il soit dicté sur répondeur |
| Zone d'intervention | non précisée | à ajouter dans `04-barrage-et-repondeur.sql` si la prospection est limitée à une région |
| Horaires d'appel | non précisés (les textes conseillent « hors service » pour la restauration) | à aligner avec la réalité de l'équipe |
| Références clients détaillées | non détaillées (pas de témoignage, pas de citation) | à n'ajouter qu'avec l'accord écrit des clients |
| Garanties / certifications | aucune mention | ne rien ajouter sans document à l'appui |

## 5. Conformité démarchage téléphonique

- Le script impose, à chaque appel : **prénom + Prime Vision Solutions + raison de l'appel**.
- Le refus définitif dispose de sa propre objection (`11-objections.sql`, question
  « Arrêtez de m'appeler, ne me rappelez plus ») et d'une phrase de conclusion dédiée.
- Le résultat d'appel **« Ne plus rappeler »** marque l'appel (`calls.do_not_call = 1`),
  affiche un indicateur dans le journal, et l'application avertit au prochain appel
  sur la même entreprise.
- À vérifier avec votre juriste : la durée de conservation de ces oppositions et
  l'inscription éventuelle sur la liste d'opposition au démarchage (Bloctel).

## 6. Points de style volontairement neutres

- Aucun superlatif, aucune promesse de position sur Google (le site ne s'engage pas sur
  un classement).
- Les phrases restent courtes et orales ; les textes « à dire » sont séparés des
  « Conseil : … » qui sont destinés au commercial, pas au prospect.
- Les variables `{entreprise_client}`, `{contact}`, `{prenom_commercial}`, `{service}`,
  `{secteur}` et `{quand}` disparaissent proprement quand elles sont vides.

## 7. Ce qui a été contrôlé automatiquement

| Contrôle | Script | Résultat |
|----------|--------|----------|
| Les 48 combinaisons secteur × service × étape affichent les 7 blocs + des objections | `php tools/content-check.php` | 48/48 complètes |
| Expressions à éviter, doublons, variables inconnues, longueur à l'oral | `php tools/content-audit.php` | aucun blocage |
| Import rejouable sans doublon | `php tools/import-content.php` (deux fois) | 123 + 44 inchangés au second passage |
| Parcours complet (connexion → 5 écrans → journal → admin) | `bash tools/smoke-test.sh` | 48 assertions |

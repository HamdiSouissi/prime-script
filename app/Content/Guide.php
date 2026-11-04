<?php
declare(strict_types=1);

/**
 * Contenu du guide « Bien démarrer ».
 *
 * Tout le texte est ici, en français, dans l'ordre d'affichage : la page /guide
 * ne fait que le mettre en forme (aucun contenu à écrire dans la base).
 *
 * Pour modifier un passage : éditez ce fichier. Les commerciaux voient la
 * nouvelle version immédiatement, sans redéploiement de la base.
 */
return [
    'title'    => 'Bien démarrer',
    'subtitle' => 'Tout ce qu\'il faut savoir pour son premier appel chez Prime Vision Solutions.',

    // ------------------------------------------------------------------ Règles d'or
    'rules' => [
        'title' => 'Les règles d\'or',
        'intro' => 'Quatre principes qui font plus pour un appel que n\'importe quelle formule.',
        'items' => [
            [
                'icon'  => 'user',
                'title' => 'Se présenter clairement',
                'text'  => 'Prénom, Prime Vision Solutions, puis la raison de l\'appel. En dix secondes, la personne doit savoir qui vous êtes et pourquoi vous appelez.',
            ],
            [
                'icon'  => 'search',
                'title' => 'Écouter d\'abord',
                'text'  => 'Posez une question, puis taisez-vous. Le prospect doit parler plus que vous : c\'est là que se trouve la vraie demande.',
            ],
            [
                'icon'  => 'shield',
                'title' => 'Respecter un refus',
                'text'  => 'Un « non » clair se respecte. Vous remerciez, vous notez « Ne plus rappeler » et vous clôturez. Insister fait perdre le client et la réputation.',
            ],
            [
                'icon'  => 'check-circle',
                'title' => 'Ne jamais mentir',
                'text'  => 'Pas de fausse urgence, pas de résultat promis, pas de prix inventé. Les tarifs sont des tarifs de départ HT, le devis écrit est gratuit et sans engagement.',
            ],
        ],
    ],

    // ------------------------------------------------------------------ Structure d'appel
    'steps' => [
        'title' => 'La structure d\'un appel en 5 temps',
        'intro' => 'Un appel réussi suit toujours à peu près le même chemin. Environ deux minutes pour un premier contact.',
        'items' => [
            [
                'num'   => '1',
                'title' => 'Ouverture',
                'time'  => '15 à 20 secondes',
                'text'  => 'Présentation courte, puis une accroche adaptée au secteur. Terminez par une question ouverte : « C\'est aussi votre cas ? »',
                'tip'   => 'Souriez : ça s\'entend au téléphone.',
            ],
            [
                'num'   => '2',
                'title' => 'Découverte',
                'time'  => '45 secondes à 1 minute',
                'text'  => 'Trois ou quatre questions sur la façon dont le prospect reçoit ses demandes, et sur ce qui lui prend du temps.',
                'tip'   => 'Prenez une note par réponse : elles serviront à l\'argumentaire.',
            ],
            [
                'num'   => '3',
                'title' => 'Proposition',
                'time'  => '30 secondes',
                'text'  => 'Reprenez ses mots, puis présentez le service qui répond à son point le plus douloureux. Annoncez le tarif de départ, sans détour.',
                'tip'   => 'Un seul service à la fois. Vous pourrez compléter plus tard.',
            ],
            [
                'num'   => '4',
                'title' => 'Objections',
                'time'  => 'variable',
                'text'  => 'Accueillez l\'objection sans la contredire, posez une question pour comprendre, répondez en deux ou trois points, puis relancez.',
                'tip'   => 'Le panneau « Objections » est disponible sur chaque écran du parcours.',
            ],
            [
                'num'   => '5',
                'title' => 'Conclusion',
                'time'  => '20 secondes',
                'text'  => 'Proposez une petite étape concrète : audit gratuit, envoi d\'une démo, rendez-vous de quinze minutes. Notez le résultat dans le journal.',
                'tip'   => 'Une étape acceptée vaut mieux qu\'une promesse vague.',
            ],
        ],
    ],

    // ------------------------------------------------------------------ Erreurs
    'mistakes' => [
        'title' => 'Les 10 erreurs à éviter',
        'intro' => 'Elles coûtent des rendez-vous tous les jours. Toutes se corrigent facilement.',
        'items' => [
            'Parler de soi et de son agence avant d\'avoir posé une seule question.',
            'Réciter un argumentaire qui ne correspond pas au secteur du prospect.',
            'Employer des mots techniques (« SEO », « API », « LLM ») sans les expliquer.',
            'Annoncer un prix sans préciser qu\'il s\'agit d\'un tarif de départ HT.',
            'Promettre un résultat chiffré : « vous allez doubler vos demandes ».',
            'Créer une fausse urgence : « l\'offre expire ce soir ».',
            'Continuer à parler après un refus clair.',
            'Oublier de noter l\'appel dans le journal, ou de saisir la date de rappel.',
            'Rappeler un prospect qui a demandé à ne plus être contacté.',
            'Laisser un message sur répondeur de plus de vingt secondes.',
        ],
    ],

    // ------------------------------------------------------------------ Dialogue
    'dialogue' => [
        'title' => 'Un appel complet, en deux minutes',
        'intro' => 'Exemple avec un restaurant qui n\'a pas de site à jour. Les répliques sont courtes : c\'est normal, c\'est le rythme du téléphone.',
        'lines' => [
            ['who' => 'commercial', 'text' => 'Bonjour, Sonia de Prime Vision Solutions. Vous êtes bien chez Maison Tilleul ?'],
            ['who' => 'prospect',   'text' => 'Oui, c\'est moi.'],
            ['who' => 'commercial', 'text' => 'Je vous appelle parce que beaucoup de restaurants perdent des réservations le soir, quand personne ne décroche. Ça vous arrive aussi ?'],
            ['who' => 'prospect',   'text' => 'Ah oui, surtout le week-end. On a une page Google, mais pas de réservation en ligne.'],
            ['who' => 'commercial', 'text' => 'D\'accord. Et le soir, les gens vous appellent ou ils écrivent ?'],
            ['who' => 'prospect',   'text' => 'Ils appellent. Souvent pendant le service, c\'est compliqué.'],
            ['who' => 'commercial', 'text' => 'Je comprends. Ce qu\'on fait dans ce cas, c\'est un site simple avec la carte à jour et la réservation en ligne. Et un assistant qui répond aux questions d\'horaires.'],
            ['who' => 'prospect',   'text' => 'Et ça coûte combien ?'],
            ['who' => 'commercial', 'text' => 'Le site démarre à 790 euros HT. Ce prix comprend la conception, la mise en ligne et le formulaire de réservation. Le devis écrit est gratuit, sans engagement.'],
            ['who' => 'prospect',   'text' => 'D\'accord. Mais je préfère en parler à ma femme, on est deux.'],
            ['who' => 'commercial', 'text' => 'Bien sûr, c\'est normal. Je vous envoie un mail avec deux exemples de restaurants et le devis. Vous en parlez ensemble, et je vous rappelle jeudi à 11 h, ça vous va ?'],
            ['who' => 'prospect',   'text' => 'Oui, jeudi, ça marche.'],
            ['who' => 'commercial', 'text' => 'Parfait, je note. Bonne journée et à jeudi !'],
        ],
        'notes' => [
            'La présentation tient en une phrase : prénom, entreprise, raison de l\'appel.',
            'L\'accroche parle du vrai problème du secteur, pas du produit.',
            'Deux questions suffisent avant de proposer : inutile de tout savoir.',
            'Le tarif arrive seulement après que le besoin a été exprimé.',
            'L\'objection « je dois en parler » ne se combat pas : on propose une date.',
        ],
    ],

    // ------------------------------------------------------------------ Glossaire
    'glossary' => [
        'title' => 'Mini-glossaire, en mots simples',
        'intro' => 'Si le prospect pose la question, répondez avec ces mots-là.',
        'items' => [
            [
                'term'  => 'Site vitrine',
                'plain' => 'Un site qui présente l\'entreprise : ce qu\'elle fait, ses réalisations, comment la contacter. Il ne vend pas en ligne, il fait venir des demandes.',
            ],
            [
                'term'  => 'Application (web ou mobile)',
                'plain' => 'Un outil sur mesure qui sert à travailler : gérer des dossiers, des rendez-vous, des devis. Une application mobile s\'installe sur un téléphone.',
            ],
            [
                'term'  => 'Chatbot (ou assistant IA)',
                'plain' => 'Un petit assistant sur le site qui répond aux questions fréquentes et note la demande du visiteur, même quand l\'entreprise est fermée.',
            ],
            [
                'term'  => 'Automatisation',
                'plain' => 'Faire faire par un programme ce qu\'on fait à la main : recopier un formulaire dans un tableur, envoyer une relance, prévenir une équipe.',
            ],
            [
                'term'  => 'Devis',
                'plain' => 'Un document écrit qui fixe le prix exact et ce qui est inclus. Chez nous il est gratuit et sans engagement.',
            ],
            [
                'term'  => 'Audit',
                'plain' => 'Un diagnostic rapide. L\'audit de site gratuit donne une note sur 100, un avis sur la vitesse et le référencement, et un PDF à garder.',
            ],
            [
                'term'  => 'Référencement (être trouvé sur Google)',
                'plain' => 'Faire en sorte que le site apparaisse quand quelqu\'un cherche l\'activité dans sa ville. Ça se travaille dans le temps, ça ne se promet pas.',
            ],
            [
                'term'  => 'Nom de domaine et hébergement',
                'plain' => 'Le nom de domaine, c\'est l\'adresse du site (votrenom.fr). L\'hébergement, c\'est l\'endroit où le site est stocké. Les deux vous appartiennent.',
            ],
        ],
    ],

    // ------------------------------------------------------------------ Réflexes
    'reflexes' => [
        'title' => 'Après l\'appel, trois réflexes',
        'items' => [
            'Enregistrez l\'appel tout de suite, pendant que le contexte est frais : c\'est l\'affaire de vingt secondes.',
            'Choisissez le bon résultat : rendez-vous, devis demandé, à rappeler, pas intéressé, injoignable, ou ne plus rappeler.',
            'Envoyez le mail de suivi le jour même. Trois modèles prêts à copier vous attendent dans « Modèles de mail ».',
        ],
    ],
];

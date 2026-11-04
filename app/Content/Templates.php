<?php
declare(strict_types=1);

/**
 * Modèles de mail de suivi, à copier-coller après un appel.
 *
 * Les variables reprennent celles des blocs de speech :
 *   {contact}            nom du contact
 *   {entreprise_client}  nom de l'entreprise
 *   {prenom_commercial}  prénom du commercial connecté
 *   {service}            service évoqué pendant l'appel
 *
 * Le rendu remplace les variables et retire proprement les tournures
 * devenues vides (voir App\Services\SpeechRenderer::replaceVariables()).
 */
return [
    'title'    => 'Modèles de mail',
    'subtitle' => 'Trois messages courts, prêts à copier. Personnalisez une phrase, pas tout le texte.',

    'templates' => [
        [
            'key'     => 'premier-echange',
            'when'    => 'Après un premier échange',
            'subject' => 'Suite à notre échange — {entreprise_client}',
            'body'    => <<<'TXT'
Bonjour {contact},

Je vous remercie pour le temps accordé au téléphone.

Pour résumer ce que j'ai retenu : vous souhaitez {service} pour {entreprise_client}, avec un résultat simple — moins de demandes perdues et des réponses plus rapides.

Deux choses à votre disposition :
- l'audit gratuit de votre site actuel : une note sur 100, en quelques minutes ;
- le simulateur de budget, qui donne un ordre de prix immédiat, sans engagement.

Le devis écrit, lui, est gratuit et fixe le prix exact. Je peux vous l'envoyer dès que vous me confirmez le périmètre.

Bonne journée,
{prenom_commercial}
Prime Vision Solutions
TXT,
        ],
        [
            'key'     => 'relance',
            'when'    => 'Après une relance',
            'subject' => 'Votre devis — une question ?',
            'body'    => <<<'TXT'
Bonjour {contact},

Je reviens vers vous comme convenu.

Le devis pour {service} est prêt. Si un point mérite une explication, je suis disponible : le plus simple est souvent un appel de dix minutes pour parcourir les lignes ensemble.

Pour mémoire, le devis est sans engagement. Il précise le prix exact, ce qui est inclus et le calendrier prévu. Rien ne démarre avant votre accord écrit.

Dites-moi simplement le créneau qui vous arrange cette semaine.

Bien à vous,
{prenom_commercial}
Prime Vision Solutions
TXT,
        ],
        [
            'key'     => 'avant-closing',
            'when'    => 'Avant la décision finale',
            'subject' => 'Récapitulatif avant notre décision — {entreprise_client}',
            'body'    => <<<'TXT'
Bonjour {contact},

Avant notre prochain échange, voici le récapitulatif de ce qui est prévu pour {entreprise_client} :

- le périmètre : {service} ;
- le prix : celui du devis signé, sans supplément caché ;
- la livraison par étapes, avec votre validation à chaque palier ;
- la remise du code source et des accès à la livraison : tout vous appartient.

S'il reste une question ouverte, répondez à ce mail : j'y réponds le jour même.

Merci pour votre confiance,
{prenom_commercial}
Prime Vision Solutions
TXT,
        ],
    ],
];

# ADR 0004 : Bon affichable et PDF généré côté serveur

- **Statut** : accepté, implémenté pour le prototype
- **Date** : 2026-10-27

## Contexte

Le bon doit être lisible par l'établissement et l'assureur, avec les montants
figés au moment de l'approbation. Le prototype doit fournir un véritable
téléchargement PDF, tout en conservant une page HTML consultable et imprimable.

## Options étudiées

| Option | Avantages | Inconvénients |
|---|---|---|
| Page HTML avec règles d'impression du navigateur | Pas de dépendance supplémentaire; rendu proche du navigateur | Le navigateur crée le PDF; le résultat varie selon l'appareil |
| Bibliothèque de génération PDF côté serveur | Téléchargement PDF cohérent et indépendant du navigateur | Dépendance, compatibilité et mise en page à maintenir |
| Navigateur sans interface côté serveur | Rendu proche du navigateur utilisateur | Plus lourd à installer et héberger |

## Décision proposée

La page HTML dédiée, protégée par policy, reste consultable et imprimable. Un
téléchargement distinct génère le PDF côté serveur avec `barryvdh/laravel-dompdf`;
le rendu n'autorise ni ressources distantes, ni PHP, ni JavaScript. Le modèle
d'autorisation conserve une copie immuable des totaux au moment de l'approbation.
Chaque téléchargement est journalisé. Le PDF est généré à la demande et n'est
pas archivé sur le serveur.

## Conséquences

- Le PDF téléchargé est un vrai fichier `application/pdf`; l'impression
  navigateur demeure une solution de repli.
- Le bon généré reste un document de démonstration. La signature électronique,
  les codes de vérification, la conservation et la valeur contractuelle ne sont
  pas fournis et devront être décidés avec les assureurs concernés avant tout
  usage réel.

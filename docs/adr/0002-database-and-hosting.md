# ADR 0002 : Base de données et hébergement de démonstration

- **Statut** : à décider (remplir après `docs/hosting-checklist.md`)
- **Date** : à compléter

## Contexte

La démonstration doit être publique, avec des comptes de test, des documents
déposés par les utilisateurs et une base de données fiable pour les montants
et les statuts. Le choix de la base dépend surtout de ce que l'hébergeur
propose réellement, pas d'une préférence technique : MySQL 8 et PostgreSQL
gèrent tous deux les transactions et les contraintes dont le projet a besoin.

## Contraintes déjà connues

- PHP 8.4.1 ou plus chez l'hébergeur (voir ADR 0001).
- Stockage **persistant** des documents déposés (voir hosting-checklist.md).
- Aucune donnée réelle : la base de démonstration est remplie par des seeders.

## Options étudiées

| Option | Avantages | Inconvénients |
|---|---|---|
| MySQL | Déjà connu, très répandu chez les hébergeurs | À compléter selon la recherche |
| PostgreSQL | À compléter selon la recherche | Extension `pdo_pgsql` à activer en local, moins répandu en hébergement partagé |
| SQLite (développement et tests uniquement) | Aucune installation | Ne convient pas à la démonstration publique multi-utilisateurs |

## Décision

À compléter : base retenue, hébergeur retenu, date de vérification des offres.

## Conséquences

À compléter : ce que cela implique (extension locale à activer, variables
d'environnement, méthode de déploiement).

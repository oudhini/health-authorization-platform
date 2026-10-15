# Grille de choix de l'hébergeur

Objectif : trouver où déployer la démonstration publique. Les offres, versions
de PHP et limites gratuites changent souvent : **vérifie chaque point sur la
documentation actuelle de l'hébergeur, à la date du jour, et note la date.**

## Critères

| # | Critère | Pourquoi | Bloquant ? |
|---|---|---|---|
| 1 | PHP 8.4.1 ou plus disponible | Exigé par les dépendances installées | Oui |
| 2 | MySQL ou PostgreSQL disponible | Voir ADR 0002 | Oui |
| 3 | Le dossier public du site peut pointer sur `public/` | Sinon le code et le `.env` seraient accessibles depuis Internet | Oui |
| 4 | Variables d'environnement ou `.env` hors du dossier public | Les secrets ne doivent jamais être exposés | Oui |
| 5 | Stockage de fichiers **persistant** | Les documents déposés doivent survivre aux redémarrages. Certains hébergements gratuits effacent les fichiers à chaque redéploiement : à vérifier explicitement | Oui |
| 6 | Accès SSH ou méthode de déploiement claire (Git, FTP, pipeline) | Pour déployer et lancer les migrations | Oui |
| 7 | Composer utilisable (ou dossier `vendor/` envoyé) | Pour les dépendances | Oui |
| 8 | HTTPS | Données de démonstration, mais bonne pratique | Fortement souhaité |
| 9 | Tâches planifiées / files d'attente | Utile plus tard (notifications) | Non |
| 10 | Limites gratuites (veille du site, quotas, durée) | Une démo qui s'endort ou disparaît est mauvaise pour un recruteur | À noter |
| 11 | Conditions d'utilisation compatibles avec une démo de projet | Éviter une suspension | À lire |

## Tableau à remplir (une ligne par hébergeur envisagé)

| Hébergeur | Date de vérification | PHP max | BD | `public/` | Fichiers persistants | SSH/déploiement | Coût | Verdict |
|---|---|---|---|---|---|---|---|---|
| | | | | | | | | |
| | | | | | | | | |
| | | | | | | | | |

Compare au moins trois solutions de nature différente : hébergement mutualisé,
plateforme de déploiement (PaaS) et petit serveur virtuel (VPS).

## Après la recherche

1. Complète `docs/adr/0002-database-and-hosting.md`.
2. Passe son statut à « accepté ».
3. Commit : `docs: decide database and hosting (ADR 0002)`.

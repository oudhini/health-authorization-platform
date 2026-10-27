# ADR 0002 : Base de données et hébergement

- **Statut** : MySQL retenu pour le développement ; hébergement public non décidé
- **Date** : 2026-10-27 (comparaison initiale; décision non acceptée)

## Contexte

Le projet doit pouvoir être présenté en ligne avec des comptes et des données
fictifs, des documents téléversés et une base fiable pour les montants et les
statuts. Une éventuelle exploitation commerciale nécessitera une décision
d'hébergement distincte, après évaluation des exigences métier, juridiques,
de sécurité et de conformité. Le choix de la base dépend aussi de l'offre de
l'hébergeur : MySQL 8 et PostgreSQL gèrent tous deux les transactions et les
contraintes dont le projet a besoin.

## Contraintes déjà connues

- PHP 8.4.1 ou plus chez l'hébergeur (voir ADR 0001).
- Stockage **persistant** des documents déposés (voir hosting-checklist.md).
- Aucune donnée réelle : la base de démonstration est remplie par des seeders.

## Options étudiées

| Option | Avantages | Inconvénients |
|---|---|---|
| MySQL | Moteur prévu pour l'hébergement; Railway documente un service MySQL | L'environnement local utilise MariaDB 10.4.32; les écarts avec MySQL 8 restent à vérifier |
| PostgreSQL | Gère transactions et contraintes nécessaires | Extension `pdo_pgsql` à activer et migrations à vérifier; pas de raison actuelle de changer |
| SQLite (tests uniquement) | Aucune installation; tests automatisés rapides | Ne valide pas à lui seul les migrations et contraintes MySQL; pas retenu pour une démo multi-utilisateurs |
| Mutualisé OVHcloud | Offre web mutualisée simple à administrer | Les critères Laravel obligatoires du plan concret (PHP, moteur/version BD, racine `public/`, Composer, stockage privé persistant) restent à confirmer |
| Railway PaaS | Service MySQL, volumes, HTTPS automatique et déploiement piloté documentés | Image PHP/Laravel et stockage privé du dépôt non testés; plan Hobby à partir de 5 USD/mois, plus ressources consommées |
| Hetzner Cloud VPS | Contrôle du serveur, de Nginx, PHP, MySQL et de la racine web | Installation, mises à jour, surveillance, sauvegardes et restauration à gérer; volume attaché exclu des sauvegardes serveur |

## Décision provisoire

Le pilote Laravel `mysql` est retenu. La base locale existante utilise
MariaDB 10.4.32 (XAMPP), et non MySQL 8. Les tests automatisés habituels
restent configurés sur SQLite en mémoire.

La comparaison initiale de `docs/hosting-checklist.md` désigne Railway Hobby
comme candidat à un essai, pas comme choix accepté. Avant toute décision, il
faut confirmer le coût total acceptable et la région, construire et tester
l'image avec PHP 8.4, vérifier que les documents restent privés et persistants
après redéploiement, puis tester une restauration. Aucune offre n'est achetée
et aucun déploiement public n'est autorisé par cette recommandation.

## Conséquences

- PHP doit disposer de l'extension `pdo_mysql`.
- `.env.example` fournit les variables de connexion MySQL ; les identifiants
  locaux restent dans `.env`, qui ne doit pas être commité.
- Les tests sur SQLite en mémoire ne remplacent pas la vérification des
  migrations et des contraintes sur le moteur d'hébergement. Le 2026-10-27,
  les 18 migrations ont été confirmées sur la base MariaDB locale; une
  sauvegarde a été créée hors du dépôt avant toute autre opération.
- Toutes les migrations et les 168 tests (1 995 assertions) ont réussi sur
  une base MariaDB 10.4.32 isolée. Cela vérifie le moteur local, mais ne
  constitue pas une validation MySQL 8.
- La méthode de déploiement, le stockage persistant des documents, le coût et
  la restauration restent à valider avec l'offre choisie.
- Cette comparaison concerne uniquement une démo avec des données fictives;
  elle ne vaut pas décision pour une exploitation commerciale ou des données
  de santé réelles.

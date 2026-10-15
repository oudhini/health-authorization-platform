# ADR 0001 : Choix du framework et de la forme de l'application

- **Statut** : proposé (passe à « accepté » quand tu l'as relu et validé)
- **Date** : 2026-10-14

## Contexte

Le projet est développé et maintenu par une seule personne. Il doit être
terminé, testé, déployé et présentable dans un portfolio. La logique métier
centrale (calcul de couverture, cycle de vie d'une demande, droits par rôle)
doit pouvoir être testée sans interface ni base de données.

## Options étudiées

| Option | Avantages | Inconvénients |
|---|---|---|
| Laravel en monolithe, interface rendue côté serveur (Blade) | Un seul projet, outils intégrés (authentification, validation, autorisations, tests, migrations), déjà connu | Interface moins dynamique |
| Laravel (API) + application séparée (SPA) | Séparation nette, API réutilisable | Deux projets, deux déploiements, plus de risque de ne pas finir |
| Autre framework | Aucun avantage identifié pour ce projet | Coût d'apprentissage élevé |

## Décision

- Framework : **Laravel 13**, version courante au démarrage du projet.
  Les dépendances installées exigent **PHP 8.4.1 ou plus** (constaté à
  l'installation, le 2026-10-14). Environnement de développement : PHP 8.4.
- Forme : **monolithe**, interface rendue côté serveur (Blade).
- Logique métier dans `app/Domain/`, en PHP pur, indépendante des
  contrôleurs, des vues et de la base de données.
- Outils standard du framework : validation, politiques d'autorisation,
  migrations, protection CSRF.
- Tests : **PHPUnit**, fourni par défaut avec Laravel, sans dépendance
  supplémentaire.
- Authentification : décision reportée à un ADR dédié, au moment
  d'implémenter les rôles (phase 2).
- Base de données et hébergement : décision séparée, dans l'ADR 0002.

## Conséquences

- Positif : un seul dépôt, un seul déploiement, peu de dépendances.
- Positif : le moteur de calcul et le cycle de vie se testent sans base de données.
- Négatif : interface simple et classique, sans interactions en temps réel.
- Contrainte : l'hébergeur de démonstration doit proposer PHP 8.4.1 ou plus.
- À revoir si : un client externe (application mobile) doit consommer une API.

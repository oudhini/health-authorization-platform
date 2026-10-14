# ADR 0001 : Choix du framework et de la forme de l'application

- **Statut** : proposé (à passer à « accepté » après relecture)
- **Date** : 2026-10-14

## Contexte

Le projet est développé et maintenu par une seule personne. Il doit être
terminé, testé, déployé et présentable dans un portfolio. La logique métier
centrale (calcul de couverture, cycle de vie d'une demande, droits par rôle)
doit pouvoir être testée sans interface.

## Options étudiées

| Option | Avantages | Inconvénients |
|---|---|---|
| Laravel en monolithe, interface rendue côté serveur (Blade) | Un seul projet, outils intégrés (authentification, validation, autorisations, tests, migrations), déjà connu | Interface moins dynamique |
| Laravel (API) + application séparée (SPA) | Séparation nette, API réutilisable | Deux projets à maintenir, deux déploiements, plus de risque de ne pas finir |
| Autre framework | — | Aucun avantage identifié pour ce projet, coût d'apprentissage élevé |

## Décision

- Framework : **Laravel 13**, version courante au démarrage du projet. Elle
  exige PHP 8.3 ou plus (vérifié sur la documentation officielle le
  2026-10-14). L'environnement de développement doit donc être mis à jour
  avant l'installation, et l'hébergeur de démonstration doit proposer PHP 8.3
  ou plus.
- Forme : **monolithe**, avec l'interface rendue côté serveur (Blade).
- Logique métier dans des classes dédiées (services), indépendantes des
  contrôleurs et des vues, pour pouvoir les tester seules et les exposer plus
  tard dans une API si nécessaire.
- Outils standard du framework : validation, politiques d'autorisation,
  migrations, protection CSRF.
- Base de données : décision séparée, dans l'ADR 0002.

## Conséquences

- Positif : un seul dépôt, un seul déploiement, peu de dépendances.
- Positif : le moteur de calcul est testable sans base de données ni interface.
- Négatif : l'interface sera simple et classique, sans interactions temps réel.
- À faire : documenter l'API dans une étape ultérieure, si elle entre dans le périmètre.
- À faire : mettre PHP à jour sur le poste de développement (8.2 actuellement).
- À revoir si : un client externe (application mobile) doit consommer l'API.
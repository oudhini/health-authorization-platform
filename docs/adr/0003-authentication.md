# ADR 0003 : Authentification par sessions, sans kit de démarrage

- **Statut** : proposé (implémenté dans le lot 2, à passer à « accepté » après relecture)
- **Date** : 2026-10-16

## Contexte

L'application est un système fermé : les comptes (agents d'établissement,
gestionnaires assureur, administrateur) sont créés par l'équipe ou par les
seeders, jamais par les visiteurs. Il faut connecter, déconnecter, limiter les
tentatives et tracer les connexions. Je n'ai pas pu consulter la documentation
officielle de Laravel 13 sur les kits de démarrage : choisir un kit sans l'avoir
lue serait prendre une dépendance à l'aveugle.

## Options étudiées

| Option | Avantages | Inconvénients |
|---|---|---|
| Authentification par sessions écrite avec les outils intégrés de Laravel (`Auth`, gardes, limiteur de débit) | Aucune dépendance, comportement entièrement lisible et testé, adapté à un système fermé | Pas de réinitialisation de mot de passe, pas de double authentification |
| Kit de démarrage officiel | Fonctions supplémentaires prêtes à l'emploi | Interface et dépendances imposées, contenu non vérifié ici, beaucoup de code inutile pour un système sans inscription |
| Jetons d'API | Adapté à une API publique | Inutile : l'interface est rendue côté serveur (ADR 0001) |

## Décision

- Sessions Laravel, connexion par e-mail et mot de passe, `Auth::attempt`.
- Régénération de l'identifiant de session après connexion, invalidation à la
  déconnexion.
- Limitation à 5 tentatives par minute et par couple e-mail et adresse (`throttle:login`).
- Message d'erreur unique : on ne révèle jamais si l'e-mail existe.
- Pas d'inscription publique. Les comptes viennent des seeders.
- Rôles portés par le compte (`UserRole`) et contrôlés par un middleware, puis
  affinés par des politiques d'autorisation.
- Connexions réussies, échouées et déconnexions écrites dans le journal d'audit.

## Conséquences

- Hors périmètre du MVP : réinitialisation de mot de passe, double
  authentification, verrouillage prolongé des comptes.
- Les mots de passe des comptes de démonstration viennent de la variable
  `DEMO_PASSWORD` (le seeder refuse de s'exécuter hors développement sans elle).
- À réévaluer avant une mise en service commerciale ou le traitement de données réelles.

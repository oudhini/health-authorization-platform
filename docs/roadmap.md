# Feuille de route

Règles de travail, valables pour toutes les phases :

1. **Les tests avant le code** pour la logique métier.
2. **Un changement de comportement passe par le document avant le code** : si
   tu veux ajouter un statut, une règle ou un écran, mets d'abord
   `docs/requirements.md` à jour (ou note l'idée dans « Idées pour plus tard »).
3. **Un commit = une intention**, au format Conventional Commits, en anglais.
4. Aucun secret, aucune donnée réelle dans le dépôt.
5. Avant chaque `git push` : `php artisan test` doit passer.

## Phase 1 : cœur métier (le pack fourni couvre cette phase)

| # | Tâche | Fait quand |
|---|---|---|
| 1.1 | Squelette Laravel commité, tests d'exemple verts | `php artisan test` passe |
| 1.2 | Calculateur de couverture et ses tests | Les 5 cas A à E passent |
| 1.3 | Cycle de vie des demandes et ses tests | Les 7 transitions autorisées passent, les 29 autres sont refusées |
| 1.4 | ADR 0001 relu et passé à « accepté » | Statut modifié et commité |
| 1.5 | Entretien terrain (`docs/field-questions.md`) | Réponses Q1, Q2, Q3, Q4 notées, section 6 mise à jour si besoin |
| 1.6 | Recherche d'hébergeur (`docs/hosting-checklist.md`) puis ADR 0002 | ADR 0002 « accepté » |

## Phase 2 : base de données et accès

| # | Tâche | Fait quand |
|---|---|---|
| 2.1 | ADR 0003 sur l'authentification (kits officiels de Laravel ou solution maison). **Vérifie dans la documentation officielle de Laravel 13 ce qui est proposé aujourd'hui** : je ne l'ai pas pu consulter. | ADR écrit |
| 2.2 | Migrations des entités de la section 4, dans l'ordre des dépendances : `organizations`, `users` (ajout du rôle et de l'organisation), `subscribers`, `policyholders`, `beneficiaries`, `policies`, `medical_acts`, `official_tariffs`, `authorization_requests`, `authorization_items`, `authorizations`, `documents`, `audit_logs` | `php artisan migrate:fresh` réussit, et `php artisan migrate:rollback` aussi |
| 2.3 | Contraintes en base : clés étrangères, montants entiers positifs, statut limité aux valeurs de `RequestStatus`, index justifiés par une requête réelle | Revue de chaque migration |
| 2.4 | Modèles Eloquent avec le cast du statut vers `RequestStatus` | Un test crée une demande et relit son statut |
| 2.5 | Seeders de **données entièrement fictives** : 1 établissement, 1 assureur, comptes de démonstration, quelques bénéficiaires, polices, actes et tarifs | `php artisan migrate:fresh --seed` donne une démo utilisable |
| 2.6 | Politiques d'autorisation (`php artisan make:policy`) pour chaque règle de `docs/actors-and-roles.md` | Un test par ligne du tableau des droits |
| 2.7 | Test d'isolation entre organisations | Un `facility_agent` ne lit jamais la demande d'un autre établissement, même avec l'identifiant |
| 2.8 | Authentification et rôles | Connexion, déconnexion, limitation des tentatives, tests |

Commandes utiles (stables dans Laravel) : `php artisan make:migration`,
`make:model Nom -m`, `make:policy`, `make:seeder`, `make:test`, `route:list`.

## Phase 3 : parcours complets

| # | Tâche | Fait quand |
|---|---|---|
| 3.1 | Action « envoyer une demande » : vérifie les conditions de la section 5.2 puis utilise `RequestStatus::transitionTo` | Tests : succès et chaque refus (aucune ligne, police inactive, document manquant) |
| 3.2 | Action « décider » (approuver, refuser, demander des informations) | Tests par rôle |
| 3.3 | Création de la demande avec lignes d'actes ; calcul via `CoverageCalculator` à partir du tarif officiel **copié** sur la ligne | Les montants de la ligne correspondent aux cas de référence |
| 3.4 | Génération du bon à l'approbation (numéro, validité de 7 jours, dates stockées) | Test : un bon approuvé n'est plus modifiable |
| 3.5 | Dépôt et téléchargement sécurisés de documents (section 8.2) | Tests : type, taille, accès refusé à un autre établissement |
| 3.6 | PDF du bon. **Choisis une bibliothèque en vérifiant qu'elle est maintenue et compatible avec Laravel 13**, et consigne le choix dans un ADR | PDF aux montants corrects |
| 3.7 | Journal d'audit (section 8.3) | Chaque action listée écrit une entrée |
| 3.8 | Écrans : liste, détail, formulaire, états de chargement, vide et erreur, affichage mobile | Parcours manuel complet des deux workflows |

## Phase 4 : qualité et mise en ligne

| # | Tâche | Fait quand |
|---|---|---|
| 4.1 | Intégration continue (`optional/.github/workflows/ci.yml` du pack) | Coche verte sur GitHub |
| 4.2 | Formateur et analyse statique, intégrés à la CI | Aucune erreur |
| 4.3 | Déploiement selon l'ADR 0002 ; HTTPS ; variables d'environnement | URL publique avec comptes de démonstration |
| 4.4 | README final : présentation, capture d'écran, comment lancer, comment tester, liens vers `docs/` | Un inconnu peut lancer le projet seul |
| 4.5 | Dépôt passé en public, après relecture : aucune donnée réelle, aucun secret, historique propre | Relecture faite |
| 4.6 | Vidéo de démonstration de 3 à 5 minutes | Fichier ou lien |
| 4.7 | Critères de la section 9 de `docs/requirements.md` cochés un par un | 12 sur 12 |

## Idées pour plus tard (hors MVP)

Écris ici toute idée qui change le comportement du système, sans la coder.

- Claims et facturation.
- Circuit des médicaments.
- Correction d'un bon approuvé par nouvelle version.
- Accès en lecture du patient à son bon.

# Feuille de route

L'objectif est de faire évoluer le prototype existant vers un produit qui
réponde à un problème opérationnel réel. Le code qui fonctionne avec des
données fictives est une base, pas une preuve suffisante de l'adéquation métier
ni de l'aptitude à traiter de vraies données.

## Règles de travail

1. Valider les règles métier avec les personnes concernées avant de les
   présenter comme universelles ou de les automatiser.
2. Un changement de comportement est d'abord décrit dans
   `docs/requirements.md`, puis codé et testé.
3. Un commit correspond à une intention et suit Conventional Commits, en
   anglais.
4. Aucune donnée réelle ni aucun secret dans le dépôt ou dans les tests.
5. Avant chaque push, les tests doivent passer.

## État de départ vérifié

- Le prototype gère le parcours simulé de demande de prise en charge :
  sélection d'un assureur partenaire, recherche d'un bénéficiaire, choix d'une
  police, calcul, documents, transmission, décision et bon imprimable.
- Les demandes d'informations et les réponses de l'établissement (message,
  nouveau document ou les deux) sont conservées dans l'historique du dossier.
- Les tests automatisés passent sur SQLite en mémoire; un scénario
  établissement-assureur a aussi été parcouru sur MySQL local.
- Il n'y a pas d'intégration à un assureur ou établissement réel, ni de
  validation multi-acteurs documentée du workflow.
- L'administration des organisations et comptes, le catalogue commun et
  l'historique des tarifs, ainsi que l'édition contrôlée des brouillons sont
  implémentés et couverts par des tests automatisés.
- Le socle fonctionnel MVP précède le retravail visuel du front. L'hébergement
  reste reporté jusqu'à ce que le socle soit jugé suffisamment solide, comme
  indiqué dans `docs/status.md`; aucune mise en ligne n'est nécessaire pour
  poursuivre le travail fonctionnel.
- Claims et facturation sont reportés à la V2. Le circuit des médicaments
  reste hors du premier workflow.

## Phase 1 : valider le problème et le workflow

| # | Tâche | Fait quand |
|---|---|---|
| 1.1 | Distinguer les faits observés, les hypothèses et les décisions produit dans les exigences | Les règles non confirmées sont clairement signalées |
| 1.2 | Recueillir des retours sans données patients auprès d'agents d'établissement | Les étapes, irritants, pièces et exceptions sont documentés |
| 1.3 | Recueillir si possible le point de vue d'un gestionnaire d'assureur | La réception, l'instruction et la décision sont comprises des deux côtés |
| 1.4 | Confirmer les règles financières et documents exigés par type de demande | Les exemples et cas limites sont écrits et vérifiables |
| 1.5 | Définir des indicateurs de valeur : complétude au premier envoi, relances, temps de préparation et de décision | Méthode de mesure définie; aucune amélioration n'est revendiquée sans mesure |

## Phase 2 : fermer les écarts du workflow V1

| # | Tâche | Fait quand |
|---|---|---|
| 2.1 | Comparer chaque étape confirmée au prototype et ajuster le cahier des charges | Chaque exigence est liée à une fonction ou à un travail restant |
| 2.2 | Compléter le parcours consultation de bout en bout | Tests et parcours manuel couvrent création, pièces, envoi, décision et bon |
| 2.3 | Compléter le parcours examen lié à la consultation | Les règles de liaison et justificatifs sont confirmés et testés |
| 2.4 | Vérifier les règles de calcul, tarifs, taux, plafonds, franchises et arrondis pertinents | Chaque règle implémentée a une source ou est explicitement paramétrable |
| 2.5 | Vérifier les états d'erreur, de demande d'informations, de refus et de reprise | Les utilisateurs savent quoi faire ensuite à chaque état |
| 2.6 | Définir qui maintient les bénéficiaires, contrats, taux et tarifs dans le système | Responsabilités et autorisations documentées |
| 2.7 | Gérer le portefeuille assuré par assureur, avec import CSV contrôlé et corrections manuelles | Cloisonnement et règles d'import testés; format provisoire confronté à des exemples fictifs |

Les fonctionnalités confirmées du socle (comptes/organisations, catalogue,
tarifs et édition des brouillons) sont en place. La validation métier des
formats et règles demeure distincte de l'achèvement technique.

Claims, facturation, règlement et médicaments ne font pas partie de cette
phase. Ils seront réévalués après validation du workflow principal.

## Phase 3 : préparer un pilote contrôlé

| # | Tâche | Fait quand |
|---|---|---|
| 3.1 | Revue de sécurité couvrant authentification, autorisations, isolation, documents et audit | Findings priorisés, corrigés ou acceptés avec justification |
| 3.2 | Définir les comptes, rôles, récupération d'accès et protections adaptées aux organisations utilisatrices | Parcours d'administration et d'assistance testé |
| 3.3 | Vérifier le cadre local de protection des données et les responsabilités contractuelles avec des personnes compétentes | Exigences documentées avant tout traitement réel |
| 3.4 | Choisir hébergement, base, HTTPS, stockage privé, sauvegardes, restauration et supervision | ADR 0002 et checklist complétés; restauration testée |
| 3.5 | Tester migrations et parcours automatisés sur le moteur de base retenu | CI et tests MySQL/PostgreSQL ciblés passent |
| 3.6 | Définir conservation, export, suppression et réponse aux incidents | Procédures écrites et testées |
| 3.7 | Effectuer un pilote limité, autorisé et accompagné avant généralisation | Critères d'arrêt, responsables et retours du pilote établis |

Ne pas utiliser de données réelles avant que les validations applicables à ce
pilote aient été obtenues.

## Phase 4 : livrer le produit et le présenter

| # | Tâche | Fait quand |
|---|---|---|
| 4.1 | Intégration continue, analyse statique et formatage | Vérifications automatisées passent à chaque changement |
| 4.2 | Déployer un environnement de démonstration avec données fictives | URL HTTPS, comptes de démo et procédure de réinitialisation vérifiés |
| 4.3 | Terminer README et documentation d'exploitation | Une autre personne peut installer, tester et comprendre les limites |
| 4.4 | Relire le dépôt avant publication | Aucun secret, aucune donnée réelle, historique et licence vérifiés |
| 4.5 | Enregistrer une démonstration de 3 à 5 minutes | Les deux parcours principaux sont montrés sans données réelles |

## V2 et idées ultérieures

- Claims, facturation et suivi de règlement.
- Circuit de validation des médicaments en pharmacie.
- Intégrations API avec des assureurs ou établissements, seulement si un besoin
  et un partenaire les justifient.
- Correction d'un bon approuvé par nouvelle version.
- Accès du patient à son propre bon.
- API publique et éventuelle interface séparée, si des consommateurs réels le
  nécessitent.

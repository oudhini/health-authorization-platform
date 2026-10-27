# ADR 0005 : Sélection de l'assureur et vérification de la couverture

- **Statut** : accepté pour le workflow MVP
- **Date** : 2026-10-17

## Contexte

Un agent d'établissement doit choisir le payeur indiqué sur les justificatifs
du patient, puis retrouver la personne assurée. L'application doit empêcher
qu'une demande soit adressée à un assureur non partenaire ou qu'une police
appartenant à un autre bénéficiaire ou assureur soit soumise par modification
du formulaire.

## Décision

- Les partenariats entre établissements et assureurs sont enregistrés dans la
  table `facility_insurer` et gérés par l'administrateur.
- L'agent choisit d'abord un assureur partenaire, puis recherche par nom ou
  numéro de bénéficiaire.
- Les résultats ne comprennent que des bénéficiaires ayant une police active
  chez l'assureur choisi.
- Chaque police active est présentée séparément avec son numéro et son taux;
  l'agent sélectionne explicitement le contrat applicable. Le système ne
  choisit pas arbitrairement une police si plusieurs sont actives.
- Le serveur vérifie de nouveau le partenariat, l'appartenance du bénéficiaire
  au souscripteur, l'assureur de la police et ses dates d'activité au moment de
  créer la demande.
- Le taux reste défini par la police et copié sur la demande; les règles
  métier d'un contrat réel devront être confirmées avec l'assureur concerné.

## Conséquences

- Un numéro de bénéficiaire peut être recherché sans le connaître par coeur,
  tout en conservant le contrôle du périmètre de recherche par assureur.
- Les partenariats doivent être configurés avant que l'établissement puisse
  créer des demandes; les données de démonstration établissent ces liens par
  seeder.
- Ce workflow n'interroge aucun service réel d'assureur et ne confirme pas
  automatiquement les droits; il s'appuie sur les données de couverture
  enregistrées dans l'application.

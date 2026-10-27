# Import du portefeuille assuré (CSV)

Le portefeuille contient des données fictives dans le prototype. N'y importez
aucune donnée personnelle ou médicale réelle avant les validations et mesures
nécessaires à un pilote contrôlé.

## Qui peut gérer les dossiers

- Un gestionnaire assureur peut consulter, importer et corriger les dossiers
  de son propre assureur uniquement.
- Un administrateur plateforme peut choisir un assureur et intervenir pour
  une correction exceptionnelle.
- Un agent d'établissement ne peut pas accéder à cet écran. Il recherche les
  bénéficiaires via le parcours de création de demande, après avoir choisi un
  assureur partenaire.

Les changements manuels et imports confirmés sont journalisés. Le journal ne
contient pas les noms, dates de naissance, numéros d'assuré, noms d'entreprise
ou détails contractuels.

## Modèle provisoire

Téléchargez le modèle depuis **Portefeuille assuré → Télécharger le modèle
CSV**. L'ordre des en-têtes est strict :

```text
subscriber_name,policyholder_name,beneficiary_name,birth_date,relationship,member_number,policy_number,coverage_rate,starts_on,ends_on
```

Une ligne décrit un bénéficiaire et son assuré principal, souscripteur et
contrat. Plusieurs lignes peuvent partager un même souscripteur et un même
contrat. Les valeurs autorisées sont :

- dates au format `AAAA-MM-JJ`;
- `relationship` : `self`, `spouse`, `child` ou `other`;
- `coverage_rate` : entier de 0 à 100;
- champs de noms et numéros non vides.

Le fichier doit être un CSV UTF-8 séparé par des virgules, de 2 Mo maximum et
500 lignes de données maximum. Le modèle contient un exemple entièrement
fictif à remplacer.

## Déroulement et comportement

1. Téléverser le CSV pour obtenir un aperçu des lignes et des erreurs.
2. Corriger le fichier si nécessaire; aucune donnée n'est écrite lors de
   l'aperçu.
3. Pour confirmer, sélectionner à nouveau le même fichier. Le serveur vérifie
   qu'il s'agit des octets exacts prévisualisés et refait toutes les
   validations avant d'importer dans une transaction.
4. Les nouveaux numéros bénéficiaires sont créés. Un numéro déjà présent chez
   le même assureur met à jour le dossier correspondant. Un numéro ou contrat
   appartenant à un autre assureur est rejeté.
5. Si une ligne est invalide, contradictoire, si un contrat est rattaché à un
   autre souscripteur, ou si le fichier dépasse les limites, tout l'import est
   bloqué; aucune ligne partielle n'est enregistrée.

Les numéros de bénéficiaire et de contrat sont uniques au sein d'un assureur,
mais peuvent être identiques chez deux assureurs distincts. Les formats réels
des assureurs n'ayant pas encore été recueillis, ce modèle est provisoire; il
doit être confronté à des exemples fictifs ou dûment anonymisés avant d'être
considéré comme un format métier.

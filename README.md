# Health Authorization Platform

Plateforme en construction pour améliorer le traitement des demandes de prise
en charge entre établissements de santé et assureurs. Le projet part d'un
processus observé au Cameroun, encore largement appuyé sur des fiches papier,
des échanges par messagerie et l'impression de bons.

L'objectif final est de concevoir un produit qui puisse résoudre ce problème
dans des conditions réelles. Le prototype actuel permet de développer et de
tester le workflow avec des organisations, comptes et bénéficiaires fictifs,
sans intégration à un assureur réel. Le portfolio est un moyen de présenter le
travail, pas la finalité du produit.

## État actuel

- Règles métier de calcul de couverture et de cycle de vie, testées unitairement.
- Modèles et schéma de données métier, avec migrations appliquées à la base
  MySQL locale.
- Connexion par session, rôles et journal d'audit.
- Création et envoi de demandes par un agent d'établissement.
- Sélection d'un assureur partenaire, puis recherche de bénéficiaires par nom
  ou numéro, avec choix explicite de la police active.
- Configuration des partenariats établissement-assureur par l'administrateur.
- Examen des demandes par un gestionnaire assureur : approbation, refus motivé
  ou demande d'informations.
- Génération d'un bon imprimable avec montants et dates de validité figés à
  l'approbation.
- Téléversement et téléchargement contrôlés de documents.
- Interface Blade avec Bootstrap compilé par Vite.
- MySQL configuré par défaut pour le développement ; les tests utilisent
  SQLite en mémoire.

La validation du besoin auprès de plusieurs acteurs, les règles métier
contractuelles, le déploiement et les contrôles nécessaires à un pilote avec
des données réelles restent à réaliser.

## Données et usage

Toutes les données du dépôt et les comptes de démonstration sont fictifs.
Aucune donnée personnelle ou médicale réelle ne doit être utilisée dans
l'environnement actuel. L'objectif d'un usage réel reste conditionné à la
validation métier, technique, opérationnelle, juridique et contractuelle.

## Documentation

Les exigences métier, les acteurs et rôles, la feuille de route, l'état actuel,
le dépannage et les décisions d'architecture se trouvent dans [`docs/`](./docs/).
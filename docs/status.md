# État du projet

Ce document décrit le code intégré dans la branche de travail, et distingue
les parcours présents des étapes qui restent à valider ou à réaliser.

## Fonctionnalités présentes

- Authentification par session et comptes fictifs; accès contrôlé par rôle.
- Portail établissement : création, consultation, envoi et annulation de
  demandes, pièces jointes privées, recherche de bénéficiaires par nom ou
  numéro et sélection explicite d'un contrat actif.
- Partenariats établissement-assureur configurables par l'administrateur; la
  recherche et la création sont limitées aux assureurs partenaires choisis.
- Portail assureur : liste filtrée, consultation, approbation, refus motivé et
  demande d'informations.
- Portefeuille assuré : les gestionnaires maintiennent les souscripteurs,
  assurés principaux, bénéficiaires et contrats de leur assureur; les
  administrateurs peuvent intervenir par exception. Import CSV avec aperçu,
  validation complète, confirmation du même fichier, limite de taille/lignes
  et journalisation sans données personnelles en métadonnées.
- L'approbation génère un bon imprimable dont les montants sont copiés et
  conservés, avec dates de validité.
- Journalisation des actions métier et accès aux documents autorisés.
- Interface Blade rendue côté serveur, Bootstrap intégré via Vite.
- Le pilote Laravel `mysql` est utilisé avec MariaDB 10.4.32 sous XAMPP en
  local. La base cible MySQL 8 n'est pas encore vérifiée; les tests habituels
  restent configurés sur SQLite en mémoire.

## Vérifications exécutées

- `& 'C:\php84\php.exe' artisan test` : 168 tests réussis, 1 995 assertions.
- `npm run build` : compilation Vite réussie.
- Les vues intégrées dans les deux portails n'utilisent pas de `<style>` ni
  d'attribut `style=`.
- La migration initiale et le seeding ont été exécutés sur la base locale;
  la migration des partenariats établissement-assureur a ensuite été
  appliquée et le seeder ciblé a ajouté les quatre partenariats fictifs.
- Le parcours MariaDB a été vérifié dans le navigateur par recherche d'un numéro
  de bénéficiaire, sélection de la police et création d'un brouillon; la
  recherche du même numéro sous un assureur qui ne le couvre pas ne renvoie
  aucun résultat. La demande de test a ensuite été envoyée, approuvée avec le
  compte assureur fictif et a généré un bon consultable/imprimable.
- Le calcul applique le taux de la police au plus petit du montant facturé et
  du tarif de référence; le cas où le montant facturé est inférieur au tarif
  avec une couverture de 80 % est couvert par des tests unitaires et fonctionnels.
  Cette règle produit doit être confirmée pour chaque contrat avant un pilote
  réel.
- L'aller-retour « informations demandées → réponse par message et/ou nouveau
  document → retransmission » est désormais conservé dans l'historique de la
  demande; la retransmission sans complément est refusée. La migration additive
  a été appliquée à la base MariaDB locale et les demandes déjà en attente
  d'informations ont été ajoutées à l'historique.
- Les tests automatisés couvrent maintenant la création et l'approbation d'un
  examen lié, son bon imprimable, l'exigence de la fiche médicale et le rejet
  d'un lien vers une consultation d'un autre bénéficiaire. Ces nouveaux tests
  tournent habituellement sur SQLite en mémoire; la suite complète a aussi
  été exécutée sur une base MariaDB de test dédiée.
- Le gestionnaire assureur peut créer/corriger manuellement un dossier et
  prévisualiser puis confirmer un import CSV; les nouveaux bénéficiaires
  deviennent recherchables par un agent dans le contexte de l'assureur choisi.
  Les tests couvrent aussi le cloisonnement entre assureurs, l'absence
  d'écriture à l'aperçu, les erreurs de fichier et le refus d'un fichier
  différent après aperçu. Le contrat CSV reste provisoire.
- Une migration additive ajoute l'assureur directement au bénéficiaire et
  rend les numéros de bénéficiaire et de contrat uniques par assureur (et non
  globalement). Elle a été appliquée à la base locale et exercée par les tests
  SQLite et MariaDB.
- Le 2026-10-27, après le démarrage contrôlé de MariaDB local, le statut a
  confirmé que les 18 migrations sont appliquées. Une sauvegarde de la base a
  été créée dans le dossier temporaire Windows, hors du dépôt, avant toute
  autre opération.
- Une base séparée `health_authorization_platform_test` a été créée pour
  l'essai : toutes les migrations y ont été exécutées et les 168 tests
  (1 995 assertions) ont réussi sur MariaDB 10.4.32. Cette base de test a reçu
  uniquement les données fictives des seeders.
- Le serveur Laravel temporaire, relié uniquement à cette base de test, a
  répondu HTTP 200 sur l'accueil et la connexion. Il a ensuite été arrêté.
- La validation MariaDB ne remplace pas un essai sur MySQL 8 chez l'hébergeur.

## À faire avant toute utilisation réelle ou commerciale

- Relire et accepter les ADR 0003 (authentification) et 0004 (bon imprimable).
- Décider et documenter l'hébergeur, la sauvegarde, le stockage privé persistant,
  la supervision et la restauration.
- Revoir la sécurité, la confidentialité, les obligations réglementaires et
  contractuelles avec des personnes compétentes avant toute donnée réelle.
- Compléter les tests et validations avec la version MySQL réellement ciblée
  avant un déploiement; l'essai actuel porte sur MariaDB 10.4.32.
- Déploiement public, CI, revue du dépôt et vidéo de présentation non réalisés.

## Revue de sécurité ciblée

- Une demande liée est maintenant vérifiée côté serveur pour appartenir au
  même établissement, au même bénéficiaire, au même assureur et être approuvée.
  Le portail assureur masque aussi un lien incohérent vers un autre assureur.
- Le cookie de session doit être configuré `Secure` et HTTP redirigé vers HTTPS
  dans tout déploiement HTTPS; cette exigence est ajoutée à la grille
  d'hébergement.
- Ces corrections ne constituent pas une certification de sécurité. La
  configuration d'un éventuel hébergement reste à vérifier avant mise en ligne.

Les données et utilisateurs restent fictifs. L'application n'est pas prête à
traiter des données personnelles ou de santé réelles.

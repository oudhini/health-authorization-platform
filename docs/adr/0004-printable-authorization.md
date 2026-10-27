# ADR 0004 : Bon imprimable en HTML plutôt qu'une bibliothèque PDF

- **Statut** : proposé, implémenté et à relire
- **Date** : à compléter

## Contexte

Le besoin V1 est de remettre à l'établissement et à l'assureur un bon lisible
et imprimable, avec les montants figés au moment de l'approbation. Générer et
archiver un fichier PDF sur le serveur n'est pas nécessaire au workflow actuel.

## Options étudiées

| Option | Avantages | Inconvénients |
|---|---|---|
| Page HTML avec règles d'impression du navigateur | Pas de dépendance supplémentaire; l'utilisateur peut imprimer ou enregistrer en PDF | Le navigateur crée le PDF, pas le serveur |
| Bibliothèque de génération PDF côté serveur | Fichier PDF généré et archivable par le serveur | Dépendance et compatibilité à vérifier; mise en page à maintenir |
| Navigateur sans interface côté serveur | Rendu proche du navigateur utilisateur | Plus lourd à installer et héberger |

## Décision proposée

Une page HTML dédiée, protégée par policy, affiche les montants et dates du
bon. Un bouton lance l'impression du navigateur; une feuille de style
`@media print` masque la navigation et les actions. Le modèle d'autorisation
conserve une copie immuable des totaux au moment de l'approbation. Aucune
bibliothèque PDF n'est ajoutée à la V1.

## Conséquences

- Le critère de réussite demande un bon imprimable ou enregistrable en PDF
  depuis le navigateur, et non un PDF produit par le serveur.
- Si un archivage PDF serveur devient nécessaire, une bibliothèque maintenue
  et compatible avec la version Laravel sera évaluée dans un nouvel ADR.

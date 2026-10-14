# Cahier des charges V1

> Statut : brouillon. Les sections marquées À FAIRE seront rédigées plus tard.

## 1. Contexte et objectif

Ce projet est un prototype de portfolio qui digitalise le processus de
prise en charge par une assurance santé observé dans un hôpital au Cameroun.
Aujourd'hui, ce processus repose sur des fiches papier, des photos envoyées
par messagerie et des bons imprimés.

Objectif : construire, tester, sécuriser, documenter et déployer un prototype
fonctionnel qui simule deux organisations (un établissement de santé et un
assureur fictif), indépendant de tout assureur ou hôpital réel.

## 2. Périmètre

### Dans le périmètre (MVP)
- Portail établissement et portail assureur (assureur fictif)
- Workflow de prise en charge pour une consultation
- Workflow simplifié de prise en charge pour un examen
- Calcul de la couverture (voir section 6)
- Bon de prise en charge généré en PDF imprimable
- Authentification et contrôle d'accès par rôle
- Dépôt sécurisé de documents
- Journal d'audit
- Tests automatisés, déploiement public, comptes de démonstration

### Hors périmètre (pour l'instant)
- Claims et facturation (V2)
- Circuit des médicaments
- Toute intégration avec un vrai assureur, hôpital ou messagerie
- Toute donnée personnelle ou médicale réelle
- Paiement en ligne

## 3. Acteurs et rôles
À FAIRE (voir docs/actors-and-roles.md)

## 4. Entités métier

> Les noms en `code` sont ceux du glossaire et deviendront les noms de tables
> et de colonnes. Les montants sont des entiers (FCFA).

### 4.1 Entités

| Entité | Nom dans le code | Rôle | Attributs principaux |
|---|---|---|---|
| Organisation | `organization` | Établissement ou assureur | nom, type (`facility` ou `insurer`) |
| Utilisateur | `user` | Compte de connexion | nom, email, mot de passe haché, rôle, organisation |
| Souscripteur | `subscriber` | Entreprise qui souscrit | nom, assureur |
| Assuré principal | `policyholder` | Salarié titulaire | nom, souscripteur |
| Bénéficiaire | `beneficiary` | Personne qui reçoit les soins | nom, date de naissance, assuré principal, lien de parenté |
| Police | `policy` | Contrat de couverture | assureur, souscripteur, taux de prise en charge, début, fin |
| Acte médical | `medical_act` | Prestation catalogue | code, libellé |
| Tarif officiel | `official_tariff` | Prix reconnu d'un acte | acte, montant, date d'entrée en vigueur |
| Demande | `authorization_request` | Demande envoyée à l'assureur | établissement, bénéficiaire, police, statut, créée par |
| Ligne de demande | `authorization_item` | Un acte dans la demande | acte, montant facturé, tarif officiel appliqué, montant accepté, part assureur, reste à charge |
| Bon de prise en charge | `authorization` | Décision d'approbation | demande, numéro, valid_from, valid_until, décidé par, version |
| Document | `document` | Pièce jointe | demande, type, chemin de stockage privé, déposé par |
| Journal d'audit | `audit_log` | Trace des actions sensibles | utilisateur, action, objet concerné, date |

### 4.2 Relations

- Une organisation a plusieurs utilisateurs ; un utilisateur appartient à une seule organisation.
- Un assuré principal a plusieurs bénéficiaires.
- Une demande concerne un bénéficiaire, une police et un établissement, et contient une ou plusieurs lignes.
- Une demande approuvée donne un bon ; une correction crée une nouvelle version du bon.
- Une demande peut avoir plusieurs documents.

### 4.3 Règles de conception

- Chaque ligne de demande **copie** le tarif officiel appliqué au moment de la décision : si la grille change plus tard, les anciens bons ne changent pas.
- Un bon approuvé n'est jamais modifié : toute correction crée une nouvelle version.
- Les documents sont stockés hors du dossier public, avec un accès contrôlé.
- Les données de démonstration sont entièrement fictives.

### 4.4 Questions ouvertes

- Faut-il séparer le catalogue d'actes de chaque assureur, ou un catalogue commun ?
- Un bénéficiaire peut-il être couvert par plusieurs polices ?
- Le lien de parenté est-il nécessaire pour les règles de calcul, ou seulement informatif ?

## 5. Cycle de vie d'une demande (états et transitions)
À FAIRE

## 6. Règles de calcul de la couverture

### 6.1 Définitions
- **Montant facturé** (`billed_amount`) : prix demandé par l'établissement pour un acte médical.
- **Tarif officiel** (`official_tariff`) : prix officiellement reconnu pour cet acte (tarif de l'État).
- **Taux de prise en charge** (`coverage_rate`) : pourcentage du montant accepté payé par l'assureur (0 à 100).
- **Montant accepté** (`accepted_amount`) : partie du montant facturé que l'assureur reconnaît.
- **Dépassement** (`overrun`) : partie du montant facturé au-dessus du tarif officiel.

### 6.2 Règles (calculées par acte, jamais sur le total)
1. accepted_amount = min(billed_amount, official_tariff)
2. insurer_share   = arrondi(coverage_rate x accepted_amount)
3. patient_share   = billed_amount - insurer_share
4. overrun         = max(0, billed_amount - official_tariff)
5. Les totaux du bon sont la somme des valeurs calculées par acte.

### 6.3 Invariants
- Tous les montants sont des entiers positifs ou nuls (le FCFA n'a pas de décimales).
- insurer_share + patient_share = billed_amount, toujours.
- 0 <= insurer_share <= accepted_amount <= billed_amount.
- Seuls les actes explicitement listés sur le bon sont couverts.
- Une prestation est couverte uniquement si sa date est dans [valid_from, valid_until].
- La validité par défaut est de 7 jours ; elle est stockée dans chaque bon, pas écrite en dur dans le code.

### 6.4 Cas de référence (valeurs fictives)
| Cas | Facturé | Tarif officiel | Taux | Part assureur | Reste à charge |
|---|---|---|---|---|---|
| A. Établissement moins cher que le tarif | 8000 | 10000 | 100% | 8000 | 0 |
| B. Prix égal au tarif | 10000 | 10000 | 100% | 10000 | 0 |
| C. Dépassement | 12000 | 10000 | 100% | 10000 | 2000 |
| D. 80 %, sans dépassement | 10000 | 10000 | 80% | 8000 | 2000 |
| E. 80 %, avec dépassement | 12000 | 10000 | 80% | 8000 | 4000 |

### 6.5 Questions ouvertes
- Quand le prix de l'établissement est inférieur au tarif, l'assureur paie-t-il bien le prix facturé ? (cas A)
- Le taux de prise en charge est-il toujours de 100 % ? (cas D, E)
- Règle d'arrondi quand taux x montant accepté n'est pas un entier (proposition : arrondi à l'entier le plus proche).
- Source et mise à jour du tarif officiel (grille de l'État, coefficient de l'acte x valeur unitaire ?).
- La franchise d'hospitalisation est exprimée en jours : hors MVP pour l'instant.
- Médicaments : hors MVP.

## 7. Workflows principaux
À FAIRE

## 8. Exigences non fonctionnelles
À FAIRE (sécurité, audit, confidentialité)

## 9. Critères de réussite
À FAIRE

## 10. Questions ouvertes
À FAIRE (regrouper ici toutes les questions ouvertes)
# Acteurs et rôles

## 1. Principe

Chaque utilisateur appartient à **une seule organisation** (un établissement
de santé ou un assureur). Il ne voit que les données de son organisation et
celles qui lui sont adressées. C'est la règle de sécurité la plus importante
de la plateforme.

## 2. Rôles du MVP

| Rôle | Nom dans le code | Organisation | Description |
|---|---|---|---|
| Agent d'établissement | `facility_agent` | Établissement | La personne chargée des assurances à l'hôpital. Crée et envoie les demandes. |
| Gestionnaire assureur | `insurer_manager` | Assureur | Examine les demandes, approuve, refuse ou demande des informations. |
| Administrateur | `platform_admin` | Plateforme | Gère les comptes et les paramètres. Ne prend aucune décision sur les demandes. |

Acteurs réels qui ne sont **pas** des utilisateurs de la plateforme dans le MVP :
le patient ou son accompagnant, le médecin, la caisse de l'hôpital.

## 3. Droits par rôle

| Action | `facility_agent` | `insurer_manager` | `platform_admin` |
|---|---|---|---|
| Créer une demande de prise en charge | Oui | Non | Non |
| Modifier une demande en brouillon | Oui (la sienne) | Non | Non |
| Joindre des documents à une demande | Oui | Non | Non |
| Envoyer la demande à l'assureur | Oui | Non | Non |
| Voir les demandes de son établissement | Oui | Non | Non |
| Voir les demandes adressées à son assureur | Non | Oui | Non |
| Approuver ou refuser | Non | Oui | Non |
| Demander des informations complémentaires | Non | Oui | Non |
| Télécharger le bon en PDF | Oui (son établissement) | Oui (son assureur) | Non |
| Consulter le journal d'audit | Non | Non | Oui |
| Gérer les comptes utilisateurs | Non | Non | Oui |
| Gérer souscripteurs, assurés, bénéficiaires et polices de son assureur | Non | Oui | Oui (correction exceptionnelle) |
| Gérer le catalogue d'actes et les tarifs | Non | Non | Oui |
| Configurer les assureurs partenaires d'un établissement | Non | Non | Oui |

## 4. Règles de sécurité sur les rôles

- Un utilisateur ne peut jamais lire ni modifier la demande d'une autre
  organisation, même s'il connaît son identifiant.
- Celui qui crée une demande ne peut pas la décider (séparation des rôles).
- Un bon approuvé ne peut plus être modifié. La correction par nouvelle version est prévue en V2.
- Chaque action sensible (envoi, décision, téléchargement) est écrite dans le journal d'audit avec l'utilisateur, la date et la demande concernée.

## 5. Questions ouvertes

- Le médecin-conseil est-il un rôle séparé de `insurer_manager`, ou la même personne ? (hors MVP pour l'instant)
- Plusieurs établissements peuvent-ils partager un même assureur dans la démo ?
- Le patient doit-il avoir un accès en lecture à son propre bon ? (hors MVP)
- Qui est autorisé à gérer le catalogue d'actes et les tarifs, et selon quelle source ?
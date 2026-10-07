# Glossaire

Chaque terme métier est associé au nom utilisé dans le code (en anglais).

## Personnes et organisations

| Terme métier | Nom dans le code | Définition |
|---|---|---|
| Souscripteur | `subscriber` | L'entreprise qui souscrit le contrat d'assurance pour ses salariés. |
| Assuré principal | `policyholder` | Le salarié titulaire du contrat. |
| Bénéficiaire | `beneficiary` | La personne qui reçoit les soins (assuré principal ou personne rattachée, par exemple un enfant). |
| Établissement de santé | `facility` | L'hôpital ou le centre qui facture les actes. |
| Assureur | `insurer` | La compagnie qui accepte ou refuse la prise en charge. |
| Médecin-conseil | `medical_advisor` | Médecin côté assureur. À vérifier : son rôle exact dans la décision. |

## Contrat et couverture

| Terme métier | Nom dans le code | Définition |
|---|---|---|
| Police | `policy` | Le contrat : taux, limites, dates, actes couverts. |
| Taux de prise en charge | `coverage_rate` | Pourcentage du montant accepté payé par l'assureur. |
| Franchise | `deductible` | Sur le bon observé, exprimée en jours d'hospitalisation. Hors MVP. |
| Période de validité | `valid_from`, `valid_until` | Dates pendant lesquelles le bon peut être utilisé. Par défaut 7 jours. |

## Documents

| Terme métier | Nom dans le code | Définition |
|---|---|---|
| Bon de prise en charge | `authorization` | Document par lequel l'assureur accepte de payer certains actes, avec limites et durée de validité. |
| Fiche maladie | `medical_form` | Document rempli, signé et cachetĂ© par le médecin après la consultation. |
| Demande de prise en charge | `authorization_request` | Demande envoyée par l'établissement à l'assureur avant la décision. |

## Actes et montants

| Terme métier | Nom dans le code | Définition |
|---|---|---|
| Acte médical | `medical_act` | Une prestation précise (consultation, échographie, etc.). |
| Montant facturé | `billed_amount` | Prix demandé par l'établissement pour un acte. |
| Tarif officiel | `official_tariff` | Prix reconnu par l'État pour cet acte. |
| Montant accepté | `accepted_amount` | Le plus petit entre le montant facturé et le tarif officiel. |
| Part de l'assureur | `insurer_share` | Ce que l'assureur paie. |
| Reste à charge | `patient_share` | Ce que le patient paie. |
| Dépassement | `overrun` | Partie du prix facturé au-dessus du tarif officiel. |
| Proforma | `proforma` | À vérifier : probablement le devis établi par l'établissement avant l'acte. |
| Proforma révisée | `revised_proforma` | À vérifier : probablement le montant ramené au tarif officiel. |
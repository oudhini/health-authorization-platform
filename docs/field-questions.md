# Guide d'entretien terrain

But : lever les questions « Terrain » de la section 10 de
`docs/requirements.md`. Durée : 15 à 20 minutes avec la personne chargée des
assurances de l'hôpital.

## Règles à respecter

- Demande la permission avant de poser des questions et explique que c'est
  pour un projet personnel de formation.
- Ne note **aucun nom de patient, de parent ou d'entreprise assurée**.
- Ne photographie ni ne copie aucun document réel.
- Note les réponses dans tes propres mots, avec la date.
- Si la personne ne sait pas, note « ne sait pas » : c'est une information.

## Questions (par ordre de priorité)

### A. Calcul (bloque les tests)
1. Quand le prix de l'hôpital est **inférieur** au tarif officiel d'un acte,
   l'assureur paie-t-il le prix de l'hôpital ou le tarif officiel ? (Q1)
2. Le taux de prise en charge est-il toujours de 100 % ? Existe-t-il des
   contrats à 80 %, 70 % ou autre ? (Q2)
3. D'où vient le tarif officiel d'un acte ? Existe-t-il une liste, un arrêté,
   un barème papier ? Y a-t-il une notion de coefficient (comme « K10 ») ? (Q4)
4. Comment l'assureur arrondit-il quand le calcul ne tombe pas juste ? (Q3)

### B. Circuit de décision (phase 2)
5. Quelles sont les raisons de refus les plus fréquentes ?
6. Que se passe-t-il quand l'assureur demande des informations en plus ?
   Qu'est-ce qui est demandé, en général ? (Q8)
7. Une demande refusée peut-elle être reprise, ou faut-il en refaire une ? (Q8)
8. Peut-on annuler une demande déjà envoyée ? (Q7)
9. Qui, chez l'assureur, décide ? Y a-t-il un médecin-conseil ? Quel est son rôle ? (Q5)

### C. Contrats et personnes (phase 2)
10. Un enfant ou un salarié peut-il être couvert par plus d'un contrat ? (Q10)
11. Les actes couverts changent-ils d'un contrat à l'autre ?
12. Quelles informations identifient un bénéficiaire (numéro, carte) ?

### D. Médicaments (hors MVP, pour plus tard)
13. Comment la pharmacie vérifie-t-elle la prise en charge ? Quels documents
    sont demandés ? (Q13)

## Fiche de notes (une ligne par réponse)

| # | Question | Réponse (mes mots) | Date | Sûr de moi ? (oui / à confirmer) |
|---|---|---|---|---|
| 1 | | | | |
| 2 | | | | |
| 3 | | | | |

## Après l'entretien

1. Mets à jour la section 6 et la section 10 de `docs/requirements.md`.
2. Si la réponse à la question 1 ou 2 change un cas de référence, modifie le
   cas dans `docs/requirements.md` **et** dans
   `tests/Unit/Coverage/CoverageCalculatorTest.php`.
3. Commit : `docs: update rules after field interview`.

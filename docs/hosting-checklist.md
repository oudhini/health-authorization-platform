# Grille de choix de l'hébergeur

Objectif : évaluer un hébergement adapté à une présentation publique du
portfolio, avec uniquement des comptes et données fictifs. Une mise en service
commerciale nécessitera une évaluation séparée. Les offres, versions de PHP et
limites gratuites changent souvent : **vérifie chaque point sur la
documentation actuelle de l'hébergeur, à la date du jour, et note la date.**

## Critères

| # | Critère | Pourquoi | Bloquant ? |
|---|---|---|---|
| 1 | PHP 8.4.1 ou plus disponible | Exigé par les dépendances installées | Oui |
| 2 | MySQL ou PostgreSQL disponible | Voir ADR 0002 | Oui |
| 3 | Le dossier public du site peut pointer sur `public/` | Sinon le code et le `.env` seraient accessibles depuis Internet | Oui |
| 4 | Variables d'environnement ou `.env` hors du dossier public | Les secrets ne doivent jamais être exposés | Oui |
| 5 | Stockage de fichiers **persistant** | Les documents déposés doivent survivre aux redémarrages. Certains hébergements gratuits effacent les fichiers à chaque redéploiement : à vérifier explicitement | Oui |
| 6 | Accès SSH ou méthode de déploiement claire (Git, FTP, pipeline) | Pour déployer et lancer les migrations | Oui |
| 7 | Composer utilisable (ou dossier `vendor/` envoyé) | Pour les dépendances | Oui |
| 8 | HTTPS | Protection du site public, même avec des données fictives | Oui |
| 9 | Tâches planifiées / files d'attente | Utile plus tard (notifications) | Non |
| 10 | Limites gratuites (veille du site, quotas, durée) | Une démo qui s'endort ou disparaît est mauvaise pour un recruteur | À noter |
| 11 | Conditions d'utilisation compatibles avec une démo de projet | Éviter une suspension | À lire |
| 12 | Cookies de session `Secure` en production HTTPS et redirection HTTP vers HTTPS | Évite d'envoyer le cookie de session sur une connexion non chiffrée | Oui |

## Comparaison initiale (vérifiée le 2026-10-27)

| Hébergeur | Date de vérification | PHP max | BD | `public/` | Fichiers persistants | SSH/déploiement | Coût | Verdict |
|---|---|---|---|---|---|---|---|---|
| OVHcloud mutualisé | 2026-10-27 | Non confirmé | Non confirmé | Non confirmé | Espace web annoncé, persistance des pièces Laravel à confirmer | Non confirmé pour ce plan | Tarif et renouvellement à vérifier dans la région du compte | Non qualifié : les critères bloquants ne sont pas prouvés |
| Railway PaaS | 2026-10-27 | PHP 8.4 possible via une image Docker à construire et tester | Service MySQL documenté | À configurer dans le conteneur pour ne servir que `public/` | Volumes persistants documentés; 0,5 Go en gratuit/essai, 5 Go sur Hobby | Déploiement GitHub et commande pré-déploiement documentés; Composer devra être installé dans l'image | Hobby : 5 USD/mois de base, plus ressources consommées; gratuit : 1 USD de crédit mensuel | Meilleur candidat PaaS à valider; coût récurrent et stockage privé à tester |
| Hetzner Cloud VPS | 2026-10-27 | À installer et maintenir soi-même | MySQL à installer et maintenir soi-même | Configurable dans Nginx avec `public/` comme racine | Disque du serveur et volumes persistants; les volumes ne sont pas inclus dans les sauvegardes de serveur | SSH; Composer, déploiement, HTTPS et migrations à configurer soi-même | Serveur selon taille/région; IPv4 publique : 0,50 EUR/mois hors TVA en supplément | Contrôle élevé, mais administration et restauration à prendre en charge |

### Sources et limites de la comparaison

Pages officielles consultées le 2026-10-27 :

- [OVHcloud — hébergement web et VPS](https://www.ovhcloud.com/en/web-hosting/) : décrit les offres mutualisées et VPS, mais ne permet pas de confirmer les critères Laravel bloquants du plan précis.
- [Railway — MySQL](https://docs.railway.com/databases/mysql), [volumes](https://docs.railway.com/volumes), [tarifs](https://docs.railway.com/pricing/plans), [déploiement préalable](https://docs.railway.com/deployments/pre-deploy-command) et [HTTPS](https://docs.railway.com/networking/public-networking).
- [Hetzner — serveurs cloud](https://docs.hetzner.com/cloud/servers/overview/), [volumes et limites de sauvegarde](https://docs.hetzner.com/cloud/volumes/overview/) et [tarifs](https://www.hetzner.com/cloud/).

Ces pages ne suffisent pas à certifier un déploiement. Pour Railway, il reste
à prouver qu'une image Laravel/PHP 8.4 fonctionne avec ce dépôt, que les
documents privés sont montés sur un volume persistant, que le déploiement ne
publie que `public/` et que les sauvegardes peuvent être restaurées. Pour le
mutualisé OVHcloud, il faut obtenir les versions PHP/base, l'accès Composer,
le répertoire racine configurable et les limites exactes du plan. Les prix
varient selon la région, les taxes, la durée d'engagement et l'usage; aucun
achat n'est décidé ici.

La recommandation provisoire est de tester Railway avec le plan Hobby, car son
service MySQL et ses volumes sont documentés et évitent d'administrer un VPS.
Ce n'est pas encore une décision d'hébergement : confirmer d'abord le coût
total acceptable, la région et les critères techniques ci-dessus.

Compare au moins trois solutions de nature différente : hébergement mutualisé,
plateforme de déploiement (PaaS) et petit serveur virtuel (VPS). Cette grille
ne constitue pas une validation pour des données de santé réelles.

En production HTTPS, définir `SESSION_SECURE_COOKIE=true` et configurer
l'hébergeur pour rediriger HTTP vers HTTPS. La valeur doit rester adaptée à
l'environnement local HTTP; ne pas copier une configuration de production
dans le `.env` local sans vérifier le protocole utilisé.

## Avant d'accepter une décision

1. Confirme les critères bloquants auprès de l'offre exacte et vérifie les
   conditions d'utilisation pour une démonstration publique.
2. Fais un déploiement d'essai avec des données fictives, puis teste
   sauvegarde **et restauration** des documents et de la base.
3. Renseigne le choix final et ses preuves dans
   `docs/adr/0002-database-and-hosting.md`; ne passe son statut à « accepté »
   qu'après ces vérifications.

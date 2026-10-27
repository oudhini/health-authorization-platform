# Dépannage local sous Windows

Depuis la racine du dépôt, utiliser explicitement le PHP 8.4 installé pour ce
projet afin de ne pas dépendre de l'ordre du PATH ou de PHP/XAMPP destiné à
d'autres projets :

```powershell
& 'C:\php84\php.exe' -v
& 'C:\php84\php.exe' artisan --version
```

## Commandes usuelles

```powershell
& 'C:\php84\php.exe' artisan serve
npm run build
& 'C:\php84\php.exe' artisan test
```

`npm run build` produit les fichiers servis par Laravel et référencés par
`@vite`. `npm run dev` est optionnel pour travailler sur le front avec le
rechargement à chaud; le serveur Vite et `artisan serve` sont deux processus
distincts.

Les tests utilisent SQLite en mémoire via `phpunit.xml`; ils ne se connectent
pas à la base MySQL décrite dans `.env`. Les migrations locales déjà appliquées
peuvent être contrôlées sans modification avec :

```powershell
& 'C:\php84\php.exe' artisan migrate:status
```

Avant d'appliquer une migration sur une base locale contenant des données,
vérifier la configuration `.env` et effectuer une sauvegarde. Ne pas utiliser
`migrate:fresh` sur une base à conserver : cette commande supprime les tables.

## Symptômes fréquents

| Symptôme | Vérification |
|---|---|
| `could not find driver` | Vérifier que l'extension PDO adaptée à la base voulue est activée dans la configuration PHP utilisée |
| `Base table ... not found` | Vérifier l'environnement réellement utilisé, puis consulter `artisan migrate:status`; ne pas effacer la base pour contourner le problème |
| Page 419 « Page expirée » | Recharger la page afin d'obtenir un nouveau jeton CSRF, puis renvoyer le formulaire |
| Vue, route ou asset qui semble ancien | Vérifier le résultat du build Vite et, si nécessaire, vider les caches applicatifs avec `artisan optimize:clear` |
| Un test échoue | Lancer `& 'C:\php84\php.exe' artisan test --filter=NomDuTest` et examiner le premier échec |

Ne pas transmettre le contenu de `.env` ni de données de patients pour demander
de l'aide.

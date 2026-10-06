# Audit de FlamePHP

Revue du cœur du framework (`Router`, `Routes`, `Request`, `Security`, `Authentication`, `Session`, `Cookie`, `DataBase`, `AbstractModel`, `File`, `View`).

**Points forts :** code clair et lisible, routes déclarées par attributs PHP 8, cache des routes, chiffrement des champs du modèle, soft delete, DataTable.

Les points ci-dessous sont classés par priorité.

---

## 🔴 1. Sécurité (à corriger en priorité)

| # | Problème | Où |
|---|---|---|
| 1 | **Le nonce de chiffrement est toujours le même.** Il est tiré de la clé (`substr($encryptKey, 0, 24)`). Avec `secretbox`, réutiliser un nonce casse la confidentialité : en comparant deux messages chiffrés, on retrouve le XOR des deux textes en clair. Il faut un `random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)` à chaque chiffrement, placé devant le texte chiffré. Pour la recherche sur un champ chiffré (`:searchEncrypted`), il faut une colonne d'index séparée (HMAC du texte en clair). ⚠️ Ce changement rend illisibles les données déjà chiffrées : il faut un script de migration. | `src/Security.php` `encrypt`/`decrypt` |
| 2 | **Upload : l'extension vient du nom envoyé par le client et n'est pas vérifiée.** Un `shell.php` est enregistré en `.php`. Si le dossier est public, n'importe qui peut exécuter du code sur le serveur. Il faut déduire l'extension du type MIME réel (`finfo`) en passant par une liste blanche. | `src/File.php:142-150` |
| 3 | **CORS :** si `corsRestrictedOrigins` est vide, n'importe quelle `Origin` est renvoyée telle quelle avec `Access-Control-Allow-Credentials: true`. N'importe quel site peut alors appeler l'API avec la session de l'utilisateur. | `src/Router.php:22-23` |
| 4 | **`X-Forwarded-For` est cru sans vérification.** L'IP, et donc l'empreinte de session/JWT, peut être choisie par le client. Il ne faut lire cet en-tête que derrière un proxy déclaré dans la config. | `Security::ip()` |
| 5 | **Jetons CSRF et refresh tokens prévisibles.** Ils sont générés avec `uniqid(rand())`. Il faut `bin2hex(random_bytes(32))`, et comparer avec `hash_equals()` plutôt qu'avec `===`. Les refresh tokens sont aussi stockés en clair en base (stocker plutôt un hash). | `Security::token`, `Authentication::getJSONWebToken` |
| 6 | **Bug dans `removeExpiredTokens` :** la condition utilise `token.time` à la place de `token.expire`. Résultat : les jetons ne sont jamais supprimés. | `src/Security.php` |
| 7 | **Cookies sans `HttpOnly` ni `SameSite`.** En plus, dans `deleteAll`, les arguments de `setcookie` sont décalés : `secure=false` et `httponly=$secure`. Utiliser la signature avec tableau d'options. | `src/Cookie.php` |
| 8 | **Pas de `session_regenerate_id(true)` à la connexion**, ce qui permet une fixation de session. | `Authentication::register` |
| 9 | **Le blocage après 10 essais est définitif.** Le compteur ne redescend jamais, donc on peut bloquer le compte de quelqu'un exprès (prévoir un blocage temporaire). Les messages d'erreur différents (« non validé » / « mauvais mot de passe ») permettent aussi de savoir si un compte existe. | `Authentication::login` |
| 10 | **`executeSqlFile`** écrit les identifiants de la base dans un fichier situé dans le dossier du package (`__DIR__/../db.cnf`), puis lance une commande shell sans `escapeshellarg`. Utiliser `sys_get_temp_dir()` + `tempnam()` et échapper les arguments. | `src/DataBase.php` |
| 11 | **`HTMLPurifier` est appliqué à toutes les entrées**, y compris les mots de passe : `a<b` est modifié. Il vaut mieux échapper à l'affichage (`htmlspecialchars` dans les vues) que nettoyer les entrées. C'est aussi coûteux en performance. | `Request`, `Cookie` |

---

## 🟠 2. Bugs fonctionnels

- **Les routes à plusieurs paramètres ne marchent pas.** `preg_match` ne capture que le premier `{param}`, il faut `preg_match_all`. Et un seul attribut `#[Route]` est lu par méthode (`$attributes[0]`), alors que l'attribut est déclaré `IS_REPEATABLE`. — `src/Routes.php`
- **`Request::data()` :** avec `JSON_THROW_ON_ERROR`, un corps `x-www-form-urlencoded` lève une exception, donc la branche `else` n'est jamais exécutée. Le découpage fait à la main (`rawurldecode` puis `explode('=')`) casse aussi les valeurs qui contiennent `=` ou `&`. Il faut utiliser `parse_str()`. — `src/Request.php`
- **`getCurrentHttpProtocol()` :** la condition `(!empty($https) || $https !== 'on')` est presque toujours vraie, et `SERVER_PORT` passe avant `X-Forwarded-Proto`. — `src/Router.php`
- **DataTable :** la recherche utilise `"%{$search}"`, il manque sans doute le `%` final. — `src/DataTableQuery.php`
- **PDO** n'est pas configuré : il manque `PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_EMULATE_PREPARES => false` et le `charset` dans le DSN. — `src/DataBase.php`
- **`Response::setHeader`** n'a pas les codes 201, 204, 405, 422, 429, et `555` n'est pas un code standard. `http_response_code($code)` suffit. — `src/Response.php`

---

## 🟡 3. Architecture

- **Tout est statique et passe par des états globaux** (`$_GET`, `$_SESSION`, `exit`, `header()`, constantes `ROOT`/`BASE_URL`/`TEMPLATES`). Conséquences : impossible à tester, et incompatible avec les serveurs persistants comme FrankenPHP, RoadRunner ou Swoole. Pistes :
  - un objet `Request`/`Response` injecté (PSR-7, ou une version maison) ;
  - un conteneur d'injection de dépendances simple (PSR-11) ;
  - une chaîne de **middlewares** (PSR-15) pour CORS, HTTPS, session, auth et gestion des erreurs, au lieu de tout mettre dans `Router::request()`.
- **Couplage en dur à `\App\Model\User`** et à `src/Controller` : à rendre configurable.
- **`Query` ne contient que des chaînes**, ce qui favorise les injections : `findBy("$field = ...")` et `orderBy`/`limit` sont insérés directement dans le SQL. Un petit query builder avec `where($col, $op, $val)` et des noms de colonnes échappés serait plus sûr.
- **Configuration :** permettre les variables d'environnement ou un `.env` pour les secrets, plutôt qu'uniquement `config.json`.

---

## 🟢 4. Qualité et outillage

- **`composer.json`** :
  - passer à `"php": ">=8.2"` et enlever `minimum-stability: dev` ;
  - mettre à jour les dépendances : `firebase/php-jwt` ^6 (avec l'objet `Key`), `predis/predis` ^2, `intervention/image` ^3 ;
  - ajouter `autoload-dev` et `scripts`.
- **Pas de tests :** ajouter PHPUnit ou Pest, en commençant par le routeur, `Request` et `Security`.
- **Analyse statique :** PHPStan niveau 5 puis plus, et PHP-CS-Fixer.
- **CI :** GitHub Actions qui lance tests et analyse.
- **Commits :** tous les messages sont « Update ». Des messages qui décrivent le changement, plus des tags semver, aideraient ceux qui utilisent le package.
- **README :** il n'y a aucune documentation. Un exemple minimal (route, contrôleur, modèle, config) aiderait beaucoup à l'adoption.

---

## Ordre conseillé

1. Sécurité #1 à #3 (chiffrement + script de migration, upload, CORS)
2. Bug des routes à plusieurs paramètres
3. Reste de la section sécurité (#4 à #11)
4. Bugs fonctionnels
5. Outillage (tests, PHPStan, CI), qui sécurise ensuite le refactoring
6. Architecture (middlewares, injection de dépendances, query builder)

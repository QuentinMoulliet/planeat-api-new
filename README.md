# 🍽️ Planeat API

Backend API de **Planeat** (version light) : une PWA familiale pour saisir ses repas, générer un planning midi/soir et en déduire automatiquement la liste de courses, le tout partagé entre les membres du foyer.

Pas de stock, pas de scan, pas de grammage : un repas = un nom + une liste de produits.

📋 Périmètre et règles métier détaillées : [CAHIER_DES_CHARGES.md](CAHIER_DES_CHARGES.md)

---

## Stack

- **PHP** 8.3 / **Symfony** 7.4
- **Doctrine ORM** 3 — MariaDB 11.4
- **JWT Auth** — `lexik/jwt-authentication-bundle` (token 30 jours)
- **CORS** — `nelmio/cors-bundle`

---

## Installation

### 1. Dépendances

```bash
git clone git@github.com:QuentinMoulliet/planeat-api-new.git planeat-api
cd planeat-api
composer install
```

### 2. Environnement

Créer un `.env.local` :

```env
APP_ENV=dev
APP_SECRET=<openssl rand -hex 16>
DATABASE_URL="mysql://user:pass@127.0.0.1:3306/planeat_light?serverVersion=11.4.13-MariaDB&charset=utf8mb4"
DEFAULT_URI=http://localhost:8000
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
JWT_PASSPHRASE=<openssl rand -hex 32>
```

`APP_TIMEZONE` vaut `Europe/Paris` par défaut (`.env`) : c'est lui qui définit « aujourd'hui » pour le planning et la liste de courses.

### 3. Base de données & clés JWT

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console lexik:jwt:generate-keypair
```

### 4. Rayons, foyer, utilisateurs

```bash
php bin/console app:seed-categories     # 15 rayons (une seule fois, idempotent)
php bin/console app:create-household    # crée le foyer + son catalogue de ~800 produits
php bin/console app:create-user         # ajoute un utilisateur à un foyer (interactif)
```

Pas de fixtures : les données de démo se créent depuis l'app.

### 5. Serveur

```bash
symfony serve --port=8000 --no-tls
```

---

## Commandes

| Commande | Rôle |
|---|---|
| `app:seed-categories` | Crée / réordonne les rayons globaux (`src/Seed/CategoryCatalog.php`). Idempotent. |
| `app:create-household [nom]` | Crée un foyer et copie le catalogue produits par défaut. |
| `app:create-user` | Ajoute un utilisateur à un foyer (prénom, email, mot de passe ≥ 8 caractères). |
| `app:seed-products <householdId>` | Ajoute au foyer les produits du catalogue qui lui manquent. ⚠️ Recrée ceux que le foyer avait supprimés. |

Le catalogue par défaut (~800 produits alimentaires classés par rayon, dont les « toujours en stock » : sel, poivre, huiles…) est dans `src/Seed/ProductCatalog.php`, éditable à la main.

Les idées de repas (« En manque d'inspi ? ») sont dans `src/Seed/MealCatalog.php` : ~200 recettes `[nom, saisons, produits, description?]`, jamais insérées automatiquement. Les noms de produits doivent correspondre à `ProductCatalog`.

---

## Authentification

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"prenom@email.fr","password":"..."}'
```

Le token (`{"token": "..."}`) est à envoyer dans `Authorization: Bearer <token>`. Son payload contient `username`, `firstName` et `householdId`.

Erreurs : toujours du JSON `{"error": "message en français"}` (+ `{"code", "message"}` pour les 401 de lexik).

---

## Endpoints

Toutes les routes (sauf login) sont **scopées au foyer** de l'utilisateur : chaque entité ciblée (et liée) est vérifiée, sinon 404.

### Rayons & produits

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/api/categories` | Rayons, dans l'ordre du magasin |
| `GET` | `/api/products` | Produits du foyer (+ `mealCount` : nb de repas qui l'utilisent) |
| `POST` | `/api/products` | Créer (`name`, `categoryId`, `isStaple`) — 409 + `product` si le nom existe déjà (insensible casse/accents) |
| `PUT` | `/api/products/{id}` | Modifier |
| `DELETE` | `/api/products/{id}` | Supprimer — 409 + `meals` si utilisé dans un repas |

### Repas

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/api/meals` | Repas avec produits et `lastPlannedAt` (dernier créneau ≤ aujourd'hui) |
| `POST` | `/api/meals` | Créer (`name`, `description`, `seasons[]`, `isFavorite`, `productIds[]` ≥ 1) |
| `PUT` | `/api/meals/{id}` | Modifier |
| `POST` | `/api/meals/{id}/favorite` | Basculer favori |
| `DELETE` | `/api/meals/{id}` | Supprimer (les créneaux qui l'utilisaient deviennent libres) |

Saisons : `spring`, `summer`, `autumn`, `winter` — aucune = toute l'année.

### Idées de repas

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/api/meal-ideas` | Idées au hasard (`count` 1–10, défaut 5 ; `exclude` = clés déjà vues, séparées par des virgules) → `{ ideas, available }` |

Les idées viennent du catalogue intégré (`src/Seed/MealCatalog.php`, ~200 recettes) : uniquement de saison et absentes de la bibliothèque du foyer. Chaque produit est rattaché au catalogue du foyer (`productId`, ou `null` + rayon par défaut si le foyer l'a supprimé). Rien n'est créé côté serveur : l'app crée le repas via `POST /api/meals`.

### Planning

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/api/meal-plans` | Plannings (résumés), plus récent d'abord |
| `GET` | `/api/meal-plans/{id}` | Détail avec `plannedMeals` |
| `POST` | `/api/meal-plans/generate` | Générer (`startDate` YYYY-MM-DD, `days` 1–31) — 409 si chevauchement |
| `POST` | `/api/meal-plans/{id}/shuffle` | Mélanger |
| `DELETE` | `/api/meal-plans/{id}` | Supprimer |
| `PUT` | `/api/planned-meals/{id}` | Remplacer (`mealId`) ou vider (`mealId: null`) un créneau |
| `POST` | `/api/planned-meals/{id}/lock` | Verrouiller / déverrouiller |
| `POST` | `/api/planned-meals/swap` | Échanger deux créneaux (`ids: [a, b]`) — repas et verrous |

### Liste de courses

Chaque écriture renvoie la liste complète (ou `null` s'il n'y en a pas).

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/api/shopping-list` | Liste active ou `null` |
| `POST` | `/api/shopping-list/generate` | Générer / régénérer (`mealPlanIds[]`) |
| `DELETE` | `/api/shopping-list` | Tout supprimer |
| `POST` | `/api/shopping-list/clear-checked` | Retirer les articles cochés |
| `POST` | `/api/shopping-list/items` | Ajouter `productId` ou un article ponctuel `customName` (+ `categoryId`), `quantity` |
| `PUT` | `/api/shopping-list/items/{id}` | Modifier `quantity` et/ou `isChecked` |
| `DELETE` | `/api/shopping-list/items/{id}` | Supprimer un article |

### Accueil

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/api/today` | Créneaux d'aujourd'hui et demain + `{ total, remaining }` de la liste |

---

## Logique métier

### Génération du planning (`MealPlanGeneratorService`)

Midi + soir tous les jours, sur une durée libre (1 à 31 jours). Les plannings d'un foyer ne se chevauchent jamais.

Pour chaque créneau, on choisit le repas de saison au meilleur score, calculé à la date du créneau :

| Critère | Points |
|---|---|
| Variété : jours entre le créneau et le placement le plus proche du repas (dernier créneau avant le planning, ou autre créneau du planning) | 0 à 30 (jamais planifié = 30) |
| Favori | +5 |
| Aléatoire (tiré à chaque créneau) | +0 à 15 |

Règles de distribution :
1. Pas deux fois le même repas dans la même semaine du planning
2. Jamais le même repas sur deux créneaux consécutifs

S'il n'y a pas assez de repas, la règle 1 est relâchée en premier (les repas les moins utilisés du planning passent alors en priorité, pour des répétitions équilibrées), la règle 2 seulement en dernier recours.

**Saison stricte** : un repas hors saison n'est jamais placé automatiquement (seulement à la main via « Remplacer »). Sans repas de saison, le créneau reste vide.

**Historique** : pas de table dédiée, un créneau passé non vide = repas mangé. On corrige l'historique en remplaçant ou vidant les créneaux passés.

**Mélanger** : re-tire tous les créneaux sauf les verrouillés, les vides (vidés volontairement) et les passés.

### Liste de courses (`ShoppingListGeneratorService`)

- Une seule liste active par foyer.
- On prend les créneaux non vides **à partir d'aujourd'hui** des plannings choisis : quantité d'un produit = nombre de créneaux dont le repas le contient (Lasagnes + Bolognaise → Viande hachée ×2). Les produits « toujours en stock » sont ignorés.
- Chaque article généré garde ses `sources` (« Lasagnes ×2, Bolognaise »).
- Régénération : les articles ajoutés à la main sont conservés tels quels (et un produit déjà ajouté à la main n'est pas dupliqué), les articles générés sont recalculés (un article coché reste coché), ceux qui ne sont plus nécessaires disparaissent.

---

## Architecture

```
src/
├── Command/            # seed-categories, create-household, create-user, seed-products
├── Controller/Api/     # un contrôleur par ressource + ApiTrait (foyer courant, JSON, erreurs, dates)
├── Entity/             # Household, User, Category, Product, Meal, MealPlan, PlannedMeal, ShoppingList, ShoppingListItem
├── EventListener/      # JWTCreatedListener (payload), ApiExceptionListener (erreurs JSON)
├── Repository/         # requêtes custom (usages produits, dernières dates planifiées, chevauchements…)
├── Seed/               # CategoryCatalog, ProductCatalog, MealCatalog (idées de repas)
└── Service/            # MealPlanGenerator, ShoppingListGenerator, MealIdeaService, ProductCatalogSeeder, ApiPresenter (entités → JSON)
```

### Modèle de données

```
Household 1──n User
Household 1──n Product ──n─1 Category (globale)
Household 1──n Meal  n──n Product            (meal_product)
Household 1──n MealPlan 1──n PlannedMeal ──n─1 Meal (null = créneau libre)
Household 1──1 ShoppingList 1──n ShoppingListItem ──n─1 Product (null = article ponctuel)
```

Suppression d'un foyer → cascade sur toutes ses données.

---

## Déploiement

`.env.local` sur le serveur :

```env
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<clé>
DATABASE_URL=<url_bdd_prod>
DEFAULT_URI=https://planeat-api.navity.aero
CORS_ALLOW_ORIGIN='^https://planeat-app\.navity\.aero$'
JWT_PASSPHRASE=<passphrase>
```

```bash
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console lexik:jwt:generate-keypair
php bin/console app:seed-categories
php bin/console app:create-household
php bin/console app:create-user
php bin/console cache:clear
```

Les logs d'erreur partent sur `php://stderr` (log Apache).

```apache
<VirtualHost *:80>
    ServerName planeat-api.navity.aero
    Redirect permanent / https://planeat-api.navity.aero/
</VirtualHost>
<VirtualHost *:443>
    ServerName planeat-api.navity.aero
    DocumentRoot /var/www/html/planeat/planeat-api/public

    <Directory /var/www/html/planeat/planeat-api/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/planeat-api.navity.aero/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/planeat-api.navity.aero/privkey.pem

    ErrorLog ${APACHE_LOG_DIR}/planeat_api_error.log
    CustomLog ${APACHE_LOG_DIR}/planeat_api_access.log combined

    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
```

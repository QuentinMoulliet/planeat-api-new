# 🍽️ Planeat (light) — Cahier des charges

Version allégée de Planeat : **on saisit nos repas et leurs ingrédients, on génère un planning, la liste de courses se crée toute seule.** Pas de stock, pas de scan, pas de grammage.

---

## 1. Périmètre

### Inclus
- Gestion des **repas** (nom, description, saisons, favori, ingrédients sans quantité)
- Catalogue de **produits** par foyer, classés par **rayon**, pré-rempli à la création du foyer
- **Générateur de planning** (midi + soir, durée libre) basé sur un système de points
- **Historique** des plannings passés
- **Liste de courses** générée depuis le planning, éditable, partagée dans le foyer
- **Foyer** : toutes les données sont partagées entre les membres d'un foyer
- **PWA** installable, dark mode, même logo / icônes / palette que l'ancienne app

### Exclus (par rapport à l'ancienne version)
Stock, scan code-barres, Open Food Facts, grammages / unités / conversions, déduction de stock, alertes péremption, pièces par paquet, tags de repas, temps de préparation, portions, confirmation « mangé / pas mangé », flux « mangé + restes », inscription in-app, fixtures, mode hors ligne avec synchronisation.

---

## 2. Stack

| | API (`planeat-api`) | App (`planeat-app`) |
|---|---|---|
| Langage / framework | PHP 8.3, Symfony 7.4 (skeleton API, sans Twig/Turbo/Stimulus/AssetMapper/Mailer) | Vue 3 (Composition API, `<script setup>`), Vite 8 |
| Données | Doctrine ORM 3, MariaDB 11.4, base `planeat_light` | Pinia, Vue Router |
| Auth | JWT `lexik/jwt-authentication-bundle` (30 jours) | Token en localStorage, redirect `/login` sur 401 |
| Divers | `nelmio/cors-bundle`, Symfony Validator | Tailwind 4, Lucide, police Inter |

**Git** : deux dépôts indépendants, `planeat-api-new` et `planeat-app-new` (GitHub `QuentinMoulliet`). Les anciens dépôts `planeat-api` / `planeat-app` restent intacts.

**Prod** : remplace l'ancienne app.
- App : `https://planeat.navity.aero`
- API : `https://planeat-api.navity.aero`

---

## 3. Modèle de données

```
Household 1──n User
Household 1──n Product ──n─1 Category (globale)
Household 1──n Meal  n──n Product            (table meal_product)
Household 1──n MealPlan 1──n PlannedMeal ──n─1 Meal (nullable = créneau vide)
Household 1──1 ShoppingList 1──n ShoppingListItem ──n─1 Product (nullable)
```

| Entité | Champs |
|---|---|
| **Household** | id, name, createdAt |
| **User** | id, email (unique), password, firstName, roles, household |
| **Category** | id, name (unique), sortOrder — *globale, gérée uniquement par commande* |
| **Product** | id, household, name, category (nullable), isStaple (« toujours en stock »), createdAt — *nom unique par foyer, insensible casse/accents* |
| **Meal** | id, household, name, description (nullable), seasons (json nullable : `spring/summer/autumn/winter`, null = toute l'année), isFavorite, createdAt, products (ManyToMany) |
| **MealPlan** | id, household, startDate, endDate, createdAt |
| **PlannedMeal** | id, mealPlan, date, slot (`lunch`/`dinner`), meal (nullable = créneau vide), isLocked — *unique (mealPlan, date, slot)* |
| **ShoppingList** | id, household (unique → une seule liste active), createdAt, updatedAt |
| **ShoppingListItem** | id, shoppingList, product (nullable), customName (nullable), category (nullable, pour article libre), quantity (int ≥ 1), isChecked, isManual |

> Un **Product** sert à la fois d'ingrédient de repas et d'article de courses (on peut ajouter « Papier toilette » à la liste à la main).

---

## 4. Règles métier

### 4.1 Produits & catégories
- **Catégories** = liste fixe globale de 15 rayons (les 14 de l'ancienne app + **Charcuterie / Traiteur**), créée par `app:seed-categories`. Pas de catégorie custom. L'ordre (`sortOrder`) = ordre d'affichage dans la liste de courses.
- **Produits** pré-remplis automatiquement à la création d'un foyer depuis un catalogue très exhaustif (801 produits, **uniquement alimentaires**).
- On peut librement créer n'importe quel produit, y compris non alimentaire (« Papier toilette » → rayon Hygiène), depuis l'onglet Produits ou directement depuis la liste de courses.
- Les produits basiques (sel, poivre, huile, vinaigre…) sont pré-marqués **« toujours en stock »** → jamais ajoutés automatiquement à la liste de courses. Modifiable.
- CRUD complet sur les produits du foyer (nom, rayon, toujours en stock).
- **Suppression** d'un produit utilisé dans un repas : **refusée**, avec la liste des repas concernés.
- Lors de la saisie d'un ingrédient dans un repas : autocomplétion sur les produits du foyer ; si inexistant, création à la volée (avec choix du rayon).

### 4.2 Repas
- Champs : nom (obligatoire), description, saisons (0 = toute l'année), favori, produits (au moins 1).
- Un produit apparaît **au plus une fois** par repas (pas de quantité : « oignon », pas « 2 oignons »).
- Liste filtrable (Tous / Favoris / De saison) + recherche par nom ou ingrédient.
- Suppression d'un repas : retiré des plannings (créneaux concernés → vides).
- **En manque d'inspi ?** : catalogue intégré et gratuit de ~200 recettes classiques (`MealCatalog`), dont les ingrédients correspondent au catalogue produits. Le bouton « Idées » propose des recettes au hasard, de saison et absentes de la bibliothèque : « Ajouter » (direct), « Modifier » (formulaire prérempli) ou « Autres idées ». Aucune API externe, aucun repas inséré automatiquement.

### 4.3 Génération du planning
- Paramètres : date de début (par défaut : lendemain de la fin du dernier planning, sinon aujourd'hui) + durée libre (raccourcis 7 / 14 jours).
- **Toujours midi + soir**, tous les jours.
- Un planning **ne peut pas chevaucher** un planning existant (HTTP 409).
- **Saison stricte** : un repas hors saison (selon la date du créneau) n'est **jamais** placé automatiquement. Il reste possible de le placer à la main via « Remplacer ».

**Score de chaque repas** (calculé à la génération) :

| Critère | Points |
|---|---|
| Jamais planifié | +30 |
| Sinon : jours entre le créneau et le placement le plus proche du repas (dernier créneau avant le planning, ou autre créneau du même planning) | +1 / jour, max 30 |
| Favori | +5 |
| Aléatoire, tiré à chaque créneau (pour que deux générations/mélanges diffèrent) | +0 à 15 |

> « Dernière fois mangé » = dernier **PlannedMeal non vide** contenant ce repas, toutes périodes confondues (y compris plannings futurs déjà créés). Plus besoin d'historique séparé ni de confirmation.

**Distribution** (reprise de l'existant) :
1. Pas deux fois le même repas dans la même semaine du planning
2. Pas le même repas deux créneaux d'affilée
3. Si pas assez de repas : on relâche la règle 1 (les repas les moins utilisés du planning passent en priorité, pour des répétitions équilibrées), jamais la règle 2 (sauf s'il n'y a qu'un seul repas)
4. Si aucun repas de saison disponible : créneau laissé vide

### 4.4 Actions sur le planning
| Action | Détail |
|---|---|
| **Mélanger** | Re-génère tous les créneaux non verrouillés, non vides* et non passés du planning |
| **Verrouiller** | Le créneau n'est plus touché par « Mélanger » |
| **Remplacer** | Choix manuel d'un repas (recherche), tous repas confondus (même hors saison) |
| **Échanger** | Sélection de deux créneaux → ils permutent |
| **Vider** | Créneau sans repas (resto, restes, invités…) — ignoré par la liste de courses |
| **Supprimer le planning** | Avec confirmation |

\* Un créneau vidé volontairement reste vide au mélange.

- Les créneaux **passés restent modifiables** (remplacer / vider) pour corriger l'historique : si un repas n'a pas été mangé, on le vide ou on le remplace par ce qu'on a vraiment mangé, et on peut le replacer dans un planning suivant via « Remplacer ».

### 4.5 Historique
- Navigation horizontale entre les plannings (dates de début → fin), les 5 plus récents en puces, tous accessibles via « Historique ».
- Le planning courant (contenant aujourd'hui) est affiché par défaut ; sinon le prochain ; sinon le dernier.

### 4.6 Liste de courses
- **Une seule liste active** par foyer.
- **Génération** : choix d'un ou plusieurs plannings → on prend tous les créneaux non vides **à partir d'aujourd'hui**, et pour chaque produit (hors « toujours en stock ») : **quantité = nombre de créneaux dont le repas contient ce produit**.
  - Ex : Lasagnes + Bolognaise → Viande hachée ×2.
  - Chaque article généré affiche son origine : « Lasagnes ×2, Bolognaise ».
- **Régénération** si une liste existe :
  - les articles **ajoutés à la main** sont conservés tels quels (un produit déjà ajouté à la main n'est pas dupliqué) ;
  - les articles **générés** sont recalculés (si un article recalculé était coché, il reste coché) ;
  - un article généré supprimé à la main réapparaîtra s'il est toujours nécessaire.
- **Édition** : cocher/décocher, modifier la quantité (stepper), supprimer, ajouter un produit (recherche dans le catalogue, ou création à la volée d'un nouveau produit avec son rayon) ou un article libre ponctuel (avec rayon optionnel, non enregistré au catalogue). Ajouter un produit déjà présent → quantité +1.
- Affichage **groupé par rayon** (ordre `sortOrder`), articles libres sans rayon dans « Divers » en fin de liste. Articles cochés barrés et descendus en bas de leur rayon. Compteur `cochés / total`.
- **Vider la liste** : retirer seulement les cochés, ou tout supprimer.
- **Partage** : rafraîchissement automatique toutes les ~10 s tant que l'écran Courses est visible + au retour sur l'app. Cochage optimiste (instantané côté UI).
- **Hors ligne** : affichage de la dernière liste chargée (lecture seule) avec un bandeau « Hors ligne ».

---

## 5. API

Toutes les routes (sauf login) exigent `Authorization: Bearer <token>` et sont **scopées au foyer** de l'utilisateur (vérification d'ownership sur chaque entité, y compris les entités liées).

| Méthode | Route | Description |
|---|---|---|
| `POST` | `/api/login` | Token JWT (payload : `username`, `firstName`, `householdId`) |
| `GET` | `/api/categories` | Liste des rayons |
| `GET` | `/api/products` | Produits du foyer (+ nb de repas qui les utilisent ; la recherche se fait côté app) |
| `POST` | `/api/products` | Créer |
| `PUT` | `/api/products/{id}` | Modifier |
| `DELETE` | `/api/products/{id}` | Supprimer (409 si utilisé, avec les repas) |
| `GET` | `/api/meals` | Repas du foyer |
| `POST` | `/api/meals` | Créer (`productIds[]`) |
| `PUT` | `/api/meals/{id}` | Modifier |
| `POST` | `/api/meals/{id}/favorite` | Basculer favori |
| `DELETE` | `/api/meals/{id}` | Supprimer |
| `GET` | `/api/meal-plans` | Liste des plannings (résumé) |
| `GET` | `/api/meal-plans/{id}` | Détail avec créneaux |
| `POST` | `/api/meal-plans/generate` | Générer (`startDate`, `days`) |
| `DELETE` | `/api/meal-plans/{id}` | Supprimer |
| `POST` | `/api/meal-plans/{id}/shuffle` | Mélanger |
| `PUT` | `/api/planned-meals/{id}` | Remplacer / vider (`mealId` ou `null`) |
| `POST` | `/api/planned-meals/{id}/lock` | Verrouiller / déverrouiller |
| `POST` | `/api/planned-meals/swap` | Échanger deux créneaux (`ids[2]`) |
| `GET` | `/api/shopping-list` | Liste active (`null` si aucune) |
| `POST` | `/api/shopping-list/generate` | Générer / régénérer (`mealPlanIds[]`) |
| `DELETE` | `/api/shopping-list` | Tout supprimer |
| `POST` | `/api/shopping-list/clear-checked` | Retirer les cochés |
| `POST` | `/api/shopping-list/items` | Ajouter (`productId` ou `customName` + `categoryId`, `quantity`) |
| `PUT` | `/api/shopping-list/items/{id}` | Modifier quantité / coché |
| `DELETE` | `/api/shopping-list/items/{id}` | Supprimer |
| `GET` | `/api/meal-ideas` | Idées de repas au hasard (`count`, `exclude`) |
| `GET` | `/api/today` | Créneaux d'aujourd'hui et demain + nb d'articles restants (Accueil) |

### Commandes console
| Commande | Rôle |
|---|---|
| `app:seed-categories` | Crée / met à jour les rayons globaux (idempotent) |
| `app:create-household` | Crée un foyer → **pré-remplit automatiquement son catalogue produits** |
| `app:create-user` | Crée un utilisateur rattaché à un foyer |
| `app:seed-products <householdId>` | Ajoute au foyer les produits du catalogue manquants (⚠️ recrée ceux supprimés) |

Le catalogue est un fichier PHP lisible et éditable (`src/Seed/ProductCatalog.php`) : `nom → rayon, toujours en stock`.

**Pas de fixtures.**

---

## 6. App

### Navigation — bottom bar 5 onglets
| Onglet | Route | Contenu |
|---|---|---|
| **Accueil** | `/` | Bonjour {prénom}, repas d'aujourd'hui (midi/soir) et de demain, raccourci liste de courses (« 12 articles restants ») |
| **Repas** | `/meals` | Bibliothèque de repas (filtres, recherche, favori) + bouton « Idées » |
| **Produits** | `/products` | Catalogue produits du foyer groupé par rayon, recherche, CRUD, « toujours en stock » |
| **Planning** | `/planner` | Sélecteur de plannings, jours (midi/soir), actions par créneau, Générer, Mélanger, Générer la liste de courses |
| **Courses** | `/shopping` | Liste par rayon, cases à cocher, quantités, ajout, vider |

+ `/login` (hors onglets). Header : thème clair/sombre, déconnexion.

### Design (repris de l'existant)
- Palette sauge / terracotta / crème, variables CSS + `@theme` Tailwind, dark mode (préférence système + toggle, script anti-flash)
- Cards `rounded-2xl`, cibles tactiles ≥ 44 px, bottom sheets pour toutes les modales
- Logo `logo_1.png`, icônes PWA `pwa/icon-192.png` / `icon-512.png`, manifest (`theme_color #7B9A6D`, `background_color #FAF8F4`)
- Service worker : network-first sur la navigation, cache-first sur les assets, API jamais en cache (le hors-ligne de la liste passe par le localStorage du store)

### Conventions (reprises de l'existant)
- Vues = orchestration ; une modale = un composant `*Sheet` (bottom sheet) ; un store Pinia par domaine (`auth`, `catalog`, `meals`, `planner`, `shopping`) ; wrapper `api.js` (JWT + 401).

---

## 7. Plan de réalisation (✅ terminé)

1. **API — socle** : skeleton Symfony, config (CORS, JWT, timezone), entités + migration, commandes (catégories, foyer, user, seed produits), catalogue produits
2. **API — repas & produits** : CRUD + validations + ownership
3. **API — planning** : service de scoring, générateur, actions (shuffle, lock, swap, remplacer/vider)
4. **API — courses** : générateur (agrégation, régénération), CRUD items, `/today`
5. **App — socle** : projet Vite, design system, PWA, login, layout + navigation
6. **App — Produits & Repas**
7. **App — Planning**
8. **App — Courses & Accueil** (polling, hors-ligne lecture seule)
9. **README** API + App (installation, commandes, déploiement Apache `*.navity.aero`)

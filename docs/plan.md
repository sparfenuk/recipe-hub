# Recipe Hub — Project Plan

**Companion to:** `recipe-app-technical-specification.md`
**Mode:** Solo developer
**Created:** 2026-05-07
**Estimated total:** 6–8 weeks at 1–2 tasks/day (MVP through Layer 5)

---

## How to use this plan

- Tasks run **strictly sequentially** by layer. Inside a layer they could parallelize on a team, but you're solo — do them in listed order.
- Each task is a single PR/branch, sized for **half a day to a full day**. If you hit something taking >1 day, split it.
- Branch naming: `feat/L1.2-auth-scaffolding`, `fix/L3.4-recipe-form-validation`, etc.
- After every task: tests green, app boots, a tiny piece of new behavior is demoable. Then merge and check the box.
- Stop at the end of each layer and play with the app for 10 minutes before continuing. It's a forcing function for "did I actually finish?"
- Tracking: just check the boxes in this file as you go. No external tooling needed at solo scale.

---

## Sizing & quality gates

A task is "done" when:

- [ ] Code merged to `main` (or default branch).
- [ ] CI green: Pint, Larastan level 6, Pest tests.
- [ ] Manual smoke check: the new behavior works in the running Sail stack.
- [ ] No new TODOs left without a tracking note.

If you can't tick all four, the task isn't done — keep going or split off a follow-up.

---

## Status legend

- [ ] Not started
- [~] In progress (only one task at a time)
- [x] Done
- [!] Blocked (with note)

---

## Layer 0 — Bootstrap

> Goal: empty Laravel app that boots in Sail with CI green. Anything that's true once for the whole project lives here.

- [x] **L0.1 — Scaffold project** *(completed 2026-05-07)*
  - **Project location:** `/home/sparf/recipe-hub` inside WSL2 (Ubuntu 22.04). From Windows: `\\wsl$\Ubuntu-22.04\home\sparf\recipe-hub`. Decision recorded: keeping the project on the WSL ext4 filesystem for fast I/O per spec §14.7.
  - Laravel **12.58** scaffolded via `composer create-project laravel/laravel`.
  - Sail installed with services: MySQL 8.4, Redis 7-alpine, MeiliSearch (latest), Mailpit (latest). PHP runtime in container: **8.5.5**.
  - `.env` configured: `APP_NAME="Recipe Hub"`, `APP_TIMEZONE=UTC`, `DB_DATABASE=recipe_hub`. `.env.example` mirrored so future clones get the same defaults. Stray SQLite file removed.
  - `sail up -d` brings up 5 containers, all healthy. `sail artisan migrate` applies the 3 default migrations on MySQL.
  - **Smoke checks passed:**
    - `curl http://localhost` → HTTP 200, `X-Powered-By: PHP/8.5.5`, `<title>Recipe Hub</title>`.
    - `curl http://localhost:7700/health` → `{"status":"available"}`.
    - Mailpit UI reachable at `http://localhost:8025`.
    - `sail artisan migrate:status` shows 3 ran migrations.
  - Spec + plan copied into `docs/spec.md` and `docs/plan.md` — these are the canonical living copies going forward.
  - Git initialized on `main` branch; `user.email` + `user.name` set repo-locally; `core.autocrlf=input` for WSL.
  - **GitHub push deferred** — `gh` CLI is not installed locally. To push: create an empty `recipe-hub` repo on GitHub, then `git remote add origin git@github.com:<you>/recipe-hub.git && git push -u origin main`.

- [x] **L0.2 — Dev tooling** *(completed 2026-05-07)*
  - **Pest 3.8** installed with `pestphp/pest-plugin-laravel`. PHPUnit example tests converted to Pest syntax. `tests/Pest.php` created with `TestCase` binding for Feature tests.
  - **Pint 1.24** (was already a dev dep) — `pint.json` created with `laravel` preset. All files pass `--test`.
  - **Larastan 3.9** installed — `phpstan.neon` at level 6, targeting `app/`. Zero errors.
  - **Telescope 5.20** installed as dev-only dependency. Registered conditionally via `AppServiceProvider::register()` (only in `local` env + class exists guard). Migration ran. `TELESCOPE_ENABLED` added to `.env.example`.
  - `.editorconfig` extended with JS/JSON indent rule. `.gitattributes` extended with export-ignore for dev files.
  - Composer scripts added: `composer pint`, `composer larastan`.
  - **GitHub Actions** workflow `.github/workflows/ci.yml`: Pint `--test` → Larastan → Pest, with MySQL 8.4 service, PHP 8.4, Composer cache. Triggers on push to `main` and PRs.
  - All quality gates verified green inside Sail: Pint, Larastan, Pest (2 tests, 2 assertions).

- [x] **L0.3 — Runtime packages** *(completed 2026-05-07)*
  - **Composer** (13 packages): `livewire/livewire ^3.8`, `livewire/flux ^2.14`, `filament/filament ^3.3`, `spatie/laravel-permission ^7.4`, `spatie/laravel-medialibrary ^11.22`, `laravel/scout ^11.1`, `meilisearch/meilisearch-php ^1.16`, `intervention/image ^4.0`, `laravel/horizon ^5.46`, `owen-it/laravel-auditing ^14.0`, `barryvdh/laravel-dompdf ^3.1`, `laravel/fortify ^1.36`, `laravel/sanctum ^4.3`.
  - **`maatwebsite/excel` deferred:** `phpoffice/phpspreadsheet` requires PHP `<8.5.0`. Only needed for L6.2 (stretch CSV import UI); USDA artisan command uses native PHP CSV. Will revisit when phpspreadsheet adds PHP 8.5 support.
  - **NPM:** `alpinejs`, `@alpinejs/focus`, `@alpinejs/persist`, `apexcharts`. Tailwind CSS 4 + `@tailwindcss/vite` kept from Laravel 12 scaffold (forms/typography plugins built-in; Flux UI requires v4).
  - **Tailwind CSS version change:** spec updated from v3 → v4. Reason: Laravel 12 defaults, Flux UI dependency, plugins built-in.
  - Vite builds `app.css` + `app.js` (Alpine.js + plugins bootstrapped).
  - Base layout `components/layouts/app.blade.php` with slate/emerald theme, Livewire styles/scripts.
  - Welcome page at `/` renders styled "Hello" with Alpine.js toggle proving stack integration.
  - Filament admin panel at `/admin` (login page) — emerald primary color, auto-discovered resources.
  - Horizon dashboard at `/horizon` returns HTTP 200.
  - Fortify published: config, service provider, user actions (create, reset password, update profile/password).
  - `.env.example` updated: `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `REDIS_HOST=redis`, Mailpit SMTP, MeiliSearch host.
  - All quality gates green: Pint (41 files pass), Larastan level 6 (0 errors), Pest (2 tests, 2 assertions).

---

## Layer 1 — Foundation

> Goal: an authenticated user can log in, switch language, and see an empty admin shell. The skeleton is in place for everything else.

- [x] **L1.1 — Public layout & theme** *(completed 2026-05-07, landed in L0.4 commit 4d4c8ae)*
  - Blade layout `layouts/app.blade.php` with header (logo wordmark "Recipe Hub", nav placeholder, locale switcher slot, login/register links), main slot, footer.
  - `layouts/guest.blade.php` for auth pages.
  - Tailwind config with slate + emerald palette, `@tailwindcss/forms`, `@tailwindcss/typography`.
  - Heroicons Blade components installed.

- [x] **L1.2 — Auth scaffolding (Fortify)** *(completed 2026-05-07)*
  - Register, login, logout, email verification, password reset, password confirmation flows via Fortify.
  - 6 Blade views styled with guest layout (emerald/slate theme).
  - User model implements `MustVerifyEmail`; Fortify `emailVerification` feature enabled; Mailpit catches verification emails locally.
  - Cabinet placeholder at `/cabinet` behind `auth`+`verified` middleware.
  - 23 Pest feature tests (43 assertions) covering all auth flows including rate limiting.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L1.3 — Roles & permissions** *(completed 2026-05-07)*
  - `spatie/laravel-permission` config & migration published, migration ran.
  - `RoleSeeder` creates `guest`, `user`, `editor`, `admin` roles with 13 granular permissions (`recipe.view/create/update/delete/publish`, `ingredient.view/create/update/delete`, `media.upload`, `user.manage/impersonate`, `admin.access`).
  - New users assigned `user` role on registration via `CreateNewUser` action.
  - `Gate::before` in `AppServiceProvider` grants admin super-access to all abilities.
  - User model implements `FilamentUser` with `canAccessPanel()` gated by `admin` role.
  - 10 Pest tests (registered user has `user` role, admin gate, editor permissions, Filament panel access/deny, seeder idempotency).
  - Quality gates green: Pint, Larastan level 6, Pest (33 tests, 58 assertions).

- [x] **L1.4 — Localization scaffolding** *(completed 2026-05-07)*
  - `lang/en.json`, `lang/uk.json` with all UI strings (31 keys each). `lang/{en,uk}/auth.php`, `validation.php`, `passwords.php`, `pagination.php` copied from framework and translated.
  - Custom domain files: `lang/{en,uk}/recipes.php`, `calculator.php`, `cabinet.php`, `nav.php`.
  - `SetLocale` middleware on `web` group: `?locale=` query param (sets cookie + redirects) → `locale` cookie → `Accept-Language` header → `en` fallback. Cookie: 1-year, SameSite=Lax.
  - `LocaleSwitcher` Livewire component in header (English / Українська) with Alpine.js dropdown. Uses `<a href="?locale=xx">` links handled by middleware.
  - All existing Blade views (auth, layouts, welcome, cabinet) wrapped with `__()` for translation.
  - Filament admin unaffected — it uses its own middleware stack, not the `web` group.
  - 10 Pest tests (43 tests total, 94 assertions): middleware behavior, drift-guard for JSON + PHP keys, locale switcher rendering, translated content verification.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L1.5 — Filament admin shell** *(completed 2026-05-07)*
  - Filament panel at `/admin` with emerald theme, login page, auto-discovery for resources/pages/widgets.
  - `ForceEnglish` middleware on the admin panel middleware stack forces `app()->setLocale('en')` regardless of cookie or Accept-Language header.
  - Login gated by `admin` role via `User::canAccessPanel()` (already in place from L1.3).
  - Default Filament widgets replaced with custom `WelcomeWidget` placeholder on the dashboard.
  - 7 Pest tests (50 total, 105 assertions): login page access, guest redirect, role gating, forced English locale, widget rendering.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L1.6 — User profile + cabinet shell** *(completed 2026-05-07)*
  - `user_profiles` migration with all columns (`sex`, `birth_date`, `height_cm`, `weight_kg`, `activity_level`, `daily_kcal_target`, `p_pct`/`f_pct`/`c_pct` with defaults 30/30/40, `units_pref`, `timezone`). `avatar_path` added to `users` table.
  - `UserProfile` model with casts. `User::profile()` HasOne relation. Profile auto-created on registration in `CreateNewUser`.
  - `/cabinet` route renders `CabinetDashboard` Livewire component (name, email, nav cards for profile/favorites/calculations).
  - `/cabinet/profile` route renders `ProfileForm` Livewire component: name editing, avatar upload (with preview, remove, 2MB max image validation), units preference (metric/imperial).
  - EN/UK translations for all cabinet strings (17 keys each).
  - 13 Pest tests (63 total, 140 assertions): guest redirect, dashboard render, profile auto-creation, name update, validation, units preference, avatar upload/remove, non-image/oversized rejection, model relations, default macro splits.
  - Quality gates green: Pint, Larastan level 6, Pest.

---

## Layer 2 — Reference data

> Goal: the ingredient catalog is populated and editable. Recipes can't exist yet, but admin can browse and edit ~600 ingredients with full nutrition data.

- [x] **L2.1 — Units + UnitConverter** *(completed 2026-05-07)*
  - `units` table migration + `Unit` model with `isMass()`/`isVolume()`/`isCount()` helpers.
  - `UnitSeeder` with 11 units (`g`, `kg`, `mg`, `ml`, `l`, `tsp`, `tbsp`, `cup`, `oz`, `lb`, `piece`), idempotent via `updateOrCreate`.
  - `App\Services\Nutrition\UnitConverter::toGrams(amount, unit, densityGPerMl, pieceWeightG)`: mass→g via factor, volume→g via factor×density, count→g via piece weight. Throws on missing density/weight.
  - 29 Pest tests (91 total, 207 assertions). Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L2.2 — Taxonomies** *(completed 2026-05-07)*
  - 5 migrations: `ingredient_categories` (id, slug, name, parent_id), `cuisines` (id, slug, name), `tags` (id, slug, name, type enum), `allergens` (id, slug, name), `categories` (id, slug, name, parent_id). All with unique slug constraints, no timestamps.
  - 5 models: `IngredientCategory`, `Cuisine`, `Tag`, `Allergen`, `Category`. Hierarchical models support parent/child. Tag has `isDiet()`/`isCuisine()`/`isMisc()` helpers.
  - 5 seeders (idempotent): 16 ingredient categories, 20 cuisines, 18 tags (10 diet + 8 misc), 9 allergens (from spec), 14 recipe categories.
  - 5 Filament resources under "Taxonomies" nav group with ManageRecords modal CRUD. Tag resource has type filter + colored badges.
  - 44 new Pest tests (135 total, 366 assertions). Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L2.3 — Ingredients CRUD (no import yet)** *(completed 2026-05-08)*
  - `ingredients` table migration with all nutrition columns (`kcal_per_100g`, `protein_g`, `fat_g`, `saturated_fat_g`, `carbs_g`, `sugar_g`, `fiber_g`, `sodium_mg`), `density_g_per_ml`, `piece_weight_g`, `default_unit_id`, `is_active`, `source`, `created_by`.
  - `ingredient_aliases` table (one-to-many), `ingredient_allergen` pivot, `ingredient_tag` pivot.
  - `Ingredient` model with relations (category, defaultUnit, creator, aliases, allergens, tags) + `IngredientAlias` model.
  - `IngredientFactory` for testing.
  - Filament `IngredientResource` with full-page CRUD (List/Create/Edit): form with 3 sections (basic info, nutrition per 100g, allergens & tags), aliases repeater, auto-slug, `created_by` set on create. Table with search, category filter, active status filter, nutrition columns.
  - 13 Pest tests (148 total, 421 assertions): CRUD, unique slug, allergens, tags, aliases, model relations, non-admin access denied, category filter, active filter, name search.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L2.4 — USDA curation script** *(completed 2026-05-08)*
  - PHP script `scripts/curate-usda.php`: reads USDA FoodData Central bulk CSV download (`food.csv`, `food_nutrient.csv`, `food_category.csv`).
  - Filters by `data_type` (foundation_food + sr_legacy_food), drops categories (baby foods, fast foods, sweets, restaurant, etc.), drops keywords (prepared, restaurant, entree, commercially prepared, frozen meal, MRE).
  - Maps 8 USDA nutrient IDs → app columns (kcal, protein, fat, sat fat, carbs, sugar, fiber, sodium). Maps USDA food categories → app `ingredient_categories` slugs.
  - Deduplication: groups by first 3 significant words (numbers stripped), keeps ≤2 active per group, marks rest inactive. Foundation foods preferred over SR Legacy.
  - Name normalization: ALL CAPS → title case, first letter capitalized.
  - Outputs `database/seeders/data/usda-curated.csv` with columns: fdc_id, name, category_slug, nutrition (8 cols), is_active.
  - Test fixtures in `tests/fixtures/usda/` (18 test rows) verify: category drops, keyword drops, branded exclusion, deduplication (4 beef ground → 2 active + 2 inactive).
  - `storage/app/usda` added to `.gitignore` (raw USDA files too large to commit).
  - Usage: `sail php scripts/curate-usda.php storage/app/usda`.

- [x] **L2.5 — `ingredients:import-usda` artisan command** *(completed 2026-05-11)*
  - `ImportUsdaIngredients` artisan command: `ingredients:import-usda {path?} {--dry-run} {--chunk=1000} {--enrich}`.
  - Streams curated CSV line-by-line; default path `database/seeders/data/usda-curated.csv`.
  - Idempotent upsert keyed on `source = "USDA FDC #<fdc_id>"`. Category slug → ID cached for performance.
  - Unique slug generation with counter fallback. Transactional chunked processing.
  - Row-level error log to `storage/logs/usda-import-{date}.log` with row numbers.
  - 5-row fixture CSV at `tests/fixtures/usda-import.csv`.
  - 12 Pest tests (160 total, 457 assertions): import, idempotency, dry-run, bad category, chunk option.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L2.6 — Enrichment data files** *(completed 2026-05-11)*
  - `database/seeders/data/densities.json` — 56 density rules for oils, dairy, syrups, flour, sugar, condiments, etc. Each entry maps keywords to `density_g_per_ml` and `default_unit`.
  - `database/seeders/data/allergen-rules.json` — 9 keyword-based allergen rules + 4 category-based rules. Covers all 9 allergens (gluten, lactose, nuts, soy, eggs, fish, shellfish, sesame, mustard).
  - `database/seeders/data/aliases.json` — 101 alias rules covering ~200+ synonym pairs (US/UK terms, spice names, regional variants).
  - `ImportUsdaIngredients` `--enrich` flag now loads all three files and applies: density + default unit, allergen flags (keyword + category), aliases (via `firstOrCreate`). All idempotent.
  - 7 new Pest tests (167 total, 477 assertions): density application, allergen keyword/category matching, alias creation, no-enrich baseline, idempotency, enriched count output.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L2.7 — `IngredientSeeder`** *(completed 2026-05-11)*
  - `IngredientSeeder` calls `ingredients:import-usda --enrich` via `Artisan::call()`, wired into `DatabaseSeeder` after `CategorySeeder`.
  - `php artisan migrate:fresh --seed` produces a working DB with 14 ingredients (12 active, 2 inactive), 3 with density, 7 with allergens. Count scales when the curated CSV grows from the full USDA download.
  - Smoke check: 5 ingredients (egg, milk, rice, salmon, broccoli) verified — nutrition values match USDA source exactly.
  - 7 Pest tests (174 total, 493 assertions): seed count, idempotency, enrichment, nutrition accuracy (egg + broccoli), active/inactive split, migrate:fresh --seed flow.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L2.8 — Media library wiring** *(completed 2026-05-11)*
  - `spatie/laravel-medialibrary` migration published & ran. Config published with `queue_name = image-processing` and `default_loading_attribute_value = lazy`.
  - `filament/spatie-laravel-media-library-plugin` installed for Filament integration.
  - Conversions defined on models: `thumb` (200x200, nonQueued), `card` (600x400, queued), `full` (1600w, queued).
  - Horizon `supervisor-image` added for `image-processing` queue (256MB memory, 120s timeout, 3 tries).
  - `Ingredient` model: `HasMedia` interface + `InteractsWithMedia` trait, `photo` single-file collection with 3 conversions. Filament `IngredientResource` updated with `SpatieMediaLibraryFileUpload` form field and `SpatieMediaLibraryImageColumn` table column.
  - `User` model: `HasMedia` interface + `InteractsWithMedia` trait, `avatar` single-file collection with `thumb` conversion.
  - `ProfileForm` migrated from manual `Storage` to medialibrary (`addMedia`/`clearMediaCollection`). Avatar upload and removal work via medialibrary.
  - `.env.example` updated with `MEDIA_QUEUE=image-processing`.
  - 9 Pest tests (183 total, 513 assertions): ingredient photo collection, single-file behavior, 3 conversions, user avatar collection, single-file, thumb generation, media cleanup on delete, Filament upload, queue config.
  - Quality gates green: Pint, Larastan level 6, Pest.

---

## Layer 3 — Core features

> Goal: an admin can create a recipe with ingredients, see auto-computed nutrition, and a logged-in user can see their suggested daily calorie target.

- [x] **L3.1 — Recipe schema** *(completed 2026-05-11)*
  - 4 migrations: `recipes` (slug, title, summary, description, servings, times, difficulty enum, status enum, is_featured, 10 cached nutrition columns, nutrition_cached_at, published_at, soft deletes, composite index on status+published_at), `recipe_ingredients` (amount, unit_id, grams_override, note, is_optional, group_label, position), `recipe_steps` (position, body), `recipe_tag` pivot (composite PK).
  - 3 models: `Recipe` (HasMedia with hero+gallery collections, 3 conversions, SoftDeletes, relations to author/category/cuisine/recipeIngredients/steps/tags), `RecipeIngredient` (relations to recipe/ingredient/unit), `RecipeStep` (HasMedia with step_photo collection).
  - `RecipeFactory` with `published()`, `archived()`, `featured()` states.
  - 22 Pest tests (205 total, 561 assertions): CRUD, relations, pivot fields, ordering, cascade deletes, soft deletes, enum validation, media collections, factory states.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L3.2 — `NutritionCalculator` service** *(completed 2026-05-11)*
  - `NutritionCalculator::totalsFor(Recipe): NutritionTotals` — sums kcal, P/F/C, fiber for all non-optional ingredients, divides by servings for per-serving values.
  - `NutritionTotals` readonly DTO with recipe totals + per-serving values.
  - Uses `UnitConverter::toGrams()` for mass/volume/count resolution; `grams_override` bypasses conversion when set.
  - Skips optional ingredients, handles null nutrition values gracefully.
  - 7 Pest tests (212 total, 621 assertions): 3 hand-computed reference recipes (simple mass / volume+density / grams_override), optional ingredient skip, empty recipe, null nutrition, per-serving division. All within ±1% tolerance.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L3.3 — Nutrition recompute job** *(completed 2026-05-12)*
  - `RecalculateRecipeNutrition` queued job (ShouldBeUnique by recipe ID): computes totals via `NutritionCalculator`, stores all 10 cached nutrition columns + `nutrition_cached_at`, uses `saveQuietly()` to prevent observer loops.
  - `RecipeObserver::saved()` dispatches on every recipe create/update.
  - `RecipeIngredientObserver::saved()`/`deleted()` dispatches when ingredient rows change.
  - `IngredientObserver::updated()` dispatches for all recipes using the ingredient when nutrition-relevant columns change (kcal, P/F/C, fiber, density, piece weight). Non-nutrition edits (name, slug) are ignored.
  - Observers registered in `AppServiceProvider::boot()`.
  - 9 Pest tests (221 total, 648 assertions): dispatch on recipe create/update, ingredient row add/delete, bulk ingredient nutrition change, non-nutrition skip, job stores values + cached_at, graceful handling of deleted recipe, end-to-end integration (edit ingredient → both recipes recomputed).
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L3.4 — Filament Recipe resource** *(completed 2026-05-12)*
  - `RecipeResource` with full-page CRUD (List/Create/Edit) under "Catalog" nav group.
  - Form: 5 sections — Basic info (title with auto-slug, summary, description rich editor, category, cuisine, difficulty, servings, prep/cook time, status, featured toggle), Ingredients (repeater with ingredient picker + amount + unit + note + optional flag + group label, reorderable/collapsible/cloneable), Steps (repeater with body + step photo upload via medialibrary, reorderable/collapsible), Tags (multi-select), Nutrition (read-only per-serving + total values, auto-refreshes after save via synchronous recompute).
  - `total_time_min` auto-computed from `prep_time_min + cook_time_min` on create/edit. `published_at` auto-set when status changes to published.
  - List view: title (searchable), status/difficulty badges with colors, category, cuisine, kcal/serving, servings, featured, author, created_at. Filters: status, category, cuisine. Default sort: newest first.
  - Row actions: edit, duplicate (with unique slug generation), delete. Bulk actions: publish (sets published_at), archive, duplicate, delete.
  - `duplicateRecipe()` copies recipe, ingredients, steps, and tags with draft status and unique slug (handles soft-deleted slug collisions).
  - `getEloquentQuery()` includes soft-deleted records; edit page shows force-delete and restore actions.
  - 22 Pest tests (243 total, 752 assertions): CRUD, soft delete, slug uniqueness, published_at on publish, total_time_min on create/edit, tags, status/category/cuisine filters, title search, bulk publish/archive, duplicate with slug collision, non-admin denied, nutrition section render, ingredient/step repeater save, nutrition recompute after save.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L3.5 — Recipe media** *(completed 2026-05-13)*
  - Hero photo + gallery via medialibrary.
  - Step photos already covered by L3.4.
  - Filament uploader with drag-drop multi-file for the gallery.

- [x] **L3.6 — Cabinet: health profile** *(completed 2026-05-13)*
  - `HealthForm` Livewire component on `/cabinet/health`.
  - Inputs: sex, birth date, height, weight, activity level (sedentary → very active).
  - Live BMR via Mifflin-St Jeor → suggested daily kcal target shown beside the form.
  - "Use suggested" button writes the value to the user's daily target. Manual override allowed.
  - Pest tests on the BMR formula and the form action.

- [x] **L3.7 — Cabinet: macro targets** *(completed 2026-05-13)*
  - `MacroTargetForm` Livewire component on the same page.
  - Inputs: P %, F %, C %. Live validation: must sum to 100. Defaults to 30/30/40.
  - Saves to `user_profiles`.

- [x] **L3.8 — Public catalog v1** *(completed 2026-05-13)*
  - `/recipes` route + `RecipeBrowser` Livewire component.
  - Lists published recipes with hero photo, title, kcal/serving, prep time.
  - Pagination.
  - Basic filters: category, cuisine.

- [x] **L3.9 — Catalog filters v2** *(completed 2026-05-13)*
  - Add filters: max kcal/serving, max prep time, diet tags, allergens (auto-applied from logged-in user's profile).
  - Sort: newest, lowest calories, shortest prep, most-favorited.
  - Filter sidebar uses `wire:model.live.debounce`.

- [x] **L3.10 — MeiliSearch wiring** *(completed 2026-05-13)*
  - Scout configured for `Recipe` and `Ingredient` models.
  - Indexed fields: title, summary, description, ingredient names (denormalized).
  - Reindex command works locally + on first deploy.
  - Search bar in catalog header → filters list to matching recipes.
  - Pest test: search returns expected recipe.

---

## Layer 4 — User-facing interactions

> Goal: a visitor can browse a recipe, save it, and use the calculator to scale ingredients to a target. This is the MVP money-shot.

- [x] **L4.1 — Recipe detail page** *(completed 2026-05-13)*
  - `/recipes/{slug}` route → `RecipeDetail` Livewire component.
  - Hero photo (full conversion), title, summary, meta badges (prep/cook/total time, servings, difficulty, cuisine, category).
  - Ingredient list grouped by `group_label`, showing amount + unit + name + note + optional flag.
  - Numbered step list with optional step photos (card conversion).
  - Tags displayed as emerald badges.
  - Nutrition panel: per-serving (kcal, P/F/C, fiber) + entire-recipe totals.
  - Gallery section for additional recipe photos.
  - Print button (placeholder for L5.2), author card.
  - Breadcrumb navigation back to catalog.
  - Step body escaped via `nl2br(e())` to prevent XSS (Textarea field, not RichEditor).
  - EN/UK translations for 15 new keys.
  - 18 Pest tests (309 total, 915 assertions): page load, title/summary, meta badges, ingredients, optional ingredients, notes, steps, nutrition panel, tags, category/cuisine, author, 404 for draft/archived/non-existent, breadcrumb, public access, group labels, print button.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L4.2 — Favorites** *(completed 2026-05-13)*
  - `favorites` table (composite PK on `user_id`, `recipe_id` with cascade deletes), `created_at` timestamp.
  - `User::favorites()` BelongsToMany and `Recipe::favoritedBy()` BelongsToMany relations.
  - `FavoriteButton` Livewire component on recipe detail page: toggle with login redirect for guests, dispatches `favorite-toggled` event.
  - `/cabinet/favorites` page (`FavoritesList` Livewire component): lists saved published recipes with search (via Scout), sort (newest/oldest/A-Z/lowest kcal), pagination, unfavorite with confirm dialog.
  - Dashboard favorites placeholder replaced with real navigation card linking to favorites page.
  - EN/UK translations for 10 new keys (cabinet + recipes domains).
  - 21 Pest tests (330 total, 960 assertions): favorite/unfavorite toggle, guest redirect, initial state, detail page integration, auth guard, list/filter/sort, unfavorite from list, unpublished exclusion, relation tests, cascade cleanup, dashboard link, duplicate prevention.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L4.3 — Calculator: scale by servings** *(completed 2026-05-13)*
  - `PortionCalculator` Livewire component embedded on recipe detail page, replacing the static nutrition panel.
  - Input: target servings (number input with +/- buttons, default = recipe's servings).
  - Output: scaled ingredient quantities (grouped, with optional flag) + scaled nutrition totals (per serving constant, totals scale linearly).
  - Updates live as input changes (`wire:model.live.debounce.300ms`).
  - Reset link appears when servings differ from original, bounds enforced (1-100).
  - Relations eager-loaded via `loadMissing` to prevent N+1 on Livewire rehydration.
  - EN/UK translations for 7 calculator keys.
  - 16 Pest tests (346 total, 998 assertions): default render, scaling, nutrition totals, per-serving constancy, increment/decrement/reset, bounds, grouping, optional ingredients, null handling, embed check, label change, fractional scaling.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L4.4 — Calculator: scale by total kcal** *(completed 2026-05-13)*
  - Mode selector tabs: Servings / Calories / % of Daily — tab switcher with active highlight.
  - Calorie mode: input target kcal with placeholder showing original total, scale factor = target / current_total, ingredients and nutrition totals scaled accordingly.
  - Per-serving values recalculated in kcal mode (total/servings) since total amount changes while servings count stays constant.
  - Daily % tab shows placeholder hint (implementation deferred to L4.5).
  - `resetCalculator()` restores servings and clears kcal target.
  - EN/UK translations for 10 new calculator keys (modes, kcal input, hints).
  - 13 new Pest tests (359 total, 1020 assertions): mode tabs, mode switching, kcal scaling, scale factor, nutrition totals, per-serving updates, edge cases (null/zero), invalid mode rejection, reset, daily_pct placeholder, original kcal hint, total label.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L4.5 — Calculator: % of daily intake** *(completed 2026-05-13)*
  - Daily-percent mode: input 5–100%, pulls user's `daily_kcal_target` from profile via `#[Computed]` property.
  - If user not logged in or no target set: inline prompt with link to `/cabinet/health` to set daily target.
  - Scale factor = `(daily_target * pct / 100) / recipe_total_kcal`, same per-serving recalculation as kcal mode.
  - Guards aligned: `isScaled` and `dailyPctScaleFactor` both enforce 5–100 range.
  - Computed property memoization via `$this->dailyKcalTarget` property access (not method call).
  - EN/UK translations for 6 new keys (daily_pct input, hints, target info, labels).
  - 9 new Pest tests (368 total, 1033 assertions): input display with/without target, scaling math, ingredient scaling, edge cases (below minimum, no target, guest user), label, per-serving recalculation.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L4.6 — Calculator history** *(completed 2026-05-13)*
  - `calculator_sessions` migration: `id`, `user_id` (FK cascade), `recipe_id` (FK cascade), `mode` varchar(20), `input_value` decimal(10,2), `scale_factor` decimal(10,6), `totals` JSON, `created_at` timestamp.
  - `CalculatorSession` model with casts (input_value decimal:2, scale_factor decimal:6, totals array, created_at datetime), `user()` and `recipe()` relations.
  - `saveCalculation()` on `PortionCalculator`: validates auth + isScaled + valid mode, stores session, sets `$saved` flag. Flag resets on any input/mode change via `updated()` lifecycle hook.
  - Save button in calculator blade: auth-gated, shown only when scaled, `wire:loading.attr="disabled"` to prevent duplicate saves, shows confirmation text after save.
  - `CalculationHistory` Livewire component at `/cabinet/calculations`: paginated list of sessions (newest first, with recipe eager-loaded), delete scoped to owner. Handles soft-deleted recipes gracefully (no broken links).
  - Dashboard card updated from placeholder to active link.
  - EN/UK translations: 2 calculator keys (`save_calculation`, `saved`), 7 cabinet keys (`calculations_desc`, `no_calculations`, `deleted_recipe`, `input_value`, `view_recipe`, `delete_calculation_confirm`, `delete`).
  - 18 Pest tests (386 total, 1067 assertions): save button visibility (auth/guest/not-scaled), save creates record (servings/kcal modes), totals JSON, guest/not-scaled guards, history page auth/load/empty-state, delete own/other-user, dashboard link, save-then-list integration, cascade deletes (recipe + user).
  - Quality gates green: Pint, Larastan level 6, Pest.

- [x] **L4.7 — Charts** *(completed 2026-05-13)*
  - `resources/js/charts.js` — Alpine.js component wrapping ApexCharts with `init()`/`destroy()` lifecycle for proper cleanup.
  - Donut chart: macro split showing kcal contribution from protein (×4), fat (×9), carbs (×4). Center label shows total kcal. Colors: emerald/amber/indigo.
  - Bar chart: actual macros (g) vs user's daily target (computed from `daily_kcal_target × p_pct/f_pct/c_pct`). Only rendered for authenticated users with daily target set.
  - `macroTargets()` computed property on PortionCalculator: derives target grams from user's profile percentage split.
  - Charts react to calculator updates via `wire:key` (md5 of chart data). `wire:ignore` on chart containers prevents Livewire morph from breaking ApexCharts DOM.
  - Registered in `app.js` as `Alpine.data('nutritionCharts', ...)`.
  - EN/UK translations: 4 keys (`chart_macro_split`, `chart_vs_target`, `actual`, `target`).
  - 13 Pest tests (399 total, 1092 assertions): chart rendering conditions (nutrition/no-nutrition/zero-kcal), Alpine component presence, bar chart visibility (guest/no-target/with-target), macroTargets computed (null/values/correct-math), scaled nutrition reactivity across modes.
  - Quality gates green: Pint, Larastan level 6, Pest.

- [ ] **L4.8 — Ingredient autocomplete**
  - `IngredientAutocomplete` Livewire component (debounced search via Scout).
  - Used in catalog filter sidebar (include / exclude ingredient).
  - Used as a fallback in admin recipe form if Filament's default picker is sluggish.

---

## Layer 5 — Polish & production readiness

> Goal: site is production-ready. Localized, monitored, backed up, deployed.

- [ ] **L5.1 — Email flows localized**
  - Verify-email, reset-password, welcome emails extend `Mail::to(...)->locale(...)` using locale captured at dispatch.
  - Blade templates use `__()`.
  - Pest test: dispatching with locale `uk` produces a Ukrainian email body.

- [ ] **L5.2 — Print + PDF**
  - Print stylesheet: hides nav/footer, expands all sections, B&W friendly.
  - PDF export via `barryvdh/laravel-dompdf` for a recipe page.
  - "Print / PDF" buttons on recipe detail.

- [ ] **L5.3 — SEO**
  - Meta tags (title, description, og:image, og:type), Twitter card.
  - `<link rel="alternate" hreflang="en|uk|x-default">` on public pages.
  - `sitemap.xml` generator (recipes + categories), `robots.txt`.
  - Lighthouse SEO score ≥ 95 on recipe detail page.

- [ ] **L5.4 — Audit log**
  - `owen-it/laravel-auditing` enabled on `Recipe`, `Ingredient`, `User`, taxonomies.
  - Filament page lists audits with filtering by user / model / action.
  - 90-day retention via scheduled prune job.

- [ ] **L5.5 — Rate limiting**
  - Auth routes: 5 req/min per IP.
  - Calculator endpoint: 60 req/min per user.
  - API: 60/min per token, 30/min per IP for unauth.
  - 429 responses use the localized error page.

- [ ] **L5.6 — Backups**
  - `spatie/laravel-backup` configured to push DB + storage to off-server S3-compatible bucket nightly.
  - 14-day retention.
  - Manual `backup:run` confirmed working.
  - Test restore on a throwaway local DB.

- [ ] **L5.7 — Sentry**
  - PHP SDK + JS SDK initialized for production env only.
  - Test exception captured.
  - Source maps uploaded on deploy.

- [ ] **L5.8 — Forge provisioning**
  - VPS provisioned (Hetzner CPX21 or equivalent).
  - Forge sites created: staging + production, separate databases.
  - MeiliSearch installed as Forge daemon, bound to `127.0.0.1`.
  - Horizon daemon configured.
  - SSL via Forge's Let's Encrypt.
  - UFW firewall: 22/80/443 only.
  - Server timezone `UTC`.

- [ ] **L5.9 — First staging deploy + smoke test**
  - Push to `develop` triggers staging deploy.
  - Run through every flow: register, verify email (real inbox), edit profile, search, view recipe, calculate, save favorite.
  - Check Sentry catches a deliberately-thrown error.
  - Verify backups land in the bucket.

- [ ] **L5.10 — Pre-launch checklist**
  - Lighthouse Performance + Accessibility ≥ 90 on landing, catalog, recipe detail.
  - Manual keyboard-only walkthrough.
  - Validate every email template renders correctly in Gmail + Outlook web.
  - Spot-check 20 random recipes for correct nutrition totals.
  - Final content review (50+ recipes seeded, photos present).
  - Cut a `v1.0.0` git tag.
  - Promote to production.

---

## Layer 6 — Stretch (post-MVP, only if time before launch)

- [ ] **L6.1 — Public API v1** — recipes list/detail + ingredient search; Sanctum tokens for `/me/*` (favorites, calculator history).
- [ ] **L6.2 — CSV ingredient import in Filament** — column-mapping wizard, dry-run preview.
- [ ] **L6.3 — OpenAPI / Scribe** — generated API docs.
- [ ] **L6.4 — Dusk smoke tests** — registration → first calculator save end-to-end.
- [ ] **L6.5 — Performance pass** — Telescope-driven N+1 sweep, eager loading audit, image lazyloading verification.

---

## Critical-path summary

Tasks that block the most downstream work — never skip ahead of these:

```
L0.1 → L0.3 → L1.2 → L1.3 → L1.5 → L2.1 → L2.5 → L2.7 → L3.1 → L3.2 → L3.4 → L4.1 → L4.3
```

Get to L4.3 (calculator scale-by-servings) and the product is technically demoable end-to-end, even if rough.

---

## Estimated timeline (solo, 1.5 tasks/day)

| Layer | Tasks | Days | Calendar weeks |
|---|---|---|---|
| L0 — Bootstrap | 3 | 2 | 0.5 |
| L1 — Foundation | 6 | 4 | 1 |
| L2 — Reference data | 8 | 6 | 1.5 |
| L3 — Core features | 10 | 7 | 1.5 |
| L4 — Interactions | 8 | 6 | 1.5 |
| L5 — Polish | 10 | 7 | 1.5 |
| **MVP total** | **45** | **~32** | **~6.5 weeks** |
| L6 — Stretch | 5 | 4 | 1 |

Add 20 % buffer for unknowns → realistic MVP target **~8 weeks**.

---

## Working rhythm (recommended)

- **Start of day:** check the next unticked task, branch, write a 1-line acceptance criterion at the top of the PR description, then code.
- **End of task:** push, CI green, merge, tick the box in this file, commit the tick.
- **End of day:** if the task isn't done, leave clear notes in the PR for tomorrow-you.
- **End of layer:** boot the app, click through the new flow, spot-fix anything ugly with a quick follow-up task. Then move on.

---

## Things to deliberately NOT do during MVP

A reminder list because scope creep kills solo projects:

- No comments / ratings.
- No social login.
- No PDF recipe import.
- No meal planner.
- No mobile app.
- No final branding / logo work.
- No multilingual content (UI strings only, content is English).
- No imperial unit conversion in recipes (the `units_pref` column exists but only swaps display labels in cabinet).
- No public API beyond what L6.1 says.

If an idea like these appears mid-build, write it in `IDEAS.md` and keep moving.

---

*End of plan.*

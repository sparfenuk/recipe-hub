# Recipe Hub — Technical Specification

**Version:** 1.0
**Date:** 2026-05-06
**Stack:** Laravel 12.x (PHP 8.5), MySQL 8, Redis, Vite + Tailwind CSS 3, Livewire 3 + Alpine.js, Filament 3

---

## 1. Project Overview

A web application that lets registered users browse a curated bank of recipes, save favorites, and use a portion-size calculator that scales recipe ingredients to match a target daily calorie intake. Each ingredient stores macronutrient data (proteins, fats, carbohydrates, fiber, calories per 100 g/ml); the system computes per-portion and per-recipe totals automatically. An admin panel manages all reference data.

### 1.1 Goals
- Centralized, structured recipe and ingredient database with full nutritional breakdown.
- Personal user cabinet (profile, favorites, calculator history, dietary preferences).
- Calorie-driven portion calculator scaling ingredients proportionally.
- Role-based admin panel for content moderation.

### 1.2 Non-Goals (v1)
- Mobile native apps (responsive web only).
- E-commerce / grocery ordering integrations.
- Social features (comments, follows, sharing feeds).
- AI recipe generation.

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12.x, PHP 8.5 |
| DB | MySQL 8.0 (primary) / PostgreSQL 16 (alt) |
| Cache / Queues | Redis 7 + Laravel Horizon |
| Search | Laravel Scout + MeiliSearch |
| Frontend | **Blade + Livewire 3 + Alpine.js 3 + Tailwind CSS 4** |
| UI Components | Flux UI (Livewire's official component library), Heroicons |
| Charts | ApexCharts (nutrition donut, macro bars) |
| Auth | Laravel Fortify + Sanctum (SPA/API tokens) |
| Admin Panel | Filament 3 |
| Permissions | spatie/laravel-permission |
| Media | spatie/laravel-medialibrary + intervention/image |
| Storage | Local (dev) / S3-compatible (prod) |
| Queues / Jobs | Redis-backed, Horizon dashboard |
| Email | Mailgun / Postmark / SES |
| Testing | Pest 3 + Laravel Dusk |
| CI/CD | GitHub Actions |
| Local Dev | Laravel Sail (Docker Compose) — **canonical setup** |
| Containerization | Docker + Laravel Sail (dev) |

---

## 3. User Roles

| Role | Capabilities |
|---|---|
| **Guest** | Browse public recipes, view recipe details, register, search. |
| **User** | Everything Guest + favorite recipes, use calculator, save calculator history, manage profile, set dietary preferences/allergens, daily calorie target. |
| **Editor** | User + create/edit recipes & ingredients, upload media, submit for review. |
| **Admin** | Full access — manage users, roles, ingredients, recipes, categories, tags, media, audit logs, system settings. |

Permissions are managed via `spatie/laravel-permission` (roles + granular permissions like `recipe.create`, `ingredient.delete`, `user.impersonate`).

---

## 4. Functional Requirements

### 4.1 Authentication & Account
- Email/password registration with email verification.
- Password reset flow.
- Optional 2FA (TOTP) via Fortify.
- Social login (Google, Facebook) via Laravel Socialite — **deferred to v1.1**.
- Session management, remember-me, logout from all devices.

### 4.2 User Cabinet (`/cabinet`)
- **Profile:** name, avatar, bio, locale, timezone.
- **Health profile:** sex, age, height, weight, activity level → suggested daily calorie target (Mifflin-St Jeor formula). User can override.
- **Macro targets:** %P/F/C split (default 30/30/40, configurable).
- **Allergens / Dislikes:** multi-select of ingredients/tags to exclude.
- **Diet tags:** vegetarian, vegan, keto, gluten-free, halal, etc.
- **Favorites:** list of saved recipes with quick filters.
- **Calculator history:** saved scaled-portion calculations.
- **Settings:** units (metric/imperial), language, password, 2FA, delete account.

### 4.3 Recipe Bank (`/recipes`)
- **Listing page** with filters: category, cuisine, diet tags, max prep time, max calories per serving, contains/excludes ingredient, allergens (auto-applied from profile), rating.
- **Sorting:** newest, most-popular (favorites count), shortest prep, lowest calories.
- **Search:** full-text on title/description/ingredients (Scout/MeiliSearch).
- **Recipe detail page:**
  - Title, hero photo, gallery (thumbnails), short description.
  - Prep time, cook time, total time, difficulty, servings, cuisine, category, tags.
  - Ingredient list with quantity, unit, optional notes, optional substitutions.
  - Step-by-step instructions (ordered, each step optionally with photo).
  - **Nutrition panel:** per serving and per 100 g — calories, P/F/C/fiber, plus optional sugar, sodium.
  - **Author** (Editor/Admin attribution).
  - Favorite button, print view, share link.
  - **Portion calculator widget** (see 4.5).
- Comments / ratings — *out of scope v1; structure DB to allow later.*

### 4.4 Ingredients
- Master ingredient catalog with:
  - Name (localized), aliases, category (vegetable, dairy, meat, etc.).
  - **Nutrition per 100 g/ml:** kcal, protein (g), fat (g), saturated fat (g, optional), carbs (g), sugar (g, optional), fiber (g), sodium (mg, optional).
  - Default unit (g, ml, piece), density (g/ml) for volume↔mass conversion.
  - Photo (optional), allergen flags (gluten, lactose, nuts, soy, eggs, fish, shellfish).
  - Tags (vegan, vegetarian, halal, etc.).
  - Source/citation field for nutrition data.
- Public read endpoints for autocomplete; write restricted to Editor/Admin.
- Ingredient page (admin/editor) shows where it is used (recipe count, links).

### 4.5 Portion Size Calculator
**Purpose:** given a recipe and the user's daily calorie target (or a manually entered target), compute how much to eat / how to scale ingredients.

**Modes:**
1. **Scale by servings** — input target servings → all ingredients scaled linearly.
2. **Scale by total calories** — input desired kcal → recipe scaled so total kcal = target.
3. **Daily plan mode** — pick % of daily intake (e.g., 30%) → calculator pulls user's daily target and scales accordingly.

**Outputs:**
- Per-ingredient adjusted quantity (rounded to sensible precision).
- Updated totals: kcal, P/F/C/fiber for the whole scaled meal and per resulting serving.
- "Save calculation" → user's history.
- "Export" → printable/PDF view.

**Calculation rules:**
- All ingredient nutrition stored per 100 g (or 100 ml with density).
- Recipe ingredient row stores `amount` + `unit_id` + optional `grams_override`.
- Conversion service resolves any unit → grams using ingredient's density and unit table.
- Aggregates computed and cached on recipe save (`recipes.nutrition_cached_at`).

### 4.6 Photos & Media
- Recipe hero photo + gallery (multiple).
- Step photos.
- Ingredient photo.
- User avatar.
- Stored via `spatie/laravel-medialibrary`; conversions: `thumb` (200x200), `card` (600x400), `full` (1600w).
- Image processing on a queue (`image-processing` queue).

### 4.7 Admin Panel (`/admin`, Filament 3)
- **Dashboard:** widgets — total users, recipes, ingredients, recent registrations, popular recipes, queue health.
- **Resources:**
  - Users (CRUD, role assignment, ban/unban, impersonate).
  - Recipes (CRUD, publish/unpublish, feature toggle, bulk actions, duplicate).
  - Ingredients (CRUD, bulk import via CSV, nutrition validation).
  - Categories, Cuisines, Tags, Allergens, Units (taxonomy management).
  - Media library browser.
  - Audit log (who changed what — `owen-it/laravel-auditing`).
  - Settings (site name, default macro split, feature flags).
- **CSV/JSON import** for ingredients (with column mapping UI).
- **PDF recipe import** (optional v1.1) — upload PDF, extract recipe via parser, review/edit before save.

### 4.8 Ingredient Data Pipeline (USDA seed)

The initial ingredient catalog is seeded from **USDA FoodData Central** (public domain), specifically the *Foundation Foods* and *SR Legacy* sub-datasets, then curated and enriched.

#### 4.8.1 Source Files
- Bulk download from `https://fdc.nal.usda.gov/download-datasets`.
- Files used:
  - `foundation_foods.csv` — ~250 lab-analyzed entries.
  - `sr_legacy_foods.csv` — ~7,800 Standard Reference entries.
  - `food_nutrient.csv` — nutrient values keyed by `fdc_id`.
  - `nutrient.csv` — nutrient definitions (id ↔ name, unit).
- Files **ignored**: Branded Foods, FNDDS (Survey), Experimental.

#### 4.8.2 Curation Rules (one-time pre-filter script)
Output: ~500–800 cooking-relevant rows. Filter logic:
- **Drop** food categories: baby foods, infant formula, fast food restaurant items, sweets/candy aggregates, alcoholic beverages (drop or keep flagged), supplements.
- **Drop** entries whose `description` contains: `prepared`, `restaurant`, `entree`, `commercially prepared`, `frozen meal`, `MRE`.
- **Keep**: vegetables, fruits, grains, legumes, nuts, seeds, dairy, eggs, meat (raw cuts), poultry (raw), fish/seafood (raw), oils, herbs, spices, condiments, common pantry staples (flour, sugar, salt, etc.).
- **Deduplicate** near-identical variants (e.g. "Beef, ground, 80%" / "Beef, ground, 85%" / ...) — keep 1–2 representative per cut, mark others inactive.
- **Normalize naming**: strip USDA's all-caps and inverted commas (`"Beef, ground, raw"` → `"Ground beef, raw"`).

#### 4.8.3 Field Mapping

| USDA field | App `ingredients` column | Notes |
|---|---|---|
| `description` | `name` | After normalization |
| `fdc_id` | `source` | Stored as `"USDA FDC #173687"` for traceability + idempotency |
| nutrient `1008` (Energy, kcal) | `kcal_per_100g` | |
| nutrient `1003` (Protein, g) | `protein_g` | |
| nutrient `1004` (Total fat, g) | `fat_g` | |
| nutrient `1258` (Saturated fat, g) | `saturated_fat_g` | Optional, may be null |
| nutrient `1005` (Carbs by diff, g) | `carbs_g` | |
| nutrient `2000` (Sugars total, g) | `sugar_g` | Optional |
| nutrient `1079` (Fiber, total, g) | `fiber_g` | |
| nutrient `1093` (Sodium, mg) | `sodium_mg` | |
| food category | `category_id` | Mapped via lookup table to app's `ingredient_categories` |

Values are stored per **100 g**. Liquid ingredients additionally need a density; see 4.8.4.

#### 4.8.4 Manual Enrichment (post-import, in admin)
USDA does not provide these fields — admin/editor fills them once per ingredient:
- `density_g_per_ml` — required for liquids and any ingredient that may be measured by volume. Reference table maintained in `database/seeders/data/densities.json` for common items (oils, dairy, syrups, flour, sugar) so the importer can pre-fill where possible.
- `default_unit_id` — `g` (solids), `ml` (liquids), `piece` (eggs, lemons, garlic cloves).
- **Allergen flags** — gluten, lactose, nuts (tree/peanut), soy, eggs, fish, shellfish, sesame, mustard. Pre-seeded from a name-keyword rule list, then confirmed by editor.
- **Diet tags** — vegan, vegetarian, halal-friendly, kosher-friendly. Same approach: keyword pre-seed → editor confirms.
- **Aliases** (`ingredient_aliases.alias`) — common synonyms (e.g., "garbanzo beans" ↔ "chickpeas", "cilantro" ↔ "coriander leaves"). Maintained as a static JSON list in `database/seeders/data/aliases.json` covering ~200 known pairs.

#### 4.8.5 Import Command

```bash
php artisan ingredients:import-usda \
    --foundation=storage/app/usda/foundation_foods.csv \
    --legacy=storage/app/usda/sr_legacy_foods.csv \
    --nutrients=storage/app/usda/food_nutrient.csv \
    --enrich=database/seeders/data/densities.json,allergen-rules.json,aliases.json \
    --dry-run
```

Behavior:
- **Idempotent**: keyed on `source` (`"USDA FDC #<fdc_id>"`). Re-running updates existing rows rather than duplicating.
- `--dry-run` prints summary (added / updated / skipped) without writing.
- Streams CSVs (no full load into memory) — works on a 4 GB VPS.
- Logs row-level errors to `storage/logs/usda-import-{date}.log` with the offending `fdc_id` for review.
- Wrapped in a DB transaction per chunk (1,000 rows) so partial failures don't leave inconsistent state.
- Sets `is_active = true` for kept items, `false` for filtered-out variants we still want to retain for traceability (configurable).

#### 4.8.6 Database Seeder

`IngredientSeeder` calls the importer on a checked-in **slim CSV snapshot** (`database/seeders/data/usda-curated.csv`, ~600 rows post-curation). This gives every developer the same baseline locally and in CI without redownloading USDA bulk files.

The full USDA bulk dump stays out of the repo; a separate `make import-usda-full` target downloads it for one-off re-curation runs.

#### 4.8.7 License & Attribution
- USDA FoodData Central is in the **public domain** (US government work). No license, no attribution required.
- Spec choice: include a single line in the public Ingredients page footer — *"Nutrition data sourced from USDA FoodData Central."* — as a courtesy, not a legal obligation.

---

## 5. Database Schema (high-level)

> **Conventions**
> - All `timestamp` and `datetime` columns store UTC. Application config: `'timezone' => 'UTC'`.
> - All textual content (recipe titles, ingredient names, taxonomy labels) is **English only** in v1 — no per-locale JSON columns.
> - Locale is resolved from the `locale` cookie at request time and is not persisted on `users`.

```
users
  id, name, email, email_verified_at, password, avatar_path,
  created_at, updated_at, deleted_at
  -- locale is NOT stored on user; resolved from cookie per request
  -- timezones are UTC-only at launch; no per-user timezone in v1

user_profiles
  user_id (PK,FK), sex, birth_date, height_cm, weight_kg,
  activity_level (enum), daily_kcal_target, p_pct, f_pct, c_pct,
  units_pref (metric|imperial), timezone (reserved, unused in v1),
  updated_at

roles, permissions, model_has_roles, model_has_permissions, role_has_permissions
  -- spatie/laravel-permission

ingredients
  id, slug, name, category_id,
  default_unit_id, density_g_per_ml, kcal_per_100g, protein_g,
  fat_g, saturated_fat_g, carbs_g, sugar_g, fiber_g, sodium_mg,
  source, is_active, created_by, updated_at
  -- name is plain English text; no translation columns

ingredient_categories
  id, slug, name, parent_id

units
  id, code (g, ml, tsp, tbsp, cup, piece, ...),
  name, type (mass|volume|count), to_base_factor

ingredient_aliases
  id, ingredient_id, alias

allergens
  id, slug, name

ingredient_allergen        -- pivot

tags
  id, slug, name, type (diet|cuisine|misc)

ingredient_tag             -- pivot

cuisines
  id, slug, name

categories                 -- recipe categories
  id, slug, name, parent_id

recipes
  id, slug, title, summary, description, instructions(JSON ordered steps),
  servings, prep_time_min, cook_time_min, total_time_min,
  difficulty (enum), category_id, cuisine_id, author_id,
  status (draft|review|published|archived), is_featured,
  total_kcal, total_protein_g, total_fat_g, total_carbs_g,
  total_fiber_g, kcal_per_serving, protein_per_serving_g, ...,
  nutrition_cached_at, published_at, created_at, updated_at, deleted_at

recipe_ingredients
  id, recipe_id, ingredient_id, position, amount, unit_id,
  grams_override, note, is_optional, group_label

recipe_tag                 -- pivot

recipe_steps               -- alternative to JSON instructions
  id, recipe_id, position, body, image_path

favorites
  user_id, recipe_id, created_at  (composite PK)

calculator_sessions
  id, user_id, recipe_id, mode (servings|kcal|daily_pct),
  input_value, scale_factor, totals(JSON), saved, created_at

media                      -- spatie/laravel-medialibrary
audits                     -- owen-it/laravel-auditing
jobs, failed_jobs, cache, sessions
```

### 5.1 Indexing
- `recipes (status, published_at)`, `recipes (category_id)`, `recipes (cuisine_id)`.
- Full-text on `recipes.title`, `recipes.summary`, `ingredients.name` (or Scout index).
- `recipe_ingredients (recipe_id, ingredient_id)`.

---

## 6. API Surface (REST)

Base: `/api/v1` — Sanctum-protected where noted.

### Public
- `GET /recipes` — list, filters, pagination.
- `GET /recipes/{slug}` — detail.
- `GET /ingredients` — search/autocomplete.
- `GET /ingredients/{slug}` — detail.
- `GET /taxonomies/{tags|cuisines|categories|allergens}`.

### Auth
- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `POST /auth/email/verify/{id}/{hash}`

### Authenticated (User)
- `GET /me` / `PUT /me` / `PUT /me/profile`
- `GET /me/favorites` / `POST /me/favorites/{recipe}` / `DELETE /me/favorites/{recipe}`
- `POST /recipes/{recipe}/calculate` — body: `{ mode, value }` → returns scaled ingredients + totals.
- `GET /me/calculations` / `POST /me/calculations` / `DELETE /me/calculations/{id}`

### Editor/Admin (scoped by permission)
- `POST/PUT/DELETE /recipes`
- `POST/PUT/DELETE /ingredients`
- `POST /ingredients/import` (CSV)

All responses use API resources (`App\Http\Resources\*`). Error format: RFC 7807 Problem Details.

---

## 7. Frontend Pages

| Route | Purpose | Auth |
|---|---|---|
| `/` | Landing — featured recipes, CTA | Public |
| `/recipes` | Catalog with filters | Public |
| `/recipes/{slug}` | Recipe detail + calculator | Public |
| `/ingredients` | Browse ingredients | Public |
| `/login`, `/register`, `/password/*` | Auth flows | Public |
| `/cabinet` | Dashboard | User |
| `/cabinet/profile` | Profile editor | User |
| `/cabinet/health` | Health & macro targets | User |
| `/cabinet/favorites` | Saved recipes | User |
| `/cabinet/calculations` | Saved calculator sessions | User |
| `/cabinet/settings` | Account settings | User |
| `/admin/*` | Filament admin | Admin/Editor |

---

## 8. Non-Functional Requirements

- **Performance:** TTFB < 300 ms p95 on cached pages; recipe list query < 100 ms with proper indexes.
- **Scalability:** stateless app servers behind LB; Redis-backed sessions/cache; horizontal scaling friendly.
- **Caching:** Cache recipe nutrition aggregates; HTTP caching headers on public endpoints; tagged cache invalidation on recipe/ingredient save.
- **Security:**
  - HTTPS only, HSTS.
  - CSRF on all web forms; Sanctum tokens for API.
  - Rate limiting on auth endpoints (5/min) and calculator (60/min).
  - Input validation via Form Requests; output escaping via Blade.
  - SQL injection — Eloquent / parameterized queries only.
  - XSS — Tailwind + Blade auto-escape; sanitize WYSIWYG (HTMLPurifier) for instructions if rich text used.
  - File uploads: MIME + extension allowlist (jpg/png/webp), size limit 5 MB, image dimension cap, virus scan (ClamAV) optional.
  - Password hashing: bcrypt cost 12.
  - GDPR: account deletion, data export endpoint.
- **Accessibility:** WCAG 2.1 AA — semantic HTML, keyboard navigation, ARIA labels on calculator.
- **i18n:** Laravel localization with JSON translation files. **Default: English (`en`). Secondary: Ukrainian (`uk`).** Both languages shipped at launch for **static UI strings only** (labels, validation messages, emails, error pages). Recipe and ingredient content is authored in English; admins do not enter translations. Locale switcher in header persists to a `locale` cookie (1-year expiry). Browser `Accept-Language` honored on first visit. No `users.locale` DB column.
- **Time zones:** Application `config('app.timezone') = 'UTC'`. All `timestamp`/`datetime` columns store UTC. All UI displays UTC for v1. MySQL container runs with `TZ=UTC`. Per-user timezone preference is a future enhancement (column reserved on `user_profiles` but unused at launch).
- **Logging/Monitoring:** Laravel Telescope (non-prod), Sentry (prod), structured logs to stdout.
- **Backups:** daily DB + media snapshots, 14-day retention.

---

## 9. Background Jobs

| Job | Trigger | Queue |
|---|---|---|
| `RecalculateRecipeNutrition` | recipe or ingredient save | `default` |
| `GenerateMediaConversions` | media upload | `media` |
| `SendVerificationEmail` | registration | `mail` |
| `ImportIngredientsCsv` | admin upload | `imports` |
| `ParsePdfRecipe` (v1.1) | admin upload | `imports` |
| `PruneOldCalculatorSessions` | nightly cron | `default` |

---

## 10. Project Structure (Laravel)

```
app/
  Models/{User,Recipe,Ingredient,Unit,Tag,...}
  Http/
    Controllers/{Web,Api}/...
    Requests/...
    Resources/...
    Middleware/...
  Services/
    Nutrition/NutritionCalculator.php
    Nutrition/UnitConverter.php
    Recipes/RecipeImporter.php
  Filament/
    Resources/{UserResource,RecipeResource,IngredientResource,...}
    Pages/Dashboard.php
  Policies/...
  Jobs/...
  Events/ Listeners/
  Enums/{RecipeStatus, ActivityLevel, Difficulty, UnitType}
database/
  migrations/
  factories/
  seeders/
resources/
  views/
  js/
  css/
routes/
  web.php  api.php  channels.php
tests/
  Feature/  Unit/
```

---

## 11. Frontend Architecture

**Stack:** Blade + Livewire 3 + Alpine.js 3 + Tailwind CSS 3, bundled via Vite.

### 11.1 Rationale
- One language (PHP) and one templating system (Blade) for all server-rendered logic.
- Filament admin panel is itself built on Livewire — same mental model across public site and admin.
- SEO-friendly server-rendered HTML out of the box (critical for a recipe site relying on Google traffic).
- API layer (Sanctum) is built only for future mobile/3rd-party use, not coupled to the web UI.

### 11.2 Component Boundaries

| Concern | Tool |
|---|---|
| Reactive components with server logic (filters, calculator, favorites) | **Livewire 3** |
| Pure client-side micro-interactions (modals, tabs, dropdowns, copy-to-clipboard, gallery, mobile menu) | **Alpine.js** |
| Layout, design system, responsive behavior | **Tailwind CSS** + `@tailwindcss/forms`, `@tailwindcss/typography` |
| Reusable UI primitives (buttons, inputs, modals, dropdowns, tables) | **Flux UI** (free) + custom Blade components |
| Icons | **Heroicons** Blade components |
| Charts (nutrition donut, macro split bars) | **ApexCharts** wrapped in an Alpine component |
| Toasts / notifications | Livewire dispatched events → Alpine listener |
| File uploads | Livewire native file uploads → `spatie/laravel-medialibrary` |
| Images / lazy loading | `loading="lazy"` + responsive `srcset` from medialibrary conversions |

### 11.3 Key Livewire Components (planned)

- `RecipeBrowser` — paginated recipe grid with filters (category, calories, diet tags, ingredient include/exclude), driven by `wire:model.live.debounce`.
- `RecipeFilters` — sidebar of facets, dispatches events to `RecipeBrowser`.
- `PortionCalculator` — three modes (servings / kcal / daily %), live-recomputes via `NutritionCalculator` service, optional save action.
- `FavoriteButton` — optimistic toggle, persists to `favorites` table.
- `IngredientAutocomplete` — debounced search, used in admin and on filter sidebar.
- `NutritionPanel` — receives totals from calculator, renders ApexCharts macro split.
- `CabinetDashboard`, `ProfileForm`, `HealthForm`, `MacroTargetForm`, `FavoritesList`, `CalculationHistory`.
- `Auth\Login`, `Auth\Register`, `Auth\ForgotPassword`, `Auth\ResetPassword`, `Auth\Verify`.

### 11.4 Asset Pipeline

```
resources/
  css/
    app.css            # Tailwind entry
  js/
    app.js             # Alpine + Livewire bootstrap, plugins
    charts.js          # ApexCharts wrapper
  views/
    layouts/
      app.blade.php    # public + cabinet
      guest.blade.php  # auth pages
    components/        # anonymous Blade components (button, card, input, ...)
    livewire/          # Livewire component templates
    pages/             # full-page Blade views
```

Vite config compiles `app.css` + `app.js`, fingerprints output, serves HMR in dev via `npm run dev`.

### 11.5 Accessibility & UX Conventions
- All form inputs paired with `<label>`, error messages via `aria-describedby`.
- Modals trap focus (Alpine `x-trap`), restore on close.
- Calculator updates announced with `aria-live="polite"`.
- Color contrast meets WCAG AA; Tailwind palette tuned accordingly.
- Keyboard-only navigation tested for catalog, calculator, cabinet.

### 11.6 Localization (EN default, UK secondary — UI strings only)

**Scope**
- **Localized:** static UI strings (Blade/Livewire labels, buttons, navigation, validation messages, password reset / verification emails, error pages, flash messages).
- **Not localized:** recipe titles, descriptions, instructions, ingredient names, categories, tags. All content is authored and stored in English. Admin panel UI and admin content forms are English-only.

**Static UI strings**
- JSON translation files: `lang/en.json`, `lang/uk.json` for flat strings.
- Grouped PHP files: `lang/{en,uk}/validation.php`, `passwords.php`, `pagination.php`, plus app-domain files `recipes.php`, `calculator.php`, `cabinet.php`, `auth.php`.
- Referenced via `{{ __('calculator.mode.servings') }}` in Blade/Livewire.

**Locale resolution order (per request)**
1. `?locale=en|uk` query param → writes the `locale` cookie, redirects to clean URL.
2. `locale` cookie.
3. `Accept-Language` header (best match among supported locales).
4. Fallback: `en`.

Implemented in a `SetLocale` middleware on the `web` group. No DB lookup; works identically for guests and authenticated users.

**Locale switcher**
- Header dropdown: `English` / `Українська`.
- Click sets cookie `locale` (1-year expiry, `SameSite=Lax`) and re-renders.

**Filament admin**
- Filament UI locale fixed to `en` regardless of user cookie (`Filament::setServingPanel()` hook or middleware that overrides locale for `/admin/*` routes).
- All resource forms have single English fields. No translation tabs, no `spatie/laravel-translatable`.

**Search**
- MeiliSearch indexes the English content fields. UI labels in the search page are localized; the searchable corpus itself is English. A UK user types into the same English index — works fine for recipe/ingredient names.

**Numbers**
- Formatted via `Number::format()` honoring `app()->getLocale()`.
- EN: `1,250.50` · UK: `1 250,50`.
- Decimals in calculator output rounded sensibly (e.g., 1 decimal place for grams, 0 for kcal).

**Dates / time zones**
- App `config('app.timezone') = 'UTC'`.
- All `timestamp` / `datetime` columns store UTC.
- Display formatting via Carbon localized helpers: `$date->locale(app()->getLocale())->isoFormat('LL')`.
- All times shown as UTC in v1 (no per-user timezone). User profiles reserve a `timezone` column but it is not exposed in UI yet.

**Measurement units**
- Metric is the system default (g, ml, °C). User profile preference (`units_pref` = `metric` | `imperial`) controls display, independent of language.

**Emails & notifications**
- Mailables read locale from session/cookie at the moment of sending; queued mails capture the locale at dispatch time via `Mail::to(...)->locale($locale)->send(...)`.
- One Blade template per mailable using `__()`; renders in EN or UK.

**Slugs & URLs**
- Slugs are English (`/recipes/cabbage-rolls`); the same URL works for both locales.
- `<link rel="alternate" hreflang="en">` and `hreflang="uk"` and `x-default` emitted on public pages — both pointing at the same URL but signaling availability of UK UI.

**Translation workflow**
- Source-of-truth: EN strings in code, UK strings maintained in `lang/uk/`.
- CI guard (Pest test): every key present in `lang/en.json` and `lang/en/*.php` must exist in the matching `uk` file. Build fails on drift.

---

## 12. Composer & NPM Packages

### 12.1 Composer

- `laravel/framework: ^12.0`
- `livewire/livewire: ^3.5`
- `livewire/flux` (free UI components) + optionally `livewire/flux-pro` if license acquired
- `laravel/fortify`, `laravel/sanctum` *(socialite added in v1.1 when social login lands)*
- `filament/filament: ^3.2`
- `spatie/laravel-permission`
- `spatie/laravel-medialibrary`
- `intervention/image`
- `laravel/scout` + `meilisearch/meilisearch-php`
- `owen-it/laravel-auditing`
- `barryvdh/laravel-dompdf` (printable recipes)
- `maatwebsite/excel` (CSV import) — **deferred: incompatible with PHP 8.5 as of 2026-05**
- `pestphp/pest`, `laravel/dusk`, `laravel/telescope`, `laravel/pint`, `larastan/larastan` (dev)

### 12.2 NPM

- `tailwindcss@4`, `@tailwindcss/vite` (forms/typography plugins are built into v4)
- `alpinejs`, `@alpinejs/focus`, `@alpinejs/persist`
- `apexcharts`
- `vite`, `laravel-vite-plugin`
- `axios` (for non-Livewire API calls if any)

---

## 13. Testing Strategy

- **Unit tests:** `NutritionCalculator`, `UnitConverter`, scaling math, BMR formulas.
- **Feature tests:** auth flows, recipe CRUD, calculator endpoint, favorites, permissions, admin policies.
- **Browser tests (Dusk):** registration, recipe view + calculator interaction, favoriting.
- **Coverage target:** 70%+ on services and controllers.
- **CI:** GitHub Actions — lint (Pint), static analysis (Larastan level 6), tests, build assets.

---

## 14. Local Development

The canonical local setup is **Laravel Sail** (Docker Compose wrapper, MIT-licensed, free).

### 14.1 Prerequisites
- Docker Desktop (free for personal/small-business use) **or** Rancher Desktop / Podman Desktop as a free alternative on Windows.
- Git, ~8 GB free RAM allocated to Docker.
- WSL2 backend enabled on Windows (recommended for performance).

### 14.2 First-time Setup

```bash
git clone <repo> recipe-hub
cd recipe-hub
cp .env.example .env

# Bootstrap composer deps via a throwaway PHP container (no host PHP needed)
docker run --rm -v "$(pwd):/app" -w /app composer:latest install --ignore-platform-reqs

# Bring up the stack
./vendor/bin/sail up -d

# App key, migrations, seed data, storage link
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link

# Frontend
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

App available at `http://localhost`.

### 14.3 Sail Services (`docker-compose.yml`)

| Service | Image | Port (host) | Purpose |
|---|---|---|---|
| `laravel.test` | custom (PHP 8.5 + nginx) | 80 | App container |
| `mysql` | mysql:8.0 | 3306 | Database |
| `redis` | redis:7-alpine | 6379 | Cache, sessions, queues |
| `meilisearch` | getmeili/meilisearch:latest | 7700 | Search index |
| `mailpit` | axllent/mailpit | 1025 / 8025 | SMTP catcher + UI |
| `minio` (optional) | minio/minio | 9000 / 8900 | S3-compatible storage emulator |

Install with:
```bash
php artisan sail:install --with=mysql,redis,meilisearch,mailpit,minio
```

### 14.4 Convenient Shell Alias

Add to `~/.bashrc` / `~/.zshrc`:
```bash
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
```
Then: `sail up -d`, `sail artisan ...`, `sail npm run dev`, `sail test`.

### 14.5 Day-to-Day Commands

| Task | Command |
|---|---|
| Start stack | `sail up -d` |
| Stop stack | `sail down` |
| Tail logs | `sail logs -f laravel.test` |
| Run tests | `sail test` or `sail pest` |
| Open shell in app container | `sail shell` |
| Run artisan | `sail artisan <cmd>` |
| Run composer | `sail composer <cmd>` |
| Run npm | `sail npm <cmd>` |
| Queue worker (foreground) | `sail artisan queue:work` |
| Horizon | `sail artisan horizon` |
| Reset DB | `sail artisan migrate:fresh --seed` |
| Tinker | `sail artisan tinker` |

### 14.6 Local `.env` Defaults

```env
APP_NAME="Recipe Hub"
APP_ENV=local
APP_URL=http://localhost
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=recipe_hub
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=redis
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=masterKey

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="hello@recipe-hub.test"

FILESYSTEM_DISK=local
# For S3 emulation via Minio:
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=sail
# AWS_SECRET_ACCESS_KEY=password
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=local
# AWS_ENDPOINT=http://minio:9000
# AWS_USE_PATH_STYLE_ENDPOINT=true
```

### 14.7 Troubleshooting Notes (Windows / WSL2)

- Keep the project **inside the WSL2 filesystem** (`\\wsl$\Ubuntu\home\...`), not under `/mnt/c/`, for ~10× faster file I/O.
- If port 80 is taken (IIS, Skype), set `APP_PORT=8080` in `.env` before `sail up`.
- After OS restart, run `sail up -d` again — containers don't autostart unless configured.

---

## 15. Deployment

**Hosting:** single VPS provisioned and managed by **Laravel Forge**.

### 15.1 Environments
- **Local:** Sail.
- **Staging:** separate Forge site on the same server (subdomain, e.g. `staging.recipe-hub.app`), tracking `develop` branch.
- **Production:** Forge site tracking `main` branch.

### 15.2 Server Stack (provisioned by Forge)
- Ubuntu 24.04 LTS.
- nginx (Forge default) + PHP-FPM 8.5.
- MySQL 8.0 (local to the VPS).
- Redis 7 (local).
- MeiliSearch (installed via Forge "Daemons" or manually as a systemd service on port 7700, reverse-proxied or bound to localhost only).
- Horizon worker daemon (Forge daemon, auto-restart on deploy).
- Supervisor for `php artisan queue:work` redundancy if needed (Horizon usually suffices).
- Server timezone: **UTC**.
- Certbot/Let's Encrypt SSL via Forge (auto-renew).
- UFW firewall: 22, 80, 443 only; MySQL/Redis/MeiliSearch bound to `127.0.0.1`.

### 15.3 Recommended VPS Sizing (launch)
- 2 vCPU, 4 GB RAM, 80 GB SSD (e.g., Hetzner CPX21, DigitalOcean 4 GB, Vultr Cloud Compute 4 GB) — ~$10–25/month.
- Scale vertically as traffic warrants; defer horizontal scaling and managed DB until needed.

### 15.4 Deploys
- Forge "Quick Deploy" enabled on `main`: every push triggers the deploy script.
- Deploy script:
  ```bash
  cd $FORGE_SITE_PATH
  git pull origin $FORGE_SITE_BRANCH
  composer install --no-dev --optimize-autoloader --no-interaction
  npm ci && npm run build
  php artisan migrate --force
  php artisan config:cache route:cache view:cache event:cache
  php artisan queue:restart
  php artisan horizon:terminate
  ```
- Maintenance mode used only for breaking migrations: `php artisan down --secret="..."` ... `up`.
- Rollback: `git reset --hard <prev-sha>` + redeploy script.

### 15.5 Storage
- v1: local disk (`storage/app/public`) symlinked into `public/storage`. Media library stores files locally.
- Backed up nightly to off-server location (see 15.7).
- Migration to S3-compatible storage (Cloudflare R2 / Backblaze B2 / DigitalOcean Spaces) when media volume justifies it — driver swap, no schema change.

### 15.6 Assets
- Built with Vite (`npm run build`) during deploy. Output in `public/build/`.
- Served by nginx with long-cache headers (Vite emits hashed filenames).
- Optional later: front the site with Cloudflare for CDN/WAF/free SSL fallback.

### 15.7 Backups
- **Database:** `spatie/laravel-backup` package, nightly cron (`php artisan backup:run`), uploaded to off-server bucket (Backblaze B2 / S3). 14-day retention.
- **Media:** included in the same backup job.
- **Restore drill:** documented runbook, test once per quarter on staging.

### 15.8 Monitoring & Alerts
- Forge "Site Monitoring" + uptime check (UptimeRobot or BetterStack free tier).
- Sentry for application errors (PHP + JS).
- Server metrics via Forge dashboard; for deeper insight add a free Grafana Cloud / New Relic tier later.

---

## 16. Roadmap & Phasing

### MVP (v1.0) — ~6–8 weeks
1. Auth, user cabinet (profile + health + targets).
2. Ingredient catalog + admin CRUD + CSV import.
3. Recipe catalog + admin CRUD + media.
4. Recipe detail + portion calculator (3 modes).
5. Favorites + calculator history.
6. Filament admin panel.
7. Public API v1.

### v1.1
- PDF recipe import (auto-extract ingredients).
- Social login.
- Ratings + comments.
- Meal planner (week view, multi-recipe day plan).
- Shopping list generator from selected recipes.

### v1.2
- Mobile API hardening, public API keys.
- Personalized recommendations.
- Additional locales beyond EN/UK based on user demand.

---

## 17. Decisions Locked

All MVP decisions are locked. Remaining items below are intentional v1.1+ deferrals.

### 17.1 Locked
- **Backend:** Laravel 12.x on PHP 8.5.
- **Frontend:** Livewire 3 + Alpine.js 3 + Tailwind CSS 4 (Flux UI for primitives, ApexCharts for charts). Upgraded from v3 → v4 because Laravel 12 scaffolds with v4, Flux UI requires v4, and forms/typography plugins are built-in.
- **Admin panel:** Filament 3, **English-only UI and content entry** (no translation tabs).
- **Local dev:** Laravel Sail (Docker Compose).
- **Database:** MySQL 8.0.
- **Localization:** English default + Ukrainian secondary for **static UI strings only**. Recipe/ingredient content is English only. Locale switcher persists to **cookie only** (no DB column).
- **Search:** MeiliSearch (via Laravel Scout).
- **Hosting:** Single VPS managed by Laravel Forge.
- **Time zones:** All timestamps stored in **UTC**; app `timezone=UTC`; display in UTC. Per-user timezone preference deferred to a later release.
- **Ingredient seed dataset:** USDA FoodData Central — Foundation Foods + SR Legacy, public domain. Curated subset of ~500–800 cooking-relevant ingredients imported via artisan command, manually enriched with densities, allergens, and aliases.
- **Social login:** Deferred to v1.1. Email + password only at launch; Sanctum + Fortify in place so Socialite can be added later without refactoring.
- **Branding & domain:** Placeholder for v1 development — working name **"Recipe Hub"**, default Tailwind palette (slate / emerald accents), no custom logo (text wordmark only). Final branding (name, logo, palette, custom domain) to be applied before public launch as a non-blocking polish pass.

### 17.2 Deferred to v1.1+
- Social login (Google / Facebook via Socialite).
- Final branding pass (name, logo, palette, custom domain).
- Per-user timezone preference (UI + display conversion).
- PDF recipe import.
- Ratings, comments, meal planner, shopping list generator.
- Additional locales beyond EN/UK.

---

## 18. Acceptance Criteria (MVP highlights)

- A guest can browse ≥ 50 seeded recipes with photos and filter by category and max calories.
- A registered user can set a daily calorie target and see the calculator suggest portions on any recipe.
- Calculator output for a known recipe matches a hand-calculated reference within ±1% on totals.
- An admin can create a new ingredient with full nutrition data and immediately use it in a new recipe.
- An admin can import 100+ ingredients from CSV in a single operation with row-level error reporting.
- All public pages score ≥ 90 on Lighthouse Performance and Accessibility.
- 2FA can be enabled and required for admin accounts.

---

*End of specification.*

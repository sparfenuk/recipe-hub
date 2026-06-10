# Code Quality Improvement Plan

Source: code quality review, 2026-06-10. Scope: `app/`, `database/`, `resources/views/`, `tests/`, tooling config. Every finding below was verified against the source before inclusion; line numbers reflect `main` at review time and may drift.

Items are grouped into four phases by priority. Each item lists the problem, the fix, short reasoning, file references, and how to test it. Phase 1 items are bugs or latent bugs and should land first; later phases are safe to interleave with feature work.

Suggested branch naming: `fix/CQ.<num>-<slug>` (e.g. `fix/CQ.1-scout-stale-index`). One item = one branch/PR; Phase 3 items can be batched if convenient.

Quality gates apply to every item: `sail composer pint`, `sail composer larastan`, `sail test`, plus a manual smoke check in the running Sail stack.

---

## Phase 1 — Correctness bugs

### CQ.1 Search index goes stale when ingredients change

- **Problem:** Meilisearch indexes `ingredient_names_en` / `ingredient_names_uk` per recipe, but nothing re-syncs a recipe after its ingredients change:
  1. `RecipeIngredientObserver` only dispatches `RecalculateRecipeNutrition`, and that job finishes with `saveQuietly()`, which skips model events — including Scout sync.
  2. `IngredientObserver::updated()` only reacts to `NUTRITION_COLUMNS`; renaming an ingredient never touches its recipes at all.

  Net effect: after an admin edits or renames ingredients, searching by ingredient returns wrong results until a manual `scout:import`.
- **Fix:**
  1. In `RecalculateRecipeNutrition::handle()`, after `saveQuietly()`, explicitly re-index: `$recipe->searchable();` (keep `saveQuietly()` — it correctly avoids audit noise and observer loops; Scout sync just has to be triggered by hand once events are suppressed).
  2. In `IngredientObserver::updated()`, also react to `name` changes. A rename does not need nutrition recompute, so dispatch the recompute job only for nutrition columns, and for name changes re-index affected recipes directly (e.g. `Recipe::whereIn('id', $recipeIds)->searchable()` via Scout's collection helper, or reuse the job since it now ends with `searchable()` — one code path, slightly more work per job, acceptable at this scale).
- **Reasoning:** This is the only verified data-corruption-class bug in the review. Ingredient search is a headline feature (include/exclude filters, autocomplete), and a silently stale index is invisible in tests that reimport fixtures.
- **Files:**
  - `app/Jobs/RecalculateRecipeNutrition.php:44` (`saveQuietly()` with no follow-up sync)
  - `app/Observers/RecipeIngredientObserver.php:11-18`
  - `app/Observers/IngredientObserver.php:11-19` (`NUTRITION_COLUMNS`), `:22-36` (`updated()`)
  - `app/Models/Recipe.php:240-247` (`ingredient_names_*` in `toSearchableArray()`)
- **Test:** Feature test with Scout's array/fake driver: update an ingredient name, assert the recipe was made searchable again (e.g. `Recipe::search()` fake assertions or spying on `searchable()`); second test for `RecipeIngredient` create/update/delete reaching `searchable()` through the job.

### CQ.2 Livewire properties are tamper-able — no `#[Locked]` anywhere

- **Problem:** No component uses `#[Locked]`. Any public property can be rewritten from the browser via the Livewire payload. Concretely:
  - `FavoriteButton::$recipeId` — a user can favorite draft/unpublished recipes, or send a nonexistent ID and get a 500 via FK violation on `attach()`.
  - `PortionCalculator::$recipe` is model-bound (safe — re-resolved by key), but `$originalServings` is a derived value; tampering skews every scale-factor calculation.
- **Fix:** Add `#[Locked]` to identity and derived properties that are set in `mount()` and never legitimately changed by the client: `FavoriteButton::$recipeId`, `PortionCalculator::$originalServings`. Audit the remaining components (`RecipeDetail`, Cabinet forms) for the same pattern while in there. In `FavoriteButton::toggle()`, also guard `attach()` against unpublished recipes if drafts must stay private to favorites.
- **Reasoning:** One attribute per property closes a whole tamper class. Cheap insurance now, much harder to retrofit after more components copy the unlocked pattern.
- **Files:**
  - `app/Livewire/FavoriteButton.php:12` (property), `:16-18` (`mount()`)
  - `app/Livewire/PortionCalculator.php:27-39` (public properties block)
- **Test:** Livewire test calling `->set('recipeId', $otherId)` and asserting a `PropertyNotWritableException` / no state change; existing favorite and calculator tests stay green.

### CQ.3 Recipe description rendered as raw HTML without sanitization

- **Problem:** `{!! $recipe->description !!}` outputs Filament RichEditor HTML unescaped. Input is admin-only today, so exposure is contained — but a single compromised admin session becomes stored XSS for every visitor. Steps already do this correctly with `nl2br(e(...))`; the description is the one unsanitized sink.
- **Fix:** Sanitize on output. Either add `mews/purifier` and render `{!! Purifier::clean($recipe->description) !!}`, or — dependency-free — whitelist via `strip_tags()` with the tag set RichEditor actually produces (`<p><br><strong><em><ul><ol><li><a><h2><h3><blockquote>`), wrapped in a small helper or Blade component so the PDF view can reuse it. Keep the JSON-LD output as is — it already strips tags and escapes correctly.
- **Reasoning:** Defense in depth for the only raw-HTML sink in the app. The cost is one helper; the alternative (trusting every future admin account forever) is not a control.
- **Files:**
  - `resources/views/livewire/recipe-detail.blade.php:94` (the sink), `:155` (correct pattern for steps, for contrast)
  - `app/Filament/Resources/RecipeResource.php:76` (`RichEditor::make('description')` — the input side)
- **Test:** Feature test: save a description containing `<script>alert(1)</script><p>ok</p>`, assert the rendered page contains the paragraph but not the script tag.

### CQ.4 Console commands decode data files without error handling

- **Problem:** Four commands `json_decode(..., true)` seeder/rule files without `JSON_THROW_ON_ERROR`. A malformed file yields `null`, and the commands then array-access it — failing with a confusing "Cannot access offset on null" instead of "your JSON is broken at line N". `ApplyIngredientNutritionOverrides` already does it right (`flags: JSON_THROW_ON_ERROR`).
- **Fix:** Add `flags: JSON_THROW_ON_ERROR` to all six call sites, matching the existing correct pattern. Optionally catch `JsonException` at the top of each `handle()` to print the file path and re-throw — these are operator-facing tools, so the file name in the error message is the actual fix-it hint.
- **Reasoning:** Five-minute change that converts silent garbage-in into a loud, located failure. These commands mutate ingredient/recipe data, where garbage-in is expensive to undo.
- **Files:**
  - `app/Console/Commands/EnrichIngredients.php:41`
  - `app/Console/Commands/AutoTagRecipes.php:36`
  - `app/Console/Commands/AutoCuisineRecipes.php:35`
  - `app/Console/Commands/ImportUsdaIngredients.php:397,401,406`
  - `app/Console/Commands/ApplyIngredientNutritionOverrides.php:50` (reference pattern)
- **Test:** Per command: point it at a fixture file with broken JSON, assert it fails with a `JsonException`/clear message and writes nothing to the DB.

---

## Phase 2 — Performance

### CQ.5 Catalog filter options re-queried on every Livewire update

- **Problem:** `RecipeBrowser::render()` runs four taxonomy queries (`Category`, `Cuisine`, `Tag`, `Allergen`), each with a `whereHas` subquery, on every render — including every search keystroke and every filter toggle. The data only changes when a recipe is published/unpublished or taxonomies are edited.
- **Fix:** Wrap the four queries in `Cache::remember()` keyed by locale (sort order is locale-dependent), e.g. `filter-options.categories.{locale}`, with a model-event-based flush (Recipe saved/deleted, taxonomy saved) or simply a short TTL (10–60 min) given admin-only edits. TTL is the MVP-appropriate option; event-flush can come later if staleness ever bites.
- **Reasoning:** Removes 4 queries x N subqueries from the hottest interactive path in the app for ~10 lines of code. Locale key matters because `orderBy('name->uk')` differs per locale.
- **Files:** `app/Livewire/RecipeBrowser.php:168-186` (`render()`), specifically `:174-180`
- **Test:** Existing `RecipeBrowserTest` / `CatalogFiltersTest` stay green; add an assertion that filter options render from cache (e.g. `Cache::spy()` or DB query count assertion via `expectsDatabaseQueryCount` on second render).

### CQ.6 "Load more" refetches all previously loaded rows

- **Problem:** `loadMore()` grows `$perPage` and `getRecipes()` always paginates page 1 (`paginate($this->perPage, ['*'], 'page', 1)`). Click N transfers all rows from clicks 1..N again — quadratic data transfer over a browsing session.
- **Fix (MVP-appropriate):** Accept and document. The catalog is small and the pattern keeps Livewire state trivially simple. Add a short comment at the call site explaining the trade-off so it is a decision, not an accident. If the catalog grows past a few hundred recipes, switch to cursor pagination with an accumulated `$loadedIds` list or `wire:key`-stable appending.
- **Reasoning:** Rewriting to cursor accumulation now is real complexity (state, dedupe, sort stability) for a catalog measured in dozens of recipes. The cheap correct move is making the trade-off visible.
- **Files:** `app/Livewire/RecipeBrowser.php:144-147` (`loadMore()`), `:230` (`paginate(..., 'page', 1)`)
- **Test:** None needed for the comment; if/when cursor pagination lands, port the existing pagination tests.

---

## Phase 3 — Architecture and maintainability

### CQ.7 Five near-identical Filament taxonomy resources

- **Problem:** `CategoryResource`, `CuisineResource`, `TagResource`, `AllergenResource`, `IngredientCategoryResource` each duplicate the same translatable name fields, slug handling, table columns, and an identical `searchable(query: ...)` closure for JSON-column search (verified byte-similar across all five). Any change to taxonomy admin UX is currently a five-file edit that will drift.
- **Fix:** Extract a `BaseTaxonomyResource` (or a `HasTaxonomyResource` trait if inheritance fights Filament's static API) holding: shared form schema builder, shared table definition, and one static helper for the translatable-search closure. Each concrete resource keeps only model, icon, labels, and genuinely unique columns (e.g. `Tag::$type`).
- **Reasoning:** Verified duplication, five copies, guaranteed drift. The translatable-search closure alone appears 7 times across the admin (also in `RecipeResource` and `IngredientResource` — extract the helper so they share it too).
- **Files:**
  - `app/Filament/Resources/CategoryResource.php:56,67`
  - `app/Filament/Resources/CuisineResource.php:53`
  - `app/Filament/Resources/TagResource.php:62`
  - `app/Filament/Resources/AllergenResource.php:53`
  - `app/Filament/Resources/IngredientCategoryResource.php:50,61`
  - `app/Filament/Resources/RecipeResource.php:316`, `app/Filament/Resources/IngredientResource.php:206` (same closure, share the helper)
- **Test:** Existing `TaxonomyAdminTest` covers behavior; it must pass unchanged. That is the point of the refactor — no behavior change, structural only.

### CQ.8 Nutrition math duplicated between PortionCalculator and NutritionCalculator

- **Problem:** `PortionCalculator::computeIngredientBreakdown()` reimplements the grams-conversion plus per-100g macro loop that `NutritionCalculator::totalsFor()` owns (same `grams_override` precedence, same `UnitConverter::toGrams()` call shape, same `?? 0` macro defaults). The two copies already differ subtly (the component try/catches conversion failures; the service does not), and any future change — say, adding sugar or sodium — must be made twice or the calculator and cached totals diverge.
- **Fix:** Extract a single per-ingredient method on `NutritionCalculator`, e.g. `contributionFor(RecipeIngredient $ri): ?NutritionTotals` (null for optional/unconvertible rows), and have both `totalsFor()` and the Livewire component consume it. Decide the failure semantics once: skipping unconvertible rows (component behavior) is the safer default for both paths.
- **Reasoning:** Per spec, `app/Services/Nutrition/` owns this math. The duplication is the kind that produces "calculator disagrees with the recipe page" bugs, which are user-visible and hard to trace.
- **Files:**
  - `app/Livewire/PortionCalculator.php:206-244` (`computeIngredientBreakdown()`)
  - `app/Services/Nutrition/NutritionCalculator.php:19-42` (`totalsFor()` loop)
- **Test:** Existing `NutritionCalculatorTest` and `PortionCalculatorTest` must pass unchanged; add one unit test for the new shared method covering optional rows, `grams_override`, and a conversion failure.

### CQ.9 Recipe duplication logic lives inside the Filament resource

- **Problem:** `RecipeResource::duplicateRecipe()` (~50 lines: replicate, clone ingredients, steps, media, translations) is business logic embedded in admin UI code. It cannot be unit-tested without booting Filament and cannot be reused (e.g. by a future console command or API).
- **Fix:** Move to `app/Services/RecipeDuplicationService::duplicate(Recipe $recipe): Recipe`. The Filament row action and bulk action become one-liners calling the service. Wrap the clone in a DB transaction while extracting (currently easy to leave a half-cloned recipe if a media copy throws).
- **Reasoning:** Testability plus a free correctness improvement (transaction). Mechanical extraction, low risk.
- **Files:** `app/Filament/Resources/RecipeResource.php:396-399` (row action), `:427-433` (bulk action), `:442-489` (`duplicateRecipe()`)
- **Test:** New unit/feature test for the service: duplicated recipe has cloned ingredients/steps/media, distinct slug, `draft` status; existing `RecipeAdminTest` duplication coverage stays green.

### CQ.10 JSON-LD construction lives in the Blade template

- **Problem:** ~70 lines of `@php` array-building for schema.org markup sit in the view. Untestable without rendering the whole page, and invisible to Larastan.
- **Fix:** Move construction into `RecipeDetail` as a `#[Computed] public function jsonLd(): array`. The view keeps only the `<script type="application/ld+json">` line with the existing `json_encode` flags (`JSON_HEX_TAG | JSON_HEX_AMP` — keep these, they are the XSS guard).
- **Reasoning:** Pure relocation that makes SEO output unit-testable (the existing `RecipeSchemaTest` becomes a component test instead of HTML scraping) and type-checked.
- **Files:** `resources/views/livewire/recipe-detail.blade.php:239-309`, `app/Livewire/RecipeDetail.php`
- **Test:** Port `RecipeSchemaTest` assertions to call the computed property directly; keep one end-to-end assertion that the script tag renders.

### CQ.11 Magic strings for status, difficulty, and type columns

- **Problem:** `'published'`, `'draft'`, difficulty levels, `Tag::$type` (`diet`/`cuisine`/`misc`), and `Unit::$type` (`mass`/`volume`/`count`) are bare strings across models, Livewire queries, Filament forms, seeders, and factories. `Recipe::where('status', 'published')` appears in at least six call sites; helper methods like `Unit::isMass()` hand-compare strings.
- **Fix:** Introduce backed enums (`RecipeStatus`, `RecipeDifficulty`, `TagType`, `UnitType`) under `app/Enums/`, add them to model `casts()`, and sweep call sites. Filament selects take `options(RecipeStatus::class)` natively. Do it one enum per commit; `RecipeStatus` first since it gates public visibility (typo in a status string = silently hidden or leaked recipe).
- **Reasoning:** Compile-time safety on the strings that control publication visibility and unit conversion branching, plus IDE discoverability. Larastan level 6 already understands enum casts.
- **Files (representative, not exhaustive):**
  - `app/Models/Recipe.php:226` (`shouldBeSearchable()` status compare)
  - `app/Models/Unit.php:31-44` (`isMass()`/`isVolume()`/`isCount()`)
  - `app/Models/Tag.php:26-39` (`isDiet()`/`isCuisine()`/`isMisc()`)
  - `app/Livewire/RecipeBrowser.php:174-180,192` (status literals)
  - `app/Livewire/PortionCalculator.php:31,50,313` (mode strings — same treatment optional)
- **Test:** Full suite green per enum; no new tests strictly required, but factories should switch to enum cases so invalid states cannot be seeded.

---

## Phase 4 — Tests and tooling

### CQ.12 Untested console commands

- **Problem:** `EnrichIngredients`, `AutoTagRecipes`, `AutoCuisineRecipes`, and `ReseedRecipes` have no test coverage (verified by grep; `ImportUsdaIngredients`, `ApplyIngredientNutritionOverrides`, and `PruneAudits` are covered). These commands bulk-mutate ingredient and recipe data based on keyword rules — exactly the kind of logic that regresses silently.
- **Fix:** Add `tests/Feature/Console/` tests per command covering: happy path against small fixtures, dry-run mode (if present) writes nothing, malformed rules file fails loudly (pairs with CQ.4), and idempotency (running twice produces the same end state).
- **Reasoning:** The covered commands prove the pattern is cheap here (`ImportUsdaIngredientsTest` exists as a template). Auto-tagging bugs corrupt taxonomy assignments en masse.
- **Files:** `app/Console/Commands/EnrichIngredients.php`, `AutoTagRecipes.php`, `AutoCuisineRecipes.php`, `ReseedRecipes.php`; template: `tests/Feature/ImportUsdaIngredientsTest.php`
- **Test:** This item is the tests.

### CQ.13 BmrCalculator has no direct unit tests

- **Problem:** `BmrCalculator` is only exercised indirectly through `HealthFormTest`. Its silent fallback to activity factor `1.2` for unknown activity levels, and boundary behavior (sex values, extreme inputs), are unasserted — a regression there would surface as subtly wrong calorie targets, which no integration test pins down numerically.
- **Fix:** Add `tests/Unit/Services/BmrCalculatorTest.php`: known Mifflin-St Jeor reference values for both sexes, each activity level's factor, and the unknown-level fallback (assert it explicitly so the behavior is a documented decision, or change it to throw and assert that).
- **Reasoning:** `NutritionCalculatorTest` and `UnitConverterTest` set the precedent — every service in `app/Services/Nutrition/` deserves direct numeric tests. This is the one gap.
- **Files:** `app/Services/Nutrition/BmrCalculator.php` (fallback at the `?? 1.2` in `tdee()`); template: `tests/Unit/Services/NutritionCalculatorTest.php`
- **Test:** This item is the tests.

### CQ.14 Housekeeping: placeholder tests, rate limiting parity

- **Problem:**
  1. Skeleton `ExampleTest.php` files remain in both suites (assert `true === true` / homepage 200 — the latter is already covered by real tests).
  2. `RateLimiter` is used only in `PortionCalculator::saveCalculation()`. Favorite toggle, profile save, and history delete are unthrottled writes. Low urgency while the app sits behind `EnsurePrivateAccess`, but cheap parity.
- **Fix:** Delete both `ExampleTest.php` files. Add the existing rate-limit pattern (copy from `PortionCalculator.php:305-311`) to `FavoriteButton::toggle()`, `ProfileForm::save()`, and `CalculationHistory::delete()` with generous limits (e.g. 30/min).
- **Reasoning:** Placeholders add suite noise; rate-limit parity closes an abuse vector before the private banner ever comes down, while the pattern is fresh.
- **Files:** `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`, `app/Livewire/FavoriteButton.php:27`, `app/Livewire/Cabinet/ProfileForm.php`, `app/Livewire/Cabinet/CalculationHistory.php:17-26`; pattern: `app/Livewire/PortionCalculator.php:305-311`
- **Test:** Extend `RateLimitingTest` to cover the newly throttled actions.

### CQ.15 Raise PHPStan to level 7

- **Problem:** `phpstan.neon` sits at level 6 with no baseline — a clean position that makes a bump cheap. Level 7 adds union-type and partially-wrong-argument checks that would have flagged several patterns the review found by hand (nullable relation access, mixed returns from `json_decode`).
- **Fix:** Bump `level: 7` in `phpstan.neon`, fix what surfaces (expect a modest batch of nullable-access guards), and update the CLAUDE.md / plan quality-gate wording from "level 6" to "level 7". Attempt level 8 afterwards only if the delta is small; do not introduce a baseline to force it — a baseline converts errors into permanent debt.
- **Reasoning:** The project is at its cheapest-ever point to raise strictness (small surface, zero suppressed errors). Each later feature raises the price.
- **Files:** `phpstan.neon:7`, `CLAUDE.md` (quality gates section), `docs/plan.md` (sizing and quality gates)
- **Test:** `sail composer larastan` green at the new level; full suite green.

---

## Verified non-issues (do not re-litigate)

Recorded so future reviews do not rediscover these as findings:

- `CalculationHistory::delete()` is correctly authorization-scoped via `where('user_id', ...)` (`app/Livewire/Cabinet/CalculationHistory.php:22-25`). No policy needed at this scale.
- `created_at` in `CalculatorSession::$fillable` is deliberate: the model sets `$timestamps = false` and manages the column manually (`app/Models/CalculatorSession.php:10,19`).
- "Missing FK indexes" on `recipe_ingredients` etc.: MySQL/InnoDB auto-creates indexes for foreign key constraints created via `constrained()`. Not a gap.
- `saveQuietly()` in `RecalculateRecipeNutrition` is the right call for suppressing audit/observer loops — the gap was only the missing Scout sync (CQ.1), not the quiet save itself.
- `loadMissing()` called from multiple computed properties in `PortionCalculator` is idempotent and effectively free after the first call. Not an N+1.
- `RecipePdfController` is covered by `tests/Feature/RecipePdfTest.php`.
- Absence of `declare(strict_types=1)` is the consistent Laravel-skeleton default across the codebase, not drift. Adopting it globally is a valid but separate decision; do not add it piecemeal.

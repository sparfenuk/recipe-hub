# UI/UX Improvement Plan

Source: front-end design review, 2026-06-10. Scope: public site + cabinet. Admin (Filament) excluded.

Items are grouped into four phases by impact. Each item lists the problem, the fix, short reasoning, and file references. Line numbers reflect the state of `main` at the time of the review and may drift.

Suggested branch naming: `fix/UX.<num>-<slug>` (e.g. `fix/UX.1-locale-switcher-query`). One phase = one PR is a reasonable granularity; Phase 1 items are independent enough to ship individually.

Quality gates apply to every item: `sail composer pint`, `sail composer larastan`, `sail test`, plus a manual smoke test in both `uk` and `en` locales and at mobile width (375px).

---

## Phase 1 — High-impact fixes (user-visible bugs and core-feature friction)

### UX.1 Locale switcher drops query string

- **Problem:** The switcher links to `?locale={{ $code }}`, which replaces the entire query string. Switching language on a filtered catalog page (`?q=...&categories[]=...`) silently wipes search and filters. UK-primary audience hits this constantly.
- **Fix:** Build the link with `request()->fullUrlWithQuery(['locale' => $code])` so existing parameters survive.
- **Reasoning:** One-line fix for a state-loss bug on the most-used control for a bilingual site.
- **Files:** `resources/views/livewire/locale-switcher.blade.php:23`
- **Test:** Feature test asserting the rendered link preserves `q` and `categories` params; manual check on a filtered catalog page.

### UX.2 Portion calculator buried on mobile, not sticky on desktop

- **Problem:** In the recipe detail grid the `<aside>` with the calculator renders after ingredients, steps, and gallery in DOM order, so on phones the app's differentiating feature sits several screens below the fold. On desktop the sidebar scrolls away on long recipes, leaving a tall empty column.
- **Fix:** Reorder with grid utilities and make the aside sticky:
  - aside: `lg:order-2 lg:sticky lg:top-24 lg:self-start`
  - main column: `lg:order-1 lg:col-span-2`
  - Place the aside first in DOM so mobile shows it right after the title/meta block.
- **Reasoning:** Pure CSS/markup reorder, no logic changes; directly increases exposure of the feature the product is built around. `top-24` clears the sticky header (`z-40`, ~57px) with margin.
- **Files:** `resources/views/livewire/recipe-detail.blade.php:88-235`
- **Test:** Manual at 375px (calculator above ingredients) and 1280px (sidebar stays in view while scrolling steps).

### UX.3 Scaled amounts do not update the main ingredient list (and print is wrong)

- **Problem:** Scaling to N servings updates only the calculator's own list. The left-column ingredient list keeps original amounts, so the page shows two contradictory lists. Printing is worse: the calculator is `print:hidden`, so a user who scaled and hits Print gets the original amounts.
- **Fix (recommended):** Make the calculator the single source of truth for amounts.
  1. In `PortionCalculator`, dispatch a Livewire event (e.g. `portion-scaled`) with the scale factor / scaled ingredient payload whenever it changes.
  2. In `RecipeDetail`, listen and re-render the main ingredient list with scaled amounts, plus a small banner: "Amounts shown for N servings — reset" linking back to original.
  3. Remove the duplicated ingredient list from the calculator card (keep only inputs, nutrition, charts) — one list, always correct, print included.
- **Fix (cheaper fallback):** Keep both lists but add an explicit label to the main list ("Original amounts for N servings") and remove `print:hidden` from the scaled list when scaled. Choose the fallback only if event wiring proves disruptive to existing tests.
- **Reasoning:** Two lists with different numbers on one page is a correctness problem, not polish; the print case actively misleads while cooking — exactly when users print.
- **Files:** `resources/views/livewire/recipe-detail.blade.php:98-138` (main list), `resources/views/livewire/portion-calculator.blade.php:158-192` (scaled list), `app/Livewire/PortionCalculator.php`, `app/Livewire/RecipeDetail.php`
- **Test:** Pest: scaling emits event and detail list reflects scaled amounts; print stylesheet manual check (Ctrl+P preview) with a scaled recipe.

### UX.4 Mobile filter wall in the catalog

- **Problem:** On `< lg` the whole filter sidebar (filters card, two ingredient autocompletes, diet tags, allergens) stacks above results. Users scroll ~2 screens of controls before the first recipe.
- **Fix:**
  1. Wrap the aside content in an Alpine toggle, collapsed by default below `lg`: a "Filters (n)" button (n = active filter count, the component already has `hasActiveFilters()`; add a `activeFilterCount()` helper) that expands the panel.
  2. Add active-filter chips above the results grid, one per applied filter, each with a remove (x) action calling the existing toggle/clear methods. Show "Clear all" when 2+ chips.
- **Reasoning:** Results-first is the established mobile pattern for faceted catalogs; chips give visibility and one-tap removal without reopening the panel. All server-side methods already exist — this is markup + a count helper.
- **Files:** `resources/views/livewire/recipe-browser.blade.php:8-186` (aside), `app/Livewire/RecipeBrowser.php`
- **Test:** Pest for `activeFilterCount()`; manual at 375px — results visible within one scroll, chips removable.

### UX.5 No loading feedback when filters change

- **Problem:** `loadMore` has a spinner, but toggling a category, typing a kcal limit, or changing sort gives no visual signal during the Meilisearch round trip; the grid sits stale.
- **Fix:** On the results container add:
  `wire:loading.class="opacity-50 pointer-events-none" wire:target="toggleCategory, toggleCuisine, max_kcal, max_prep_time, diet_tags, exclude_allergens, sort, clearFilters"`
  Optionally a small spinner next to the results count.
- **Reasoning:** One attribute; perceived performance fix for the page users spend most time on.
- **Files:** `resources/views/livewire/recipe-browser.blade.php:189-293`
- **Test:** Manual — throttle network, toggle a filter, grid dims.

### UX.6 Untranslated difficulty in cards + hardcoded English home title

- **Problem:** Catalog and favorites cards print `ucfirst($recipe->difficulty)` — raw English enum even in `uk` locale. The detail page already does it correctly via `__('recipes.difficulty_' . ...)`. Separately, the home page `<x-layouts.app title="Recipe Hub — Your Personal Recipe & Nutrition Calculator">` is hardcoded English on a UK-primary site (also feeds og:title/twitter:title).
- **Fix:** Replace both `ucfirst(...)` usages with the existing `__('recipes.difficulty_' . $recipe->difficulty)` keys. Move the home title to a lang key (e.g. `book.home_meta_title`) in both `lang/en` and `lang/uk`.
- **Reasoning:** i18n consistency bug; the translation keys already exist for difficulty, so it is a two-line fix plus one new key.
- **Files:** `resources/views/livewire/recipe-browser.blade.php:244`, `resources/views/livewire/cabinet/favorites-list.blade.php:93`, `resources/views/welcome.blade.php:2`
- **Test:** Pest snapshot/feature test rendering a card in `uk` asserts no raw "Easy"/"Medium" strings.

---

## Phase 2 — Design-system and consistency debt

### UX.7 Decide on Flux UI vs. extracted Blade components; use the primary token

- **Problem:** Flux UI is in the locked stack (spec section 17) but zero views use it — every input/button hand-rolls the same Tailwind string (`rounded-lg border-slate-300 ... focus:border-emerald-500 focus:ring-emerald-500`, ~15 copies). `--color-primary` is defined in `app.css` but never referenced; everything hardcodes `emerald-600`. A brand-color change today means a global find-and-replace.
- **Fix:** Make an explicit decision and record it in `docs/spec.md` section 17:
  - **Option A (recommended, lower risk):** drop Flux from the stack, extract `x-ui.input`, `x-ui.select`, `x-ui.button` (primary/secondary variants), `x-ui.badge`, `x-ui.card` Blade components under `resources/views/components/ui/`, and migrate views incrementally (catalog + detail + calculator first). Components reference `primary` token classes (`bg-primary`, `focus:ring-primary` via the existing `@theme` vars, extended with the shades needed).
  - **Option B:** adopt Flux components site-wide. Larger diff, restyles everything at once; only choose if Flux's look is acceptable wholesale.
- **Reasoning:** This is the root cause of most inconsistency below; doing it before Phase 3/4 means later fixes touch one component instead of fifteen call sites. Spec requires stack changes to be recorded.
- **Files:** `resources/css/app.css:11-16`, all views under `resources/views/` (incremental), `docs/spec.md` section 17
- **Test:** Visual regression by eye per migrated view; Pint/Larastan/Pest unchanged.

### UX.8 Unify cabinet breadcrumbs

- **Problem:** Favorites uses a "Dashboard / Favorites" breadcrumb; the health form uses "Back to cabinet" with an arrow. Two navigation patterns for sibling pages.
- **Fix:** Extract one `x-ui.breadcrumb` component (the slash style is more conventional and scales to deeper paths) and use it in favorites, health form, profile form, and calculation history.
- **Reasoning:** Sibling pages with different wayfinding patterns read as unfinished; extraction is trivial once UX.7 establishes the components directory.
- **Files:** `resources/views/livewire/cabinet/favorites-list.blade.php:2-7`, `resources/views/livewire/cabinet/health-form.blade.php:2-8`, `resources/views/livewire/cabinet/profile-form.blade.php`, `resources/views/livewire/cabinet/calculation-history.blade.php`

### UX.9 Duplicate heart icon on the dashboard cards

- **Problem:** "Health profile" and "Favorites" cards both use `heroicon-o-heart` — two adjacent cards with identical icons.
- **Fix:** Keep the heart for Favorites; switch Health profile to `heroicon-o-clipboard-document-check` or `heroicon-o-scale` (body metrics connotation).
- **Reasoning:** Icons exist to differentiate at a glance; duplicates defeat that.
- **Files:** `resources/views/livewire/cabinet/dashboard.blade.php:34,46`

### UX.10 Align catalog and favorites card design / pagination model

- **Problem:** The catalog uses large horizontal "plate" cards with infinite scroll; favorites uses compact 3-column vertical cards with numbered pagination. Same content type, two systems.
- **Fix:** Extract a shared `x-recipe-card` component with a `variant` prop (`horizontal` for catalog, `compact` for grids), so badges, kcal/time formatting, and hover behavior are defined once. Keep numbered pagination in favorites (small bounded lists) and infinite scroll in the catalog — but consider a grid/list toggle in the catalog later (see UX.22).
- **Reasoning:** The duplicated card markup already drifted (badge styles differ, difficulty bug appeared twice). One component prevents the next drift; the pagination difference is defensible and not worth forcing.
- **Files:** `resources/views/livewire/recipe-browser.blade.php:209-250`, `resources/views/livewire/cabinet/favorites-list.blade.php:43-101`

### UX.11 Author teaser placeholder + stray purple palette

- **Problem:** The home-page author teaser shows an empty purple gradient circle (reads as an unfinished placeholder) and introduces a purple/fuchsia palette used nowhere else, fighting the emerald brand.
- **Fix:** Use the author's real photo (add to `public/images/` or media library); restyle the teaser card to the slate/emerald family used by the adjacent "about the book" card.
- **Reasoning:** The author is the product's trust anchor (book-driven site); a placeholder avatar undermines exactly that section.
- **Files:** `resources/views/welcome.blade.php:127-145`

### UX.12 Red info tooltip in the calculator

- **Problem:** The per-ingredient breakdown info icon is `text-red-500`, but the content is informational. Red signals error/danger.
- **Fix:** Change to `text-slate-400` (or `text-amber-500` if the mismatch warning deserves emphasis).
- **Reasoning:** Color semantics; one class.
- **Files:** `resources/views/livewire/portion-calculator.blade.php:246`

---

## Phase 3 — Accessibility

### UX.13 Keyboard and screen-reader support for custom dropdowns and autocomplete

- **Problem:** The category/cuisine multi-select dropdowns and the ingredient autocomplete are mouse-only. Triggers lack `aria-expanded`/`aria-haspopup`; option lists lack `role="listbox"`/`role="option"`; there is no arrow-key navigation; the dropdowns (unlike the autocomplete) do not close on Escape. Keyboard and screen-reader users cannot filter the catalog at all.
- **Fix:**
  1. Triggers: bind `:aria-expanded="open"`, add `aria-haspopup="listbox"`.
  2. Panels: `role="listbox"` with `aria-multiselectable="true"`; options as `role="option"` with `:aria-selected`.
  3. Alpine keyboard handling: ArrowDown/ArrowUp move an `activeIndex`, Enter toggles the active option, Escape closes and refocuses the trigger. Same pattern for the autocomplete (`aria-activedescendant` on the input, `role="combobox"`).
  4. Extract as one shared Alpine component (`x-data="multiSelect()"` in `resources/js/app.js`) used by both dropdowns and the autocomplete, instead of fixing three copies.
- **Reasoning:** This is the largest a11y gap — core functionality is inaccessible, not merely awkward. Doing it as a shared Alpine behavior keeps the three widgets in sync.
- **Files:** `resources/views/livewire/recipe-browser.blade.php:16-125`, `resources/views/livewire/ingredient-autocomplete.blade.php`, `resources/js/app.js`
- **Test:** Manual keyboard-only walkthrough: Tab to category filter, open, select two, Escape, repeat for autocomplete. Optionally a Dusk test if browser testing gets added later.

### UX.14 Header search: no submit affordance, no label

- **Problem:** Search is Enter-only — the magnifying glass is `pointer-events-none`, there is no `<form>`, no submit button, no `aria-label`. Mouse and assistive-tech users cannot submit. The Alpine block is also duplicated verbatim for desktop and mobile.
- **Fix:** Replace both Alpine blocks with one plain `<form method="GET" action="{{ route('recipes.index') }}">` containing `<input type="search" name="q" aria-label="...">` and a visually-hidden (or icon) submit button. Extract as `x-header-search` to kill the duplication. Native form submission removes the JS entirely.
- **Reasoning:** A GET form is simpler, accessible by default, and deletes ~20 lines of duplicated Alpine.
- **Files:** `resources/views/components/layouts/app.blade.php:72-87,135-149`

### UX.15 Skip-to-content link

- **Problem:** No skip link; keyboard users tab through the full header (nav, search, locale, auth) on every page.
- **Fix:** First element in `<body>`: `<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 ...">{{ __('Skip to content') }}</a>`; add `id="main"` to `<main>`. Same in the guest layout.
- **Reasoning:** Standard, cheap, expected.
- **Files:** `resources/views/components/layouts/app.blade.php:42,190`, `resources/views/components/layouts/guest.blade.php:14`

### UX.16 Sub-12px text in the calculator

- **Problem:** Per-ingredient breakdown and the daily-target label use `text-[11px]` — below the practical readability floor, in the densest data area of the app.
- **Fix:** Bump to `text-xs` (12px); compensate with `tracking-tight` if width is a concern.
- **Reasoning:** Readability of nutrition numbers is the calculator's whole job.
- **Files:** `resources/views/livewire/portion-calculator.blade.php:100,253,268`

### UX.17 Replace wire:confirm with undo for unfavoriting

- **Problem:** Removing a favorite triggers a native browser `confirm()` dialog — heavyweight and jarring for a low-stakes, instantly reversible action.
- **Fix:** Remove `wire:confirm`; unfavorite immediately and show a transient inline "Removed — Undo" affordance (Livewire keeps the removed id for one render; Undo calls `favorite($id)` back). Also add `aria-label` to the icon-only heart button (currently `title` only).
- **Reasoning:** Confirmation dialogs are for destructive irreversible actions; undo is faster and friendlier for this one.
- **Files:** `resources/views/livewire/cabinet/favorites-list.blade.php:47-55`, `app/Livewire/Cabinet/FavoritesList.php`

---

## Phase 4 — Engagement polish (do after Phases 1-3; each independent)

### UX.18 Recipe detail is a dead end

- **Fix:** Add a "More from {category}" section at the bottom: 3 compact recipe cards (reuse `x-recipe-card` from UX.10) from the same category, excluding the current recipe, newest first. Eager-load in `RecipeDetail`.
- **Reasoning:** Cheapest session-length win; users finish a recipe with nowhere to go.
- **Files:** `app/Livewire/RecipeDetail.php`, `resources/views/livewire/recipe-detail.blade.php` (after gallery)

### UX.19 Tickable ingredients (cook mode lite)

- **Fix:** Alpine-only checkboxes on the main ingredient list: clicking a row toggles `line-through opacity-60`. No persistence, no server round-trip. Exclude from print.
- **Reasoning:** Very on-genre for cooking pages; ~10 lines of Alpine, zero backend.
- **Files:** `resources/views/livewire/recipe-detail.blade.php:115-135`

### UX.20 Gallery lightbox

- **Fix:** Replace `target="_blank"` raw-file links with a minimal `<dialog>`-based lightbox (full-size image, close on backdrop/Escape, prev/next optional).
- **Reasoning:** Leaving the page to view a photo breaks the cooking flow; native `<dialog>` keeps it dependency-free.
- **Files:** `resources/views/livewire/recipe-detail.blade.php:171-194`

### UX.21 Image dimensions to prevent layout shift

- **Fix:** Add `width`/`height` attributes (from media library conversion dimensions) to card images, hero, step photos, and gallery thumbs.
- **Reasoning:** Eliminates CLS on image-heavy pages; data is already available on the media model.
- **Files:** `resources/views/livewire/recipe-browser.blade.php:214-219`, `resources/views/livewire/recipe-detail.blade.php:12-16,157-164,184-190`, `resources/views/livewire/cabinet/favorites-list.blade.php:60-66`

### UX.22 Catalog grid/list view toggle

- **Fix:** Add a grid/list toggle next to the sort select (persist choice in localStorage via Alpine). List = current horizontal plate cards; grid = compact cards (variant from UX.10).
- **Reasoning:** Horizontal cards show only ~3 recipes per screen; some users scan, some browse. Depends on UX.10.
- **Files:** `resources/views/livewire/recipe-browser.blade.php:188-251`

### UX.23 Guest-visible favorite button

- **Fix:** Render the favorite button for guests; clicking redirects to `/login` with intended URL (or shows a small "log in to save" popover).
- **Reasoning:** Hidden functionality cannot convert; a visible locked state advertises the account benefit.
- **Files:** `resources/views/livewire/recipe-detail.blade.php:204-207`, `app/Livewire/FavoriteButton.php`

### UX.24 Footer navigation

- **Fix:** Add the three main nav links (Recipes, Book, Author) and the locale switcher to the footer.
- **Reasoning:** Recipe pages are long; ending at a bare copyright line forces a scroll back up.
- **Files:** `resources/views/components/layouts/app.blade.php:194-206`

### UX.25 Home stats strip orphan

- **Fix:** Change `sm:grid-cols-3` to `sm:grid-cols-4` (7 items: 4+3) or reorder so the strongest stat anchors the last row.
- **Reasoning:** 3+3+1 leaves a lone orphan cell; cosmetic.
- **Files:** `resources/views/welcome.blade.php:48`

### UX.26 Move home-page category query out of the template

- **Fix:** The sections-grid tile hrefs run a `Category::query()` inside `@php` in the Blade. Move to the route closure/controller (or a view composer) and pass `$categoryIdBySlug` to the view.
- **Reasoning:** Not user-visible, but it is the only DB query living in a template — a trap for the next edit. Bundle with any UX.25 touch of the same file.
- **Files:** `resources/views/welcome.blade.php:67-82`, `routes/web.php`

---

## Execution order and dependencies

1. **Phase 1** first — UX.1, UX.5, UX.6 are near-one-liners; UX.2 is markup-only; UX.4 and UX.3 are the two real tasks (UX.3 touches Livewire component logic, write tests first).
2. **UX.7 (component extraction decision) gates Phase 2** — do it before UX.8/UX.10 so they build on the new components. UX.9, UX.11, UX.12 are independent one-offs and can go anytime.
3. **Phase 3** is independent of Phase 2 except that UX.13's shared Alpine component should land before any Phase 4 work touches the same dropdowns.
4. **Phase 4** items are independent of each other; UX.22 depends on UX.10.

| Phase | Items | Estimated size |
|---|---|---|
| 1 | UX.1-UX.6 | 1-2 sessions |
| 2 | UX.7-UX.12 | 2-3 sessions (UX.7 dominates) |
| 3 | UX.13-UX.17 | 1-2 sessions (UX.13 dominates) |
| 4 | UX.18-UX.26 | pick-and-choose, ~0.5 session each |

Out of scope for this plan (noted during review, deliberately excluded): dark mode, keyboard shortcut for search focus, scroll-position restore on back navigation, toast/flash system. If wanted, add to `IDEAS.md`.

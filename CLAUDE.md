# Recipe Hub — Agent Briefing

Laravel 12 / PHP 8.5 recipe app with nutrition data and portion-size calculator. Solo project, MVP phase.
Full spec: `docs/spec.md`. Task checklist: `docs/plan.md` (read it to find the next task).

## Environment

- **Path:** `/home/sparf/recipe-hub` in WSL2 / Ubuntu 22.04. Do not move to `/mnt/c/`.
- **Sail from Windows host:** `wsl -d Ubuntu-22.04 -- bash -c "cd /home/sparf/recipe-hub && ./vendor/bin/sail <cmd>"`
- If already inside WSL, just use `./vendor/bin/sail` directly.

## Tech stack (locked)

Laravel 12, PHP 8.5, Livewire 3 + Alpine.js 3 + Tailwind CSS 4 + Flux UI, Filament 3 (EN-only), MySQL 8, Redis 7 + Horizon, MeiliSearch via Scout, Fortify + Sanctum (no social login), spatie/laravel-medialibrary, Sail, Laravel Forge VPS. i18n: EN + UK, UI strings only, locale via cookie (no DB column). Content is English-only. UTC everywhere. Ingredients seeded from USDA FoodData Central.

Do not change stack choices without updating `docs/spec.md` section 17.

## Commands

```bash
sail up -d / sail down / sail logs -f laravel.test
sail artisan / sail composer / sail npm / sail test / sail shell

# Quality gates (all must pass before task is done)
sail composer pint          # formatter
sail composer larastan      # static analysis (level 6)
sail test                   # Pest
```

## Conventions

- **Branches:** `feat/L<layer>.<num>-<slug>` or `fix/L<layer>.<num>-<slug>`
- **Commits:** prefix with task code, e.g. `L1.2: scaffold auth flows via Fortify`. Never commit/push without explicit user approval.
- **Done means:** CI green (Pint + Larastan + Pest), smoke-tested, no dangling TODOs, box ticked in `docs/plan.md`.
- **One task at a time.** Follow `docs/plan.md` sequentially. Out-of-scope ideas go to the plan or `IDEAS.md`, never silently added.

## Never do

- Commit/push without explicit approval.
- Modify files outside the repo (especially `C:\Users\sparf\Documents\`).
- Touch global git config. Identity is repo-local only.
- Install host-side PHP/Composer. Everything runs in Sail.
- Destructive git (`reset --hard`, `push --force`, `clean -fd`, branch delete) without instruction.
- Add `spatie/laravel-translatable` or per-locale content columns. Content is EN-only.
- Store non-UTC timestamps.

## Out of scope (MVP)

Comments/ratings, social login, PDF import, meal planner, shopping lists, mobile app, public API, per-user timezone, locales beyond EN/UK, final branding. See `docs/spec.md` section 17.2.

## Key paths

| Path | Purpose |
|---|---|
| `docs/spec.md` | Architecture, schema, decisions |
| `docs/plan.md` | Task checklist (tick boxes as completed) |
| `app/Services/Nutrition/` | NutritionCalculator, UnitConverter |
| `app/Filament/` | Admin panel resources (locale forced to `en`) |
| `database/seeders/data/` | USDA CSV, densities, allergen rules, aliases |

## Working with the user

Solo dev, Ukrainian-native, both EN/UK OK in chat. Prefers concise responses with ready-to-run code. No emoji in files. No `gh` CLI — GitHub ops are manual. When in doubt: check `docs/spec.md`, then `docs/plan.md`, then ask.

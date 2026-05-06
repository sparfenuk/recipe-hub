# Claude / AI Agent Briefing — Recipe Hub

This file is read first whenever Claude (or any AI coding agent) starts work in this repo. It gives the context, conventions, and constraints needed to make useful changes without re-deriving them every session.

If you are an AI agent: read this entire file before suggesting or making changes. Then read `docs/plan.md` to find the next task.

---

## What this project is

Recipe Hub is a Laravel 12 / PHP 8.5 web application for recipes, ingredients with full nutrition data, and a portion-size calculator that scales recipes to a user's daily calorie target. Solo project, MVP phase.

Full architecture: `docs/spec.md`. Task breakdown: `docs/plan.md`.

## Where the project lives

- **Filesystem:** `/home/sparf/recipe-hub` inside **WSL2 / Ubuntu 22.04** on a Windows host.
- **Windows access path:** `\\wsl$\Ubuntu-22.04\home\sparf\recipe-hub`.
- **Do not move it to `/mnt/c/...`.** I/O on the Windows-mounted filesystem is 5–10× slower and was deliberately avoided per spec §14.7.

## Critical execution rule for agents on Windows

Sail's wrapper (`./vendor/bin/sail`) refuses to run from Git Bash / MINGW. Every `sail` command **must** be invoked through WSL. From a Bash tool on the Windows host, the canonical pattern is:

```bash
wsl -d Ubuntu-22.04 -- bash -c "cd /home/sparf/recipe-hub && ./vendor/bin/sail <command>"
```

For multiple commands, chain inside the same WSL invocation — bash state does not persist between separate `wsl ...` calls.

If a session is already inside WSL (e.g. running on Linux), drop the `wsl -d ... -- bash -c` prefix and just `cd /home/sparf/recipe-hub`.

## Tech stack — locked decisions

| Layer | Choice |
|---|---|
| Backend | Laravel 12.x, PHP 8.5 |
| Frontend | Livewire 3 + Alpine.js 3 + Tailwind CSS 4 + Flux UI |
| Admin | Filament 3, **English-only UI and content** |
| DB | MySQL 8 |
| Cache / Queue | Redis 7 + Horizon |
| Search | MeiliSearch via Laravel Scout |
| Auth | Fortify + Sanctum (no social login at launch) |
| Media | spatie/laravel-medialibrary |
| Local dev | Laravel Sail (Docker Compose) |
| Hosting | Single VPS via Laravel Forge |
| i18n | EN default + UK secondary, **UI strings only** (recipe/ingredient content is EN-only). Locale via cookie, no DB column. |
| Time zones | UTC everywhere in v1; no per-user timezone yet. |
| Ingredients | Seeded from USDA FoodData Central (Foundation + SR Legacy). |

Do not propose changing any of these without a written reason. If a change is genuinely needed, update both `docs/spec.md` §17 (Decisions Locked) and the relevant section of the spec, in the same commit as the code change.

## Daily commands

```bash
# Stack
sail up -d                         # start
sail down                          # stop
sail logs -f laravel.test          # tail app logs

# Workflow
sail artisan <cmd>
sail composer <cmd>
sail npm <cmd>
sail test                          # Pest
sail shell                         # bash inside app container

# Quality gates (must all pass before a task is "done")
sail composer pint                 # formatter (after L0.2 lands)
sail composer larastan             # static analysis (after L0.2)
sail test                          # Pest tests
```

## Project conventions

### Branch naming
`feat/L<layer>.<num>-<slug>` — example: `feat/L1.2-auth-scaffolding`, `fix/L3.4-recipe-form-validation`.

### Commit messages
Prefix with the layer task code:

```
L1.2: scaffold auth flows via Fortify

- Register, login, logout, email verify, password reset
- Mailpit catches verification emails locally
- Pest feature tests for each flow
```

Never commit without the user's explicit "commit it" instruction. Stage and prepare, then ask.

### Definition of done for any task
- Code merged to `main`.
- CI green: Pint, Larastan (level 6), Pest.
- Manual smoke check in the running stack.
- No new TODOs without a tracking note.
- Box ticked in `docs/plan.md`, with a 1-2 line "completed" note.

### One task at a time
The plan in `docs/plan.md` is **strictly sequential** for solo work. Do not start L2.x while L1.x is incomplete unless the user explicitly authorizes it. If you spot a follow-up that's out of the current task's scope, add it to `docs/plan.md` under a layer (or note it in `IDEAS.md` if one exists). Never silently expand a task.

### Out-of-scope reminders (do NOT add these in MVP)
- Comments, ratings, social features.
- Social login.
- PDF recipe import.
- Meal planner, shopping lists.
- Mobile app, expanded public API.
- Per-user timezone UI.
- Locales beyond EN / UK.
- Final branding (logo, custom palette beyond Tailwind defaults).

If a feature request matches any of these, point at the relevant section of `docs/spec.md` §17.2 ("Deferred to v1.1+") rather than implementing.

## Things to never do

- Commit or push without explicit user approval.
- Modify or delete files outside the repo without an explicit request (especially `C:\Users\sparf\Documents\` — those originals are kept as backups).
- Touch the host's global `~/.gitconfig` or WSL's global git config — git identity is set repo-locally only.
- Install host-side PHP or Composer. Everything runs inside Sail containers.
- Run destructive git commands (`reset --hard`, `push --force`, `clean -fd`, branch deletion) without explicit instruction.
- Skip the locale resolution order. Locale comes from cookie only — there is no `users.locale` column.
- Add `spatie/laravel-translatable` or any per-locale JSON content columns. Content is English-only by design.
- Store non-UTC timestamps. App, MySQL container, and server are all UTC.

## Useful repo locations

| Path | What it is |
|---|---|
| `docs/spec.md` | Architecture, schema, decisions, requirements. Reference. |
| `docs/plan.md` | Living checklist of tasks by layer. Check boxes as you complete tasks. |
| `compose.yaml` | Sail's Docker Compose definition. PHP 8.5 image, MySQL 8.4, Redis, MeiliSearch, Mailpit. |
| `.env` | Local env (gitignored). Mirrors `.env.example`. UTC, MySQL, MeiliSearch wired. |
| `.env.example` | Canonical env template. Update both when adding new env vars. |
| `app/Services/Nutrition/` | Will hold `NutritionCalculator`, `UnitConverter` (created in L2.1, L3.2). |
| `app/Filament/` | Filament admin panel resources. Locale forced to `en`. |
| `app/Livewire/` | Livewire components. |
| `lang/en/` and `lang/uk/` | Translation files (created in L1.4). |
| `database/seeders/data/` | Static data files: USDA curated CSV, densities, allergen rules, aliases. |

## Working with the user

- Solo developer, English-speaking but Ukrainian-native — both languages OK in chat.
- Prefers explicit recommendations over open menus, but expects justification.
- Wants concise, structured responses with code/commands ready to run.
- Does not want emoji in files unless explicitly requested.
- Pushes back on assumptions (good); double-check before assuming a default.
- Does not have `gh` CLI — GitHub operations are manual on their side.

## When in doubt

1. Read the relevant section of `docs/spec.md` first.
2. Check `docs/plan.md` for which task is current and what's already done.
3. If the answer isn't in the docs, ask the user before guessing.
4. Update the spec / plan in the same change that lands the code, so the docs never drift behind reality.

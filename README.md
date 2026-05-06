# Recipe Hub

A Laravel-based web application for browsing recipes, managing ingredients with full nutritional data, and computing portion sizes against personal calorie targets. Includes a user cabinet, public recipe catalog, ingredient bank, and a Filament admin panel.

> **Status:** Bootstrap phase. Layer 0 task L0.1 complete. See [`docs/plan.md`](docs/plan.md) for the full task breakdown.

## What it does (MVP scope)

- **Recipe bank** — searchable, filterable catalog with photos, step-by-step instructions, and per-serving / per-100 g nutrition.
- **Ingredient catalog** — ~600 cooking-relevant ingredients seeded from USDA FoodData Central (Foundation + SR Legacy), with kcal, protein, fat, saturated fat, carbs, sugar, fiber, sodium per 100 g, plus densities, allergens, and aliases.
- **Portion calculator** — scale any recipe by target servings, total kcal, or % of the user's daily calorie target. Returns adjusted ingredient amounts and updated nutrition totals.
- **User cabinet** — profile, health metrics (BMR via Mifflin-St Jeor), macro targets, favorites, and saved calculations.
- **Admin panel** — Filament 3-based, English-only UI for managing all reference data.

Full requirements and architecture: [`docs/spec.md`](docs/spec.md).

## Tech stack

| Layer | Choice |
|---|---|
| Backend | Laravel 12, PHP 8.5 |
| Frontend | Livewire 3 + Alpine.js + Tailwind CSS 3 + Flux UI |
| Admin | Filament 3 (English-only) |
| Database | MySQL 8 |
| Cache / Queues | Redis 7 + Laravel Horizon |
| Search | MeiliSearch via Laravel Scout |
| Media | spatie/laravel-medialibrary + intervention/image |
| Auth | Laravel Fortify + Sanctum |
| Testing | Pest 3 + Laravel Dusk |
| Local dev | Laravel Sail (Docker Compose) |
| Hosting | Single VPS via Laravel Forge |
| i18n | English (default) + Ukrainian (UI strings only) |
| Time zones | UTC everywhere |

## Local development

**Prerequisite:** WSL2 with a Linux distribution (Ubuntu 22.04 recommended) and Docker Desktop with WSL integration enabled. The project lives on the WSL ext4 filesystem at `/home/<you>/recipe-hub` for fast file I/O — see [`docs/spec.md` §14](docs/spec.md) for rationale.

### First-time setup (already completed for current developer)

```bash
# Inside WSL
git clone git@github.com:<owner>/recipe-hub.git ~/recipe-hub
cd ~/recipe-hub
cp .env.example .env

# Bootstrap Composer deps via a throwaway container (no host PHP needed)
docker run --rm -v "$(pwd):/app" -w /app composer:latest install --ignore-platform-reqs

# Bring up the stack
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

App: http://localhost · Mailpit UI: http://localhost:8025 · MeiliSearch: http://localhost:7700

### Day-to-day

```bash
./vendor/bin/sail up -d            # start all services
./vendor/bin/sail down             # stop all services
./vendor/bin/sail artisan <cmd>    # any artisan command
./vendor/bin/sail composer <cmd>   # composer
./vendor/bin/sail npm <cmd>        # npm
./vendor/bin/sail test             # run tests (Pest)
./vendor/bin/sail shell            # bash inside the app container
./vendor/bin/sail logs -f laravel.test
```

Add this alias to `~/.bashrc` (inside WSL) for ergonomics:

```bash
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
```

Then it's just `sail up -d`, `sail artisan ...`, etc.

### Service ports

| Service | Host port | Purpose |
|---|---|---|
| App (nginx + PHP-FPM 8.5) | 80 | Web |
| Vite dev server | 5173 | HMR |
| MySQL 8.4 | 3306 | DB |
| Redis 7 | 6379 | Cache, sessions, queues |
| MeiliSearch | 7700 | Search index |
| Mailpit SMTP | 1025 | Outbound email catcher |
| Mailpit UI | 8025 | Email inspection |

If port 80 is in use on Windows, set `APP_PORT=8080` in `.env` before `sail up`.

## Project documentation

- [`docs/spec.md`](docs/spec.md) — full technical specification (architecture, database schema, API surface, non-functional requirements, decisions log).
- [`docs/plan.md`](docs/plan.md) — sequential task breakdown by layer, with checkboxes. The single source of truth for "what's done / what's next."

The two markdown files are the canonical living docs and are version-controlled with the code. They were originally drafted in `C:\Users\sparf\Documents\` but those copies are now historical backups only.

## Working rhythm

This is a solo project. Tasks run **sequentially** layer-by-layer per [`docs/plan.md`](docs/plan.md):

1. Pick the next unticked task.
2. Branch: `feat/L<layer>.<num>-<slug>` (e.g. `feat/L1.2-auth-scaffolding`).
3. Build. Test. Smoke-check the new behavior in the running stack.
4. PR (or just merge to `main` if working solo) once CI is green.
5. Tick the box in `docs/plan.md`, commit the tick, move to the next task.

Definition of done for each task:

- [ ] Code merged to `main`.
- [ ] CI green: Pint, Larastan, Pest.
- [ ] Manual smoke check passes.
- [ ] No new TODOs without a tracking note.

## Out-of-scope for v1 (parked, not forgotten)

Listed in `docs/plan.md` "Things to deliberately NOT do during MVP" and `docs/spec.md` §17.2:

- Comments / ratings.
- Social login (Google / Facebook via Socialite) — wiring exists, defer to v1.1.
- PDF recipe import.
- Meal planner, shopping list generator.
- Mobile app, public API beyond minimal v1.
- Final branding pass (logo, palette, custom domain).
- Per-user timezone preference.
- Locales beyond EN/UK.

## License

Proprietary. All rights reserved.

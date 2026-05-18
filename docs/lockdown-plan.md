# Lockdown Plan — закрити сайт перед листом автору книги

**Створено:** 2026-05-17
**Контекст:** проект містить контент із купленої кулінарної книги. Перед пропозицією автору купити проект потрібно зняти публічний доступ, прибрати з пошукових індексів і підготувати приватне demo з акаунтом для автора. Деталі юридичного аргументу — у чаті 2026-05-17, тут лише план дій.

**Принцип:** один env-флаг (`APP_PRIVATE=true`) на проді закриває все; локальна розробка не страждає.

---

## Поточний стан коду (станом на 2026-05-17)

- Публічні маршрути в `routes/web.php`:
  - `/`, `/book`, `/author`, `/recipes`, `/recipes/{slug}`, `/recipes/{slug}/pdf`, `/sitemap.xml` — відкриті
  - `/cabinet/*` — `auth + verified`
- `public/robots.txt` — зараз **дозволяє** `/` і `/recipes` (треба інвертувати)
- `app/Http/Controllers/SitemapController.php` віддає всі `published` рецепти
- `config/fortify.php:147` — `Features::registration()` увімкнено
- `resources/views/components/layouts/app.blade.php` — немає `<meta name="robots">`; кнопки Register на рядках 102–104 і 173–175
- `bootstrap/app.php` — `web` middleware-група містить лише `SetLocale`

---

## Хвиля 1 — припинити індексацію (зробити перш за все, ~15 хв)

- [x] **1.1** Перезаписати `public/robots.txt`:
  ```
  User-agent: *
  Disallow: /
  ```
- [x] **1.2** Додати у `<head>` обох layout (`resources/views/components/layouts/app.blade.php`, `resources/views/components/layouts/guest.blade.php`):
  ```html
  <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
  ```
  ~~Якщо PDF-template має HTML-обгортку — і туди.~~ — PDF це бінарник, meta не діє; покривається `X-Robots-Tag` на nginx (1.4).
- [x] **1.3** Прибрати маршрут sitemap у `routes/web.php` (рядок 21) АБО зробити так, щоб `SitemapController` повертав 404. Видалити простіше. (видалено маршрут, `SitemapController.php`, `resources/views/sitemap.blade.php`; `tests/Feature/SeoTest.php` оновлено під lockdown.)
- [ ] **1.4** На Forge → site → Nginx Configuration, всередині `server { ... }` додати:
  ```nginx
  add_header X-Robots-Tag "noindex, nofollow, noarchive, nosnippet" always;
  ```
  Reload nginx. Це покриває і PDF, і медіа-файли, і будь-що, що віддає сервер.
- [ ] **1.5** Commit + deploy одразу — без цього все інше не має сенсу.

---

## Хвиля 2 — закрити доступ (~30–60 хв)

Двошарово: nginx Basic Auth як аварійний барʼєр зараз + app-level Private Mode як остаточне рішення.

### 2a. Аварійний Basic Auth (5 хв, тимчасово)

- [ ] **2a.1** На сервері:
  ```bash
  sudo apt-get install apache2-utils -y
  sudo htpasswd -c /etc/nginx/.htpasswd-recipehub demo
  ```
- [ ] **2a.2** У Nginx Configuration, всередині `server { ... }`:
  ```nginx
  auth_basic "Private";
  auth_basic_user_file /etc/nginx/.htpasswd-recipehub;
  location = /up { auth_basic off; }   # Forge health-check
  ```
- [ ] **2a.3** Reload nginx, перевірити з іншого браузера/інкогніто.

### 2b. App-level Private Mode (основне рішення)

- [x] **2b.1** Додати в `config/app.php`:
  ```php
  'private' => env('APP_PRIVATE', false),
  ```
- [x] **2b.2** Створити `app/Http/Middleware/EnsurePrivateAccess.php`. Логіка:
  - якщо `! config('app.private')` → `$next($request)`
  - якщо `Auth::check()` → `$next($request)`
  - якщо шлях у allowlist → `$next($request)`. Allowlist (фактично):
    - `login`, `logout`
    - `forgot-password`, `reset-password`, `reset-password/*`
    - `two-factor-challenge`, `user/two-factor-*`
    - `email/verify`, `email/verify/*`, `email/verification-notification`
    - `up` (health-check)
    - `admin`, `admin/*` (Filament має власний auth-стек)
  - інакше → `redirect()->guest(route('login'))`
  - Login-форма виявилась plain HTML, не Livewire — `/livewire/*` не потрібно в allowlist; для auth-юзерів Livewire-запити проходять через гілку `Auth::check()`.
- [x] **2b.3** У `bootstrap/app.php`:
  ```php
  $middleware->web(append: [SetLocale::class, EnsurePrivateAccess::class]);
  ```
- [ ] **2b.4** На Forge → site → Environment: `APP_PRIVATE=true`. Локально в `.env` не задавати або поставити `false`.
- [~] **2b.5** ~~`config/fortify.php:147` — закоментувати `Features::registration()`.~~ Відмінено: маршрут `/register` лишається зареєстрованим, але EnsurePrivateAccess не містить його в allowlist — на проді з `APP_PRIVATE=true` він редіректить на `/login`. Це зберігає покриття тестами скеффолдингу (welcome email, авто-профіль, role assign).
- [x] **2b.6** З `resources/views/components/layouts/app.blade.php` прибрати кнопки Register (рядки ~102–104 і ~173–175). Також прибрано посилання «Don't have an account? Register» з `resources/views/auth/login.blade.php`.
- [ ] **2b.7** Deploy, потім `php artisan config:cache` на проді (Forge зробить автоматично, якщо в deploy script).
- [ ] **2b.8** Тест з інкогніто: `/`, `/recipes`, `/recipes/<slug>`, `/recipes/<slug>/pdf` → всі редіректять на `/login`. `/up` → 200 OK.

### 2c. Прибрати Basic Auth після того, як 2b працює

- [ ] **2c.1** З Nginx Configuration видалити `auth_basic` і `auth_basic_user_file`. Reload nginx.
- [ ] **2c.2** Перевірити, що сайт усе ще закритий через Private Mode.

---

## Хвиля 3 — підготувати demo для автора (перед листом)

- [ ] **3.1** Створити акаунт автора (один раз на проді):
  ```bash
  sail artisan tinker
  >>> App\Models\User::create([
  ...   'name' => '<AuthorName>',
  ...   'email' => '<author-email-or-placeholder>',
  ...   'password' => bcrypt('<strong-random-pass>'),
  ...   'email_verified_at' => now(),
  ... ]);
  ```
  Зберегти креди в Bitwarden/1Password.
- [x] **3.2** Банер у `layouts/app.blade.php` під `<header>`. Реалізовано: показується лише коли `config('app.private') && Auth::check()`; рядок «Private preview prepared for :name. Not for public distribution.» з перекладом EN/UK у `lang/*.json`; тести в `PrivateModeTest`.
- [x] **3.3** ~~Перевірити, що `/recipes/{slug}/pdf` теж за логіном~~ — покрито `EnsurePrivateAccess` і тестом «private mode on — recipe PDF redirects guests». На проді ще раз руками.
- [x] **3.4** ~~Перевірити, що Filament admin (`/admin`) працює лише для тебе~~ — покрито тестами в `RolesAndPermissionsTest` («admin can access filament admin panel» / «regular user cannot access filament admin panel»). На проді ще раз руками.
- [ ] **3.5** Зробити 30–60 сек screen recording walkthrough — щоб вкласти в лист поряд із приватним посиланням.

---

## Хвиля 4 — підчистити сліди в індексі (паралельно з Хвилею 2)

- [ ] **4.1** Google Search Console → Removals → New Request → Temporarily remove all URLs with this prefix → твій домен.
- [ ] **4.2** Bing Webmaster Tools → Block URLs → той самий префікс.
- [ ] **4.3** Зробити скрін `site:yourdomain.com` зараз; повторити через 48 год — для контролю прогресу.
- [ ] **4.4** Для рецептів з впізнаваним текстом автора — окремі запити «Remove outdated content» в GSC.
- [ ] **4.5** Перевірити `web.archive.org/web/*/yourdomain.com/*`. Якщо є снапшоти — написати на `info@archive.org` із проханням exclude.
- [ ] **4.6** Пройтись по соцмережах/каналах, де ти сам публікував посилання. Видалити або відредагувати.

---

## Чек-ліст «готово до листа автору»

- [ ] `site:yourdomain.com` у Google показує 0 результатів (або тільки головну)
- [ ] `curl -I https://yourdomain.com` → header `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet`
- [ ] Анонімний `/recipes` редіректить на `/login`
- [ ] `/recipes/{slug}/pdf` теж за логіном
- [ ] `/register` недоступний
- [ ] Логін акаунтом автора з іншого браузера працює
- [ ] Банер «Private preview» показується після логіну
- [ ] На проді `APP_PRIVATE=true`, config cached
- [ ] Записаний короткий walkthrough для листа

---

## З чого почати завтра

1. Прочитати «Поточний стан коду» — переконатись, що нічого з тих пір не змінилось.
2. Хвиля 1 цілком — це 15 хв і дає головну користь. Без цього решта не критична.
3. Поки Хвиля 1 деплоїться — паралельно почати **4.1** (GSC removal), щоб таймер пішов.
4. Далі — 2a (5 хв Basic Auth як страховка), потім 2b (Private Mode middleware).
5. Хвиля 3 — лише коли все інше зроблено і протестовано.

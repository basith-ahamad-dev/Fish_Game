# DeepCatch — Fish Game Platform

A competitive gaming platform built around Marc Conrad's [Fish Game API](https://marcconrad.com/uob/fish/), written in plain PHP 8 with an MVC structure and MySQL. It uses no frameworks.

The external API is the game engine: it supplies the aquarium pictures and their solutions. DeepCatch adds the player ecosystem around it:

- accounts and remember-me login
- difficulty modes, server-side scoring, XP and levels
- leaderboards, achievements, history and statistics
- player profiles, settings and an admin panel

---

## 1. The Fish Game API

These are the facts the integration relies on, taken from the API's [doc.php](https://marcconrad.com/uob/fish/doc.php):

| | |
|---|---|
| Endpoint | `GET https://marcconrad.com/uob/fish/api.php` |
| Parameters | `out=json\|csv` (default `json`), `base64=yes\|no` (default `no`) |
| JSON response | `{ "question": "<image URL or base64 PNG>", "fish": <int>, "chest": <int> }` |
| Auth | none |
| CORS | `Access-Control-Allow-Origin: *` |
| Content-Type | `text/html`, even for JSON |
| Game rule | count the fish in the picture; treasure chests don't count |

The API has **no difficulty, scoring, sessions or game results**. It returns one random puzzle per call, together with its solution.

**Security consequence.** The solution comes back in the same response as the picture. The image URL encodes it too: in `…_004x008.png`, 4 is the chest count and 8 the fish count. If the browser called the API directly, any player could read the answers. So:

- `App\Services\FishGameService` is the **only** code that talks to the API. It requests `base64=yes` and validates that the payload is a real PNG.
- The browser receives only a `data:` image. The solution stays in the database (`game_rounds`) until the round is answered.
- Timing, scoring, XP and badges are all decided on the server. The browser only ever sends a guess.

---

## 2. Requirements

- PHP **8.1+** with `pdo_mysql`, `curl` (or `allow_url_fopen`), `gd`, `fileinfo` and `mbstring`
- MySQL **5.7+** or MariaDB **10.2+**
- Apache with `mod_rewrite`. Any host that can route requests to `public/index.php` also works.

---

## 3. Installation

### Local (XAMPP / WAMP / MAMP)

1. Copy the project folder into your web root, e.g. `C:\xampp\htdocs\fish-game`.
2. Create a database:
   ```sql
   CREATE DATABASE fish_game CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the SQL files **in this order** (phpMyAdmin → Import, or the CLI):
   ```bash
   mysql -u root fish_game < database/schema.sql      # tables
   mysql -u root fish_game < database/seeders.sql     # badges + admin account (required)
   mysql -u root fish_game < database/demo_data.sql   # optional: 14 demo players, ~500 games
   ```
4. Copy `.env.example` to `.env` and set your database credentials.
5. Open `http://localhost/fish-game/`. The root `.htaccess` forwards every request into `public/`.

Alternatively, run PHP's built-in server:
```bash
php -S localhost:8000 -t public public/index.php
```

### Shared hosting / cPanel

1. Upload the whole project.
   - **Best:** point the domain's document root at the `public/` folder.
   - **If you can't:** upload into `public_html/`. The root `.htaccess` sends requests into `public/` and blocks direct access to `.env`, `.sql`, `.md` and `.log` files. `storage/logs/` has its own deny rule. `app/`, `config/` and `database/` aren't served because every request is routed through `public/`.
2. Create the database and user in cPanel, then import the SQL files as above. The schema doesn't run `CREATE DATABASE`, so it imports into whichever database you select.
3. Create `.env` with the database credentials cPanel gave you. Set `APP_URL` only if the site lives in a sub-folder and URLs come out wrong.
4. Make `storage/logs/` and `public/uploads/avatars/` writable by PHP.

### Accounts

| Account | Username | Password |
|---|---|---|
| Admin (from `seeders.sql`) | `admin` | `Admin@12345` — **change this immediately** |
| Demo players (from `demo_data.sql`) | e.g. `Basith`, `CoralQueen`, `ReelDeal` | `Player@123` |

---

## 4. How the game works

1. The player picks a difficulty. `POST /game/start` creates a `games` row. Any unfinished game is closed as *abandoned*.
2. `POST /game/round` fetches a puzzle through `FishGameService`. It stores the solution and a server timestamp, then returns only the image.
3. The player types or taps a count. `POST /game/answer` checks it against the stored solution, using the server clock for the time limit plus a 2-second network allowance.
4. A wrong answer or a timeout costs a life. The game ends when the player runs out of lives (**lost**) or has played every round (**won**).
5. On completion the server applies score, XP, level, streaks and global rank, and unlocks badges, all in one transaction. `POST /game/result` and `GET /game/result/{id}` return the stored summary.

**Anti-cheat details:**
- Requesting a new picture while one is still open forfeits the open one, so refreshing can't re-roll a hard picture.
- Answers are validated as integers between 0 and 99.
- Game rows are locked (`SELECT … FOR UPDATE`) so double submissions can't be counted twice.
- Players can only access their own games.

### Difficulty (server-side layer)

| | Easy ★ | Medium ★★ | Hard ★★★ |
|---|---|---|---|
| Pictures | 5 | 8 | 10 |
| Time per picture | 30 s | 20 s | 12 s |
| Lives | 3 | 2 | 1 |
| Score multiplier | 1.0× | 1.5× | 2.0× |
| XP multiplier | 1.0× | 1.5× | 2.0× |

Everything above is configured in [config/game.php](config/game.php).

### Scoring and XP

- **Correct answer:** `(100 + 50 × share of time left) × score multiplier`
- **Perfect game** (every picture correct): `+200 × score multiplier`
- **XP:** `(10 per correct + 20 for finishing + 30 for a win + 25 for perfect) × XP multiplier`. Abandoned games keep only the per-correct XP.
- **Levels:** completing level *L* needs a cumulative `25 · L · (L + 3)` XP. That gives Level 1 → 100, Level 2 → 250, Level 3 → 450, Level 4 → 700, and so on, with each level needing 50 XP more than the one before. See [app/Services/LevelService.php](app/Services/LevelService.php).

### Leaderboards

The boards are Global (all-time total score), Weekly (from Monday), Monthly, and one each for Easy, Medium and Hard.

- Ties share a rank (1, 2, 2, 4).
- The signed-in player is highlighted. If they're outside the visible top, they're appended under the table.
- Disabled players are excluded.

### Badges

| Badge | Requirement |
|---|---|
| First Catch | 1 game completed |
| Beginner Angler | 10 games completed |
| Sharp Angler | best score ≥ 1,000 |
| Hot Streak | 5 wins in a row |
| Champion | global top 10 |
| Elite Angler | level 10 |
| Speed Fisher | win a Hard game |
| Ocean Master | 100 games completed |

Admins can add badges using these requirement types:
- games played
- best score
- win streak
- global rank
- level
- Hard wins
- perfect games
- fish caught
- total score

A unique key on `user_badges` prevents duplicate awards.

---

## 5. Architecture

```
app/
  Core/          Router, Request, Response, Session (+CSRF, flash), Validator, Database (PDO),
                 base Model & Controller, View (layouts, components, asset stacks), Logger, Env, helpers
  Controllers/   Thin: read the request, call a service, return a view or JSON
  Models/        All SQL lives here (prepared statements only)
  Services/      Business logic: FishGameService, GameService, XPService, LevelService,
                 BadgeService, LeaderboardService, StatisticsService, AuthService,
                 AccountService, AvatarService
  Middleware/    AuthMiddleware, GuestMiddleware, AdminMiddleware, CsrfMiddleware (all POSTs)
  Views/         layouts/ (main, auth, dashboard), components/ (navbar, sidebar, cards,
                 avatar, progress bar, badge, leaderboard row, modal, dropdown, toast, …), pages
config/          app.php, database.php, game.php
routes/          web.php (pages), api.php (JSON endpoints used by fetch)
database/        schema.sql, seeders.sql, demo_data.sql
public/          index.php (front controller), .htaccess, assets/{css,js}, uploads/avatars
storage/logs/    application and PHP error logs
```

Request flow: `public/index.php` → `Router` → middleware → controller → service → model → view or JSON.

### Routes

| Method | Path | Purpose |
|---|---|---|
| GET | `/` | Landing page (live stats via `GET /api/stats`) |
| GET/POST | `/login`, `/register` | Guests only |
| POST | `/logout` | |
| GET | `/dashboard`, `/play` (alias `/game`), `/game/result/{id}` | |
| POST | `/game/start`, `/game/round`, `/game/answer`, `/game/abandon`, `/game/result` | JSON |
| GET | `/leaderboard?tab=…` (+ `GET /api/leaderboard`), `/players/{username}` | Public |
| GET | `/profile`, `/achievements`, `/history`, `/statistics` (+ `/api/statistics`) | |
| GET/POST | `/settings` | Sections: profile, security, preferences |
| GET | `/admin`, `/admin/players[/{id}]`, `/admin/games`, `/admin/badges`, `/admin/leaderboard` | Admin only |
| POST | `/admin/players/{id}/status`, `/admin/players/{id}/role`, `/admin/games/{id}/delete`, `/admin/badges`, `/admin/badges/{id}/delete`, `/admin/leaderboard/recalculate` | Admin only |

---

## 6. Security

- **Passwords:** hashed with `password_hash()` and rehashed automatically when the algorithm changes. Registration requires 8+ characters with upper- and lowercase letters and a number.
- **Logins:** unknown usernames take the same time to reject as wrong passwords. Rate limiting allows 5 failures per username + IP and 25 per IP within 15 minutes.
- **Sessions:** strict mode, `HttpOnly` and `SameSite=Lax` cookies, `Secure` on HTTPS. Session IDs are regenerated at login and every 15 minutes, with a 2-hour idle timeout.
- **Remember me:** selector/validator tokens. Only a SHA-256 hash is stored, tokens rotate on every use, and they're revoked when the password changes.
- **CSRF:** a token on every POST, either a form field or the `X-CSRF-Token` header for fetch calls.
- **SQL injection:** PDO prepared statements with native prepares. Filter values are whitelisted.
- **XSS:** all output is escaped with `e()`, and JSON embedded in HTML is hex-escaped.
- **Access control:** auth and admin middleware. Admins can't disable or demote their own account. Disabled accounts are signed out on their next request.
- **Avatars:** checked with finfo and getimagesize, size-limited, then re-encoded by GD to a 256×256 PNG with a random name. PHP execution is disabled in the uploads folder.
- **Errors:** raw errors never reach users. Details are logged to `storage/logs/` and visitors see a friendly error page. Set `APP_DEBUG=true` only on development machines.
- **Headers:** `X-Content-Type-Options`, `X-Frame-Options` and `Referrer-Policy` are sent on every response.

---

## 7. Front end

- Vanilla JS with `fetch()`, inline SVG icons and CSS animations. No frameworks or build step.
- Two themes, selectable in Settings: *Deep Ocean* (dark) and *Shallow Reef* (light).
- Sound effects are synthesised with WebAudio and can be turned off.
- Animations can be turned off in Settings, and the OS `prefers-reduced-motion` setting is honoured.
- Layout: a sidebar on desktop; an off-canvas drawer plus a bottom navigation bar at 1024px and below. Tested at 1440, 1024, 768, 480, 390 and 375px.
- The game supports keyboard input (type digits, then <kbd>Enter</kbd>) and an on-screen keypad for touch.
- Loading, error and empty states throughout. If the Fish API is unreachable, the game shows "Unable to connect to the game server" with a **Retry** button, and the game state is kept.

---

## 8. Testing performed

The end-to-end script ran against PHP 8.2 and MariaDB 10.4 using the **live** Fish API. 60 of 60 checks passed:

- **Authentication:** registration; duplicate email or username; weak or mismatched passwords; login by username and by email; wrong password; logout; rate limiting; remember-me restore; guest/auth redirects.
- **Game:**
  - invalid difficulty rejected
  - the solution never appears in the round payload
  - correct and wrong answers
  - timeouts
  - finishing as won or lost
  - double-answer rejection
  - re-roll forfeits the open picture
  - abandoning on new start and on quit
- **Progression:** XP and level, personal best, streak updates and resets, user totals, admin recalculation that reproduces the stored totals.
- **Competition:** all six leaderboard tabs and their tie ranks.
- **Achievements:** First Catch unlocks exactly once, and admin-created badges unlock automatically.
- **Security:**
  - CSRF on forms and JSON
  - SQL injection attempts in filters and URLs
  - stored XSS in the bio escaped
  - other players' games return 404
  - players get 403 on admin pages
  - a fake image upload is rejected
- **Responsive and visual:** headless-browser screenshots at 1440px and 390px for every page, in both themes, checked for horizontal overflow and JavaScript errors.

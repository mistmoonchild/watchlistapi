# Movie Watchlist API

A RESTful API for managing a personal movie watchlist, built with Laravel. Users register, authenticate via token-based auth, and maintain a private list of movies they want to watch or have already watched — complete with personal ratings and notes. Movie metadata is pulled automatically from the [OMDb API](https://www.omdbapi.com/) using IMDb IDs and cached locally.

## Features

- Token-based authentication (register / login / logout) via [Laravel Sanctum](https://laravel.com/docs/sanctum).
- Per-user watchlist — each user only sees and manages their own items.
- Automatic movie enrichment: movies are fetched from OMDb on first reference and stored locally, so repeat lookups don't hit the external API.
- Filtering by status (`to_watch` / `watching` / `watched`) and paginated listings.
- Personal `rating` (1–5) and `personal_notes` per watchlist item.
- Duplicate protection — the same movie can't be added to a user's watchlist twice.

## Tech Stack

- **PHP** ^8.3
- **Laravel** ^13.8
- **Laravel Sanctum** ^4.0 — API token authentication
- **SQLite** (default) — easily swappable for MySQL/PostgreSQL via `.env`
- **Pest** ^4.7 — testing
- **OMDb API** — external movie data source

## Requirements

- PHP 8.3+
- Composer
- An [OMDb API key](https://www.omdbapi.com/apikey.aspx) (free tier available)
- Node.js & npm (only needed for front-end asset building; not required for the API itself)

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd watchlistapi

# Install PHP dependencies
composer install

# Create your environment file
cp .env.example .env

# Generate the application key
php artisan key:generate

# Create the SQLite database file (if it doesn't exist) and als if you don't want to use PostgreSQL or MySQL
touch database/database.sqlite

# Run the migrations
php artisan migrate
```

### Environment Configuration

Set the following in your `.env` file:

```dotenv
DB_CONNECTION=sqlite

# OMDb API
OMDB_BASE_URL=https://www.omdbapi.com/
OMDB_API_KEY=your_omdb_api_key_here
```

For me and for the purpose of this project

```dotenv
OMDB_BASE_URL=https://www.omdbapi.com/
OMDB_API_KEY=36c78802
```

#### Database options

SQLite is the default and requires no extra configuration. To use **MySQL/MariaDB** or **PostgreSQL** instead, uncomment the relevant block in `.env` and set your credentials.

**MySQL / MariaDB:**

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

**PostgreSQL** (requires the `pdo_pgsql` PHP extension):

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=
```

After changing the database connection, run `php artisan migrate`.

## Running the Application

```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api` (or your configured `APP_URL`).

## API Endpoints

All endpoints are prefixed with `/api`. Protected routes require an
`Authorization: Bearer <token>` header, obtained from register or login.

### Authentication

| Method | Endpoint    | Auth | Description                          |
|--------|-------------|------|--------------------------------------|
| POST   | `/register` | No   | Register a new user, returns a token |
| POST   | `/login`    | No   | Log in, returns a token              |
| POST   | `/logout`   | Yes  | Revoke the current access token      |

**Register / Login request body:**

```json
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123"
}
```

(`name` is required for register only; `min:8` characters for password.)

**Response:**

```json
{
  "message": "Login successful",
  "access_token": "1|abcdef...",
  "token_type": "Bearer"
}
```

### Watchlist (protected)

| Method | Endpoint                      | Description                                       |
|--------|-------------------------------|---------------------------------------------------|
| GET    | `/watchlist`                  | List the current user's watchlist (paginated, 10/page) |
| GET    | `/watchlist?status=to_watch`  | List filtered by status (`to_watch`, `watching`, or `watched`) |
| POST   | `/watchlist`                  | Add a movie to the watchlist                      |
| GET    | `/watchlist/{id}`             | Show a single watchlist item                      |
| PUT    | `/watchlist/{id}`             | Update status, rating, or notes                   |
| DELETE | `/watchlist/{id}`             | Remove an item from the watchlist                 |

**Add a movie (`POST /watchlist`):**

```json
{
  "omdb_id": "tt0133093",
  "status": "to_watch",
  "rating": 5,
  "personal_notes": "A classic, must rewatch!"
}
```

- `omdb_id` — required IMDb ID (e.g. `tt0133093`). The movie is fetched from OMDb if not already stored.
- `status` — optional, one of `to_watch` (default), `watching`, or `watched`.
- `rating` — optional integer, 1–5.
- `personal_notes` — optional, max 1000 characters.

**Item response shape:**

```json
{
  "data": {
    "id": 1,
    "status": "to_watch",
    "rating": 5,
    "personal_notes": "A classic, must rewatch!",
    "added_at": "2026-06-16 14:40:00",
    "movie": {
      "imdb_id": "tt0133093",
      "title": "The Matrix",
      "description": "...",
      "poster_url": "https://...",
      "release_year": "1999"
    }
  }
}
```

A ready-to-use request collection is available in [`api-endpoints.http`](./api-endpoints.http).

## Data Model

- **User** — owns watchlist items.
- **Movie** — locally cached movie record (`external_id` = IMDb ID, `title`, `description`, `poster_url`, `release_year`).
- **WatchlistItem** — links a user to a movie with `status`, `rating`, and notes. A unique constraint on `(user_id, movie_id)` prevents duplicates.

## Authentication

This API uses **Laravel Sanctum** with bearer tokens.

**Why Sanctum over the alternatives:**

- **vs. other auth** — Sanctum is out-of-the-box Laravel easy to setup api authentification with with plain Bearer token

The flow: `register`/`login` return a plain-text token, the client sends it as `Authorization: Bearer <token>`, and `logout` deletes the current token. All watchlist routes sit behind the `auth:sanctum` middleware and are scoped to `$request->user()`.

## Decisions & Trade-offs

**What I focused on**

- **Clean separation of concerns.** OMDb access lives in a dedicated `MovieDatabaseService`, validation in Form Requests, output shaping in an API Resource, and orchestration in a thin controller. A teammate can change the external provider or the response shape without touching the others.
- **Movies cached locally.** When a movie is added, it's fetched from OMDb once and stored in a `movies` table keyed by IMDb ID (`external_id`). Subsequent adds of the same film by any user reuse the local record — no repeated external calls, and the watchlist keeps working even if OMDb is temporarily down.
- **Normalized data model.** `movies` (shared catalog) is separate from `watchlist_items` (per-user state: status, rating, notes). A unique constraint on `(user_id, movie_id)` enforces "no duplicates" at the database level rather than relying on application checks alone.
- **Predictable REST surface.** Resourceful URLs, correct status codes (`201` created, `204` deleted, `403` forbidden, `404` not found on OMDb miss, `409` duplicate, `422` validation), and a consistent resource envelope for every item.
- **Ownership enforced on every item route.** `show`/`update`/`destroy` verify the item belongs to the authenticated user and return `403` otherwise, so users can never read or mutate another user's list.

**What I deliberately skipped (and why)**

- **No TMDB / multi-provider abstraction.** A single concrete `MovieDatabaseService` is enough for the brief. If a second provider were needed, I'd extract an interface and bind it in the container — but doing that now would be speculative.
- **No search-by-title endpoint.** The client sends an IMDb ID, which is unambiguous. Title search invites "which of these 8 results did you mean?" handling that's out of scope for the time budget.
- **No background/queued enrichment.** The OMDb call happens inline during `store`. For this volume it's fine; at scale I'd dispatch a job and return the item immediately, backfilling movie details asynchronously.
- **No caching of OMDb responses beyond the DB record, no rate-limit handling.** The local `movies` table already prevents repeat lookups for known films; a dedicated HTTP cache / retry-with-backoff layer would be the next step for production.
- **Focused tests, not exhaustive coverage.** Tests target the things most likely to break: the external-API enrichment path (with `Http::fake()`) and cross-user authorization. I did not write trivial assertions for every field.

**Assumptions made**

- The identifier sent by the client is an **IMDb ID** (e.g. `tt0133093`), matching OMDb's `i=` lookup.
- A rating is an integer **1–5**; notes are capped at 1000 characters.
- A movie that OMDb doesn't recognize is a **client error surfaced as `404`**, not a silently-stored empty record.

## Testing

```bash
php artisan test
# or
./vendor/bin/pest
```

The suite uses `Http::fake()` to stub OMDb, so tests run without network access or a real API key.

## Small maybe important informations
- I did this project as fast as I could with quality in mind
- I first tested with sqlite then switched to postgreSql
- For naming commits I used AI integrated naming generator in VSCode
- ** I DIDN'T IN ANY WAY, EXCEPT GENERATING NAMES FOR COMMITS, USED ANY AI HELPER **
- Anyway I am very comortable with using Claude Code, Codex or any other AI 

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

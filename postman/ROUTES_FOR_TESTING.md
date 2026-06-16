# Watchlist API routes for testing

Base URL: `{{baseUrl}}/api`

Auth:
- `POST /register` and `POST /login` are public.
- All `watchlist` routes and `POST /logout` require `Authorization: Bearer {{accessToken}}`.

Recommended Postman variables:
- `baseUrl` → local app URL, for example `https://watchlistapi.test`
- `accessToken` → bearer token returned by login/register
- `watchlistId` → ID from create/list response

## Route inventory

| Method | Path | Auth | Purpose |
|---|---|---|---|
| POST | `/register` | No | Create user and return access token |
| POST | `/login` | No | Login and return access token |
| POST | `/logout` | Yes | Revoke current access token |
| GET | `/watchlist` | Yes | List current user's watchlist items |
| POST | `/watchlist` | Yes | Add a movie to watchlist |
| GET | `/watchlist/{watchlist}` | Yes | Get one watchlist item |
| PUT | `/watchlist/{watchlist}` | Yes | Update one watchlist item |
| DELETE | `/watchlist/{watchlist}` | Yes | Delete one watchlist item |

## Request details

### 1) Register
**POST** `{{baseUrl}}/api/register`

Body:
```json
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123"
}
```

Expected success:
- Status: `201`
- Body contains:
  - `message`
  - `access_token`
  - `token_type` = `Bearer`

Key validation cases:
- missing `name`
- invalid `email`
- duplicate `email`
- password shorter than 8 chars

---

### 2) Login
**POST** `{{baseUrl}}/api/login`

Body:
```json
{
  "email": "test@example.com",
  "password": "password123"
}
```

Expected success:
- Status: `200`
- Body contains:
  - `message`
  - `access_token`
  - `token_type` = `Bearer`

Key negative cases:
- wrong password → `401`
- unknown email → `401`
- missing email/password → validation error

---

### 3) Logout
**POST** `{{baseUrl}}/api/logout`

Headers:
- `Authorization: Bearer {{accessToken}}`

Expected success:
- Status: `200`
- Body contains success message

Key negative cases:
- no token → unauthenticated
- invalid/expired token → unauthenticated

---

### 4) List watchlist
**GET** `{{baseUrl}}/api/watchlist`

Headers:
- `Authorization: Bearer {{accessToken}}`

Optional query params:
- `status` → one of `to_watch`, `watching`, `watched`

Expected success:
- Status: `200`
- Paginated resource response with `data` array
- Each item contains:
  - `id`
  - `status`
  - `rating`
  - `personal_notes`
  - `added_at`
  - `movie.imdb_id`
  - `movie.title`
  - `movie.description`
  - `movie.poster_url`
  - `movie.release_year`

Key cases:
- no items yet
- filter by each status
- ensure only current user's items are returned
- request without auth should fail

---

### 5) Add movie to watchlist
**POST** `{{baseUrl}}/api/watchlist`

Headers:
- `Authorization: Bearer {{accessToken}}`

Body:
```json
{
  "omdb_id": "tt1234567",
  "status": "to_watch",
  "rating": 5,
  "personal_notes": "Must watch this weekend!"
}
```

Validation rules from code:
- `omdb_id`: required string
- `status`: optional, one of `to_watch`, `watching`, `watched`
- `rating`: optional integer, min `1`, max `5`
- `personal_notes`: optional string, max `1000`

Expected success:
- Status: `201`
- Resource body under `data`

Important edge cases:
- same movie added twice by same user → `409`
- OMDb movie not found → `404`
- invalid status → validation error
- rating below 1 or above 5 → validation error
- notes over 1000 chars → validation error
- no auth → unauthenticated

Note:
- If the movie does not exist locally, the app fetches it from OMDb using `OMDB_API_KEY`.

---

### 6) Get single watchlist item
**GET** `{{baseUrl}}/api/watchlist/{{watchlistId}}`

Headers:
- `Authorization: Bearer {{accessToken}}`

Expected success:
- Status: `200`
- Returns one watchlist item resource

Key negative cases:
- item belongs to another user → `403`
- item ID does not exist → `404`
- no auth → unauthenticated

---

### 7) Update watchlist item
**PUT** `{{baseUrl}}/api/watchlist/{{watchlistId}}`

Headers:
- `Authorization: Bearer {{accessToken}}`

Body example:
```json
{
  "status": "watched",
  "rating": 4,
  "personal_notes": "Finished it yesterday"
}
```

Validation rules from code:
- `status`: sometimes, one of `to_watch`, `watching`, `watched`
- `rating`: nullable integer, min `1`, max `5`
- `personal_notes`: nullable string, max `1000`

Expected success:
- Status: `200`
- Updated resource returned

Key negative cases:
- other user's item → `403`
- invalid status/rating/notes → validation error
- unknown item ID → `404`
- no auth → unauthenticated

---

### 8) Delete watchlist item
**DELETE** `{{baseUrl}}/api/watchlist/{{watchlistId}}`

Headers:
- `Authorization: Bearer {{accessToken}}`

Expected success:
- Status: `204`
- Empty response body

Key negative cases:
- other user's item → `403`
- unknown item ID → `404`
- no auth → unauthenticated

## Suggested testing flow for teammates
1. Register a new user
2. Save `access_token` into `{{accessToken}}`
3. Add a movie to watchlist
4. Save returned `id` into `{{watchlistId}}`
5. List watchlist
6. Get single watchlist item
7. Update watchlist item
8. Delete watchlist item
9. Logout

## Notes for local setup
- This app uses Laravel Sanctum for token auth.
- Movie lookup depends on OMDb configuration:
  - `OMDB_BASE_URL`
  - `OMDB_API_KEY`
- If create-watchlist tests fail for unknown IMDb IDs, check OMDb config in the local `.env`.

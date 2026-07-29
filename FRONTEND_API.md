# Weeplay API — Frontend Guide

## Base URL and conventions

Use your environment's API origin followed by `/api`. For local development, this is normally:

```text
http://localhost:8000/api
```

- JSON requests must send `Content-Type: application/json`.
- File uploads must use `multipart/form-data`; do **not** manually set the multipart `Content-Type` header when using `FormData`.
- Successful validation failures return `422` with Laravel's `errors` object.
- The authenticated endpoints listed below use a Sanctum bearer token:

```http
Authorization: Bearer YOUR_TOKEN
```

The API currently exposes the routes exactly as written below. The `vanue` spelling is part of the current URL and must be preserved by the frontend.

## Authentication

### Register

`POST /v1/auth/register`

No authentication required.

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `username` | string | Yes | Maximum 255 characters |
| `phone` | string | Yes | Must be unique |
| `email` | string | Yes | Valid, unique email address |
| `password` | string | Yes | At least 8 characters |

```js
const response = await fetch(`${API_URL}/v1/auth/register`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    username: 'Jane Doe',
    phone: '+998901234567',
    email: 'jane@example.com',
    password: 'password123',
  }),
});

const { token, user } = await response.json();
localStorage.setItem('authToken', token);
```

Example response (`201 Created`):

```json
{
  "message": "Registered successfully.",
  "user": {
    "id": 1,
    "username": "Jane Doe",
    "phone": "+998901234567",
    "email": "jane@example.com",
    "plan": "free"
  },
  "token": "1|..."
}
```

### Log in

`POST /v1/auth/login`

No authentication required.

```js
const response = await fetch(`${API_URL}/v1/auth/login`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ phone: '+998901234567', password: 'password123' }),
});

if (!response.ok) throw new Error('Incorrect phone or password');
const { token, user } = await response.json();
localStorage.setItem('authToken', token);
```

Returns `200 OK` with the same `message`, `user`, and `token` fields as registration. Invalid credentials return `401`.

### Log out

`POST /v1/auth/logout`

Bearer token required. This revokes the token used in the request.

```js
await fetch(`${API_URL}/v1/auth/logout`, {
  method: 'POST',
  headers: { Authorization: `Bearer ${localStorage.getItem('authToken')}` },
});
localStorage.removeItem('authToken');
```

Response: `200 OK`, `{ "message": "Logged out successfully." }`.

### Current user

`GET /user`

Bearer token required. Returns the authenticated user object.

```js
const response = await fetch(`${API_URL}/user`, {
  headers: { Authorization: `Bearer ${token}` },
});
const user = await response.json();
```

## Categories

### List categories

`GET /v1/category/index`

```js
const { categories } = await fetch(`${API_URL}/v1/category/index`).then((r) => r.json());
```

Response: `{ "categories": [Category] }`.

### Create a category

`POST /v1/category/create`

Use multipart form data. `name` must be a JSON string, allowing translated/category metadata content; `image` is a required PNG, JPG, JPEG, or SVG up to 10 MB.

```js
const data = new FormData();
data.append('name', JSON.stringify({ en: 'Football', uz: 'Futbol' }));
data.append('image', imageFile);

const response = await fetch(`${API_URL}/v1/category/create`, {
  method: 'POST',
  body: data,
});
const { category } = await response.json();
```

Response: `201 Created`, `{ "category": Category }`.

### Update a category

`PUT /v1/category/update/{id}`

Multipart form data with required `name` (currently validated as a plain string) and optional image (JPG, JPEG, PNG, or SVG up to 10 MB).

```js
const data = new FormData();
data.append('name', 'Football');
if (imageFile) data.append('image', imageFile);

await fetch(`${API_URL}/v1/category/update/1`, {
  method: 'POST', // FormData PUT compatibility: send POST with this override.
  headers: { 'X-HTTP-Method-Override': 'PUT' },
  body: data,
});
```

Response: `{ "message": "Category updated successfully.", "category": Category }`.

### Delete a category

`DELETE /v1/category/delete/{id}`

```js
await fetch(`${API_URL}/v1/category/delete/1`, { method: 'DELETE' });
```

Response: `{ "message": "Category deleted successfully." }`.

## Venues

### List venues

`GET /v1/vanue/index`

```js
const { venues } = await fetch(`${API_URL}/v1/vanue/index`).then((r) => r.json());
```

Response: `{ "venues": [Venue] }`.

### Create a venue

`POST /v1/vanue/create`

Use multipart form data. Although this route currently does not enforce authentication middleware, creating a venue relies on the logged-in user, so send the bearer token.

| Field | Type | Required | Example |
| --- | --- | --- | --- |
| `category_id` | integer | Yes | `1` |
| `name` | string | Yes | `Central Football Arena` |
| `address` | string | Yes | `10 Amir Temur Ave` |
| `use_type` | string | Yes | `football` |
| `location` | JSON string | Yes | `{"lat":41.31,"lng":69.28}` |
| `owner_phone` | string | Yes | `+998901234567` |
| `availability` | string | Yes | `09:00-23:00` |
| `price` | number | Yes | `150000` |
| `images[]` | image files | Yes | JPG, JPEG, PNG, or WEBP; max 10 MB each |

```js
const data = new FormData();
data.append('category_id', '1');
data.append('name', 'Central Football Arena');
data.append('address', '10 Amir Temur Ave');
data.append('use_type', 'football');
data.append('location', JSON.stringify({ lat: 41.31, lng: 69.28 }));
data.append('owner_phone', '+998901234567');
data.append('availability', '09:00-23:00');
data.append('price', '150000');
for (const image of imageFiles) data.append('images[]', image);

await fetch(`${API_URL}/v1/vanue/create`, {
  method: 'POST',
  headers: { Authorization: `Bearer ${token}` },
  body: data,
});
```

Response: `201 Created`, `{ "message": "Venue created successfully." }`.

### Venue by category / my venues

Routes currently registered:

```text
GET /v1/vanue/get-by-category
GET /v1/vanue/get-by-user
```

`get-by-user` looks up the authenticated user ID, so send a bearer token. The category service implementation expects a category ID, but the registered route has no `{id}` parameter; therefore the frontend cannot currently call category filtering successfully. It must be corrected server-side to a route such as `/v1/vanue/get-by-category/{id}` before use.

`get-by-user` responds with `{ "venues": [Venue] }`. The category implementation uses the misspelled response key `{ "vanues": [...] }` once its route is corrected.

### Update / delete a venue

Registered routes:

```text
PUT    /v1/vanue/update/{id}
DELETE /v1/vanue/delete/{id}
```

The update body is the same as create; `images[]` is optional and `deleted_images[]` may contain existing venue-image IDs to remove. These routes currently use `{id}` while the controller expects a `{venue}` route-model parameter, so they require a server-side route fix before they can reliably be used.

## Venue slots

### List slots

`GET /v1/slot/index`

```js
const { slots } = await fetch(`${API_URL}/v1/slot/index`).then((r) => r.json());
```

Response: `{ "slots": [VenueSlot] }`.

### Create a slot

`POST /v1/slot/create`

Creating a slot uses the logged-in user ID, so send a bearer token even though the route does not currently enforce it.

```js
const response = await fetch(`${API_URL}/v1/slot/create`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    Authorization: `Bearer ${token}`,
  },
  body: JSON.stringify({
    venue_id: 1,
    date: '2026-08-01',
    start_time: '09:00',
    end_time: '10:00',
    price: 150000,
  }),
});
const { slot } = await response.json();
```

Response: `{ "slot": VenueSlot }`.

### Slots by venue, category, or user

Registered routes:

```text
GET /v1/slot/get-by-venue
GET /v1/slot/get-by-category
GET /v1/slot/get-by-user
```

`get-by-user` uses the logged-in user ID and returns `{ "slots": [VenueSlot] }`; send a bearer token. The venue and category implementations both require an ID, but their registered routes do not provide `{id}`. They cannot currently be called successfully until the backend routes are changed, for example:

```text
GET /v1/slot/get-by-venue/{id}
GET /v1/slot/get-by-category/{id}
```

## Object shapes

```ts
type Category = {
  id: number;
  name: Record<string, string> | string;
  image: string;
  status: string;
  created_at: string;
  updated_at: string;
};

type Venue = {
  id: number;
  category_id: number;
  user_id: number;
  name: string;
  address: string;
  use_type: string;
  location: { lat: number; lng: number };
  owner_phone: string;
  availability: string;
  price: string;
  status: string;
};

type VenueSlot = {
  id: number;
  venue_id: number;
  user_id: number;
  date: string;
  start_time: string;
  end_time: string;
  price: string;
  status: string;
};
```

## Error handling

```js
const response = await fetch(url, options);
const payload = await response.json();

if (!response.ok) {
  // Laravel validation example: payload.errors.phone[0]
  throw new Error(payload.message || 'Request failed');
}
```

Common status codes are `201` for creation, `200` for successful requests, `401` for missing/invalid authentication or invalid login, `404` for unknown records, and `422` for validation errors.

## Framework and utility routes

These routes are registered but are normally not called as part of the token-based frontend API flow:

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/sanctum/csrf-cookie` | Initializes a CSRF cookie for Sanctum's cookie/session authentication flow. It is not needed when using bearer tokens. |
| `GET` | `/up` | Laravel health check. |
| `GET` | `/storage/{path}` | Serves a publicly stored file, such as a category or venue image. |
| `PUT` | `/storage/{path}` | Framework local-storage upload route. |
| `GET` | `/` | Web welcome page, not an API endpoint. |

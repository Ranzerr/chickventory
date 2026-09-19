# Changes

## 1. Dev server fix (Vite / IPv6)

- `vite.config.js` — added `server.host: '127.0.0.1'`. Vite was previously binding only to the IPv6 loopback (`::1`), so injected asset URLs (`http://[::1]:5173/...`) failed to load in browsers/networks without working IPv6 loopback, leaving the app unstyled on first load.

## 2. Authentication (login/logout)

- `app/Http/Controllers/AuthController.php` — `show` (login form), `login`, `logout`.
- `resources/views/auth/login.blade.php` — standalone login page styled to match the app theme.
- `resources/css/app.css` — added `.login-*` styles.
- `database/migrations/2026_09_18_000001_add_username_to_users_table.php` — adds a `username` column to `users`.
- `app/Models/User.php` — `username` added to fillable.
- `database/seeders/DatabaseSeeder.php` — seeds/updates the admin account with `username: admin`, `password: admin`.
- `routes/web.php` — `login` (GET/POST) and `logout` routes are public; every other route requires the `auth` middleware.
- `app/Providers/AppServiceProvider.php` — header now shows the actual logged-in user (via `Auth::user()`) instead of a generic "active user" lookup.
- `resources/views/partials/header.blade.php` — added a Log Out button.

Default login: **admin / admin**.

## 3. Role-based edit/delete restrictions

Rule: any authenticated user can **add** data. Only users with `role = Administrator` can **edit** or **delete** data. This is enforced both in the UI (buttons hidden) and server-side (route middleware), so it can't be bypassed by calling the endpoint directly.

- `app/Http/Middleware/EnsureUserIsAdmin.php` — new middleware, aborts with `403` if the user isn't an Administrator.
- `bootstrap/app.php` — registers the middleware alias `admin`.
- `app/Providers/AppServiceProvider.php` — a global view composer shares `$isAdmin` with every view.
- `routes/web.php` — all edit/delete/approve routes moved into an `admin`-gated group; create/store routes remain open to any authenticated user.

Per-entity changes:

| Entity | Add | Edit | Delete |
|---|---|---|---|
| Products | any user | admin only | admin only (soft: sets `status = inactive`) |
| Ingredients (Raw Materials) | any user | admin only | admin only (soft: sets `status = inactive`) |
| Recipe ingredients | any user | — | admin only |
| Suppliers | any user | admin only | admin only (soft: sets `status = inactive`) |
| Expenses | any user | admin only | admin only (hard delete) + transfer toggle now admin only |
| Purchase Orders | any user | — | admin only, only while status is `draft`; approving is admin only |
| Purchases | any user | — | admin only — reverses the material stock increment and deletes the related stock movements |
| Sales (Order Items) | any user | — | admin only — restores the ingredient stock that was deducted by the recipe (new `RecipeStockService::restockForOrder`) |
| Users | admin only (see §4) | admin only | admin only (cannot delete your own account) |

New controller methods: `ProductController::update/destroy`, `IngredientController::update/destroy`, `SupplierController::update/destroy`, `ExpenseController::update/destroy`, `PurchaseOrderController::destroy`, `PurchaseController::destroy`, `OrderItemController::destroy`.

Views updated with admin-only Edit/Delete controls: `products.blade.php`, `suppliers.blade.php`, `expenses.blade.php`, `purchase-orders.blade.php`, `purchases.blade.php`, `sales.blade.php`, `users.blade.php`.

## 4. User registration page (admin only)

- `app/Http/Controllers/UserController.php` — added `create()` (renders the registration page) and `password` on `store()` now requires `confirmed`.
- `resources/views/users-register.blade.php` — new full-page registration form (name, username, email, password + confirmation, role, status).
- `resources/views/users.blade.php` — the old "Add User" modal was removed and replaced with a `+ Register User` link to the new page (admin-only).
- `routes/web.php` — `GET users/register` (`users.create`) added inside the `admin` middleware group.

Account creation (registration) is restricted to admins only — unlike other entities, regular Staff users cannot create new user accounts, since that would allow privilege escalation.

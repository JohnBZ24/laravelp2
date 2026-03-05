# Intern Guide: Laravel + Filament Deep Dive

This guide explains how your current project works, what was changed, and how Laravel + Filament are implementing the admin frontend and auth behavior.

## 1) Project Overview

Your app currently has three main surfaces:

1. Public web route (`/`) that redirects to the admin panel.
2. Filament admin panel (`/admin`) for managing users.
3. API routes (`/api/...`) protected by Sanctum middleware.

You are using Laravel as the application framework and Filament as the admin UI framework.

## 2) Route Map (Everything important)

### Web routes

File: `routes/web.php`

- `GET /` -> redirects to `/admin`.

### Health route

File: `bootstrap/app.php`

- `GET /up` -> framework health check route.

### API routes

File: `routes/api.php`

All routes below are inside `Route::middleware('auth:sanctum')`, so they require Sanctum auth:

- `GET /api/users` -> list users (paginated)
- `GET /api/users/{user}` -> get single user
- `PUT/PATCH /api/users/{user}` -> update user
- `DELETE /api/users/{user}` -> delete user

### Filament panel routes (auto-generated)

Base panel path is configured as `/admin`.

File: `app/Providers/Filament/AdminPanelProvider.php`

Key routes available through Filament:

- `GET /admin/login` -> admin login page
- `GET /admin` -> dashboard
- `GET /admin/users` -> users listing
- `GET /admin/users/create` -> create user page
- `GET /admin/users/{record}` -> view user page
- `GET /admin/users/{record}/edit` -> edit user page

## 3) What Filament is doing for you

Filament is an admin panel framework for Laravel. Instead of building controllers + Blade pages manually for admin CRUD, you describe resources in PHP classes and Filament renders the frontend and handles workflows.

In this project, Filament handles:

- Admin routing for resources/pages
- Admin auth pages (login)
- CRUD pages (list/create/view/edit)
- Table UI (columns, actions, sorting, pagination, filters)
- Form UI + validation integration
- Dashboard page and widgets

That is why there are very few app-side frontend files, but still a complete admin UI.

## 4) File-by-file deep dive

## 4.1 Filament panel bootstrap

File: `app/Providers/Filament/AdminPanelProvider.php`

This class configures the admin panel:

- `->id('admin')` sets panel id.
- `->path('admin')` mounts panel at `/admin`.
- `->login()` enables the Filament login page.
- `->discoverResources(...)` auto-loads resources from `app/Filament/Resources`.
- `->discoverPages(...)` auto-loads pages from `app/Filament/Pages`.
- `->pages([Dashboard::class])` registers dashboard.
- `->discoverWidgets(...)` and `->widgets([...])` configure dashboard widgets.
- `->middleware([...])` applies cookie/session/csrf/etc middleware.
- `->authMiddleware([Authenticate::class])` protects panel routes.

Why this matters: this is the main entry point for admin panel behavior.

## 4.2 User model and auth access

File: `app/Models/User.php`

Important points:

- Extends `Authenticatable` (Laravel auth user model).
- Implements `FilamentUser` to allow panel access checks.
- `canAccessPanel()` currently returns `true`, so every authenticated user can access admin.
- `fillable` includes `name`, `email`, `password`.
- `hidden` includes `password`, `remember_token`.
- `casts()` includes `'password' => 'hashed'`, so assigning password auto-hashes.

Why this matters: security and panel access are controlled here.

## 4.3 Users resource wiring

File: `app/Filament/Resources/Users/UserResource.php`

This is the core Users resource definition:

- Resource model is `App\Models\User`.
- Form schema delegated to `UserForm::configure(...)`.
- Table schema delegated to `UsersTable::configure(...)`.
- Page routes mapped:
  - `'index' => ListUsers::route('/')`
  - `'create' => CreateUser::route('/create')`
  - `'view' => ViewUser::route('/{record}')`
  - `'edit' => EditUser::route('/{record}/edit')`

Filament uses this to generate a full users admin section.

## 4.4 Users form behavior (create + edit)

File: `app/Filament/Resources/Users/Schemas/UserForm.php`

Fields:

- `name`: required, max 255
- `email`: required, valid email, unique (ignoring current record on edit), max 255
- `password`: password field with reveal option

Password logic:

- Required only on create:
  - `->required(fn (string $operation): bool => $operation === Operation::Create->value)`
- Saved only when not empty:
  - `->dehydrated(fn (?string $state): bool => filled($state))`

Meaning:

- On create page, password must be provided.
- On edit page, password is optional.
- If blank on edit, existing password is preserved.

### 500 error fix you asked for

You had a 500 on `/admin/users/create` caused by operation type mismatch.

Changed from:

- `fn (Operation $operation): bool => $operation === Operation::Create`

To:

- `fn (string $operation): bool => $operation === Operation::Create->value`

In Filament 5 context here, operation is injected as string (`create`, `edit`, `view`).

## 4.5 Users table behavior (list, actions, filter, sort)

File: `app/Filament/Resources/Users/Tables/UsersTable.php`

Columns:

- `id` -> searchable + sortable
- `name` -> searchable + sortable
- `email` -> searchable + sortable
- `created_at` -> sortable

Default ordering:

- `->defaultSort('id', 'desc')`

Filters added:

- ID filter (exact numeric match)
- Name filter (`LIKE %name%`)
- Email filter (`LIKE %email%`)

Actions:

- Row actions: view, edit, delete
- Toolbar bulk action: bulk delete

Pagination:

- Options: 10, 25, 50, 100
- Default: 25

Sorting behavior you asked for:

- Clicking `id`, `name`, or `email` header sorts ascending/descending.

## 4.6 Create button placement

File: `app/Filament/Resources/Users/Pages/ListUsers.php`

Added header action:

- `CreateAction::make()`

Result:

- Create User button now appears in Users section (`/admin/users`).

## 4.7 Dashboard cleanup

File: `app/Filament/Pages/Dashboard.php`

You requested removing create-user action from dashboard.

Current state:

- Dashboard class is minimal, no custom header action.
- Create button remains only in Users page.

## 4.8 Filament page classes for users

Files:

- `app/Filament/Resources/Users/Pages/CreateUser.php`
- `app/Filament/Resources/Users/Pages/EditUser.php`
- `app/Filament/Resources/Users/Pages/ViewUser.php`

These classes are intentionally thin and inherit behavior from Filament base pages:

- `CreateRecord`
- `EditRecord`
- `ViewRecord`

This is standard Filament style: minimal boilerplate, behavior comes from resource + schema + table config.

## 4.9 API users controller

File: `app/Http/Controllers/Api/UserController.php`

Methods:

- `index()`
  - reads `per_page` (default 15, capped to max 100, min 1)
  - returns paginated users sorted by latest id
- `show(User $user)`
  - route model binding returns one user
- `update(Request $request, User $user)`
  - validates optional name/email/password
  - email remains unique except current user
  - blank password is removed before update
- `destroy(User $user)`
  - deletes user and returns 204

## 5) Signup and Auth Explained

You asked specifically for signup + auth explanation. Here is the current implementation reality.

## 5.1 Admin auth (implemented)

- Admin login exists via Filament (`->login()`).
- Uses Laravel session guard from `config/auth.php` (`web` guard).
- Session and CSRF middleware are active for panel routes.

Flow:

1. User opens `/admin`.
2. If not authenticated, redirected to `/admin/login`.
3. On successful login, user gets session and can access panel.

## 5.2 Signup/registration (not currently implemented)

- There is no enabled public signup flow in routes.
- There is no registration page configured for Filament panel.
- New users are currently created through:
  - admin create page (`/admin/users/create`) after login, or
  - seeders/factories/tinker.

If you want self-signup, you need to add registration endpoints/pages (Laravel starter kit, Fortify, Breeze, or custom implementation).

## 5.3 Sanctum API auth

- API users routes are protected by `auth:sanctum` middleware.
- This means requests need a valid authenticated Sanctum context.
- Project currently does not include token-issuing/login API endpoints.

So API protection is in place, but token issuing flow is not yet scaffolded.

## 5.4 Password handling details

- Form side avoids forcing password on edit.
- API side avoids updating password when blank.
- Model cast (`'password' => 'hashed'`) ensures secure hashing whenever password is set.

## 6) Frontend Explained (What is app frontend vs Filament frontend)

You asked for frontend explanation in depth. In this project there are two layers.

## 6.1 Application asset pipeline (your app files)

Files:

- `package.json`
- `vite.config.js`
- `resources/css/app.css`
- `resources/js/app.js`
- `resources/js/bootstrap.js`

What they do:

- Vite builds frontend assets (`npm run dev`, `npm run build`).
- Tailwind is configured through the Vite plugin.
- `bootstrap.js` sets up Axios with `X-Requested-With` header.
- These are minimal currently and mostly default Laravel setup.

## 6.2 Filament frontend (major admin UI)

Most admin UI you see is rendered by Filament internals (vendor package), not by custom Blade files in your app.

You define behavior in PHP classes (resource/table/form/page), and Filament renders:

- forms
- tables
- action buttons
- filter drawers
- sorting controls
- pagination UI

That is why this repo has very few custom frontend templates, but still has a complete admin interface.

## 7) Database + seed layer used by auth/users

### Users table

File: `database/migrations/0001_01_01_000000_create_users_table.php`

Columns include:

- `id`
- `name`
- `email` unique
- `email_verified_at`
- `password`
- `remember_token`
- timestamps

### Seeded default user

File: `database/seeders/DatabaseSeeder.php`

Creates default user:

- email: `test@example.com`

Factory uses default password:

File: `database/factories/UserFactory.php`

- password hashed from string `password`.

So local login can usually use `test@example.com` / `password` after seeding.

## 8) Exact change log from our session

1. Fixed 500 on create user form:
   - file: `app/Filament/Resources/Users/Schemas/UserForm.php`
   - operation closure now uses string operation value comparison.

2. Added Create button inside users list page:
   - file: `app/Filament/Resources/Users/Pages/ListUsers.php`
   - added `CreateAction::make()` in header actions.

3. Added users table filters:
   - file: `app/Filament/Resources/Users/Tables/UsersTable.php`
   - filters for id, name, email.

4. Confirmed sortable columns behavior:
   - file: `app/Filament/Resources/Users/Tables/UsersTable.php`
   - `id`, `name`, `email` have `->sortable()`.

5. Removed dashboard create button:
   - file: `app/Filament/Pages/Dashboard.php`
   - no custom create action remains.

## 9) Filament features you are already using

- Resource-based CRUD generation
- Operation-aware forms (create/edit/view context)
- Built-in validation integration
- Table search
- Table filters with custom query closures
- Column sorting + default sort
- Row actions + bulk actions
- Pagination configuration
- Auto-discovery of resources/pages/widgets
- Panel auth integration with Laravel middleware

## 10) Suggested next intern steps

1. Add role-based panel access in `User::canAccessPanel()`.
2. Add proper registration flow if needed (Breeze/Fortify/custom).
3. Add API auth endpoints for Sanctum token issuing.
4. Add feature tests for `/admin/users` list/create/edit and API user routes.

---

If you want, the next guide can be a sequence diagram version (request lifecycle from browser -> middleware -> Filament page -> Eloquent -> response), plus a second doc for "how to add any new Filament resource from scratch".

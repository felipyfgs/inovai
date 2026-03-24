# AGENTS.md

## Project Overview

InovAI is a multi-tenant SaaS application for Brazilian accounting firms to manage companies and issue fiscal documents (NF-e, NFC-e, CT-e, MDF-e). Includes an admin panel for managing offices (contadores), subscriptions, invoicing, and platform-wide analytics.

- **Backend**: Laravel 11 (PHP 8.2+) with PostgreSQL, Redis, Sanctum auth
- **Frontend**: Nuxt 3 with TypeScript, Nuxt UI, TanStack Table
- **Architecture**: Multi-tenant with company_id scoping via `X-Company-Id` header

## Build/Lint/Test Commands

### Backend (Laravel)

```bash
# Install dependencies
composer install

# Run migrations
php artisan migrate

# Run seeders (creates admin, demo office, plans)
php artisan db:seed

# Run all tests
php artisan test

# Run a single test file
php artisan test --filter=PessoaControllerTest

# Run a single test method
php artisan test --filter=PessoaControllerTest::test_can_list_pessoas

# Run linting (if PHP CS Fixer configured)
./vendor/bin/pint

# Clear caches
php artisan optimize:clear
```

### Frontend (Nuxt)

```bash
# Install dependencies
pnpm install

# Run dev server (proxies API to localhost:8000)
pnpm dev

# Build for production
pnpm build

# Run linting
pnpm lint

# Run type checking
pnpm typecheck

# Run lint + typecheck
pnpm lint && pnpm typecheck
```

## Architecture

### Roles & Access Control

- **admin**: Platform administrators. Full access to admin panel (`/admin/*` routes). Managed via `EnsureAdmin` middleware.
- **office_user** (default): Accounting firm users. Scoped to their `office_id`. Can manage companies belonging to their office.
- **company_user**: Users directly attached to specific companies. Access limited to assigned companies via `$user->companies()`.

### Route Structure (Backend)

```
POST   /api/register, /api/login          # Public auth
POST   /api/logout                         # Authenticated
GET    /api/me                             # Current user with office, companies, roles

# Office-scoped (no X-Company-Id needed)
GET|POST        /api/companies
GET|PUT|DELETE  /api/companies/{id}
GET             /api/dashboard/office

# Company-scoped (requires X-Company-Id header → ResolveTenant middleware)
GET|POST        /api/pessoas, /api/produtos, /api/transportadoras
GET|POST        /api/orcamentos, /api/pedidos
GET             /api/dashboard/company

# Admin-only (requires admin role → EnsureAdmin middleware)
GET|POST        /api/admin/offices
GET|PUT|DELETE  /api/admin/offices/{id}
POST            /api/admin/offices/{id}/assign-plan
GET             /api/admin/offices/map
GET|POST        /api/admin/invoices
GET|PUT|DELETE  /api/admin/invoices/{id}
GET             /api/admin/invoices/dashboard
POST            /api/admin/invoices/generate-monthly
```

### Key Models

- **Office**: Accounting firm or direct client. Has `type` (admin/contador/direct), reseller fields, parent_office_id.
- **Company**: A client company managed by an office. Scoped by `office_id`.
- **Plan/Subscription**: SaaS plans with `max_companies` and `max_nfs_month` limits.
- **Invoice/InvoiceItem**: Platform billing. Tracks monthly charges per office.
- **Pessoa, Produto, Transportadora**: Client-managed entities scoped by `company_id`.
- **Orcamento, Pedido**: Commercial flow (quotes → orders).
- **NotaFiscal, Cte, Mdfe**: Fiscal documents.

### Frontend Page Structure

```
/login, /register                    # Auth (layout: auth, guest-only)
/                                    # Office dashboard
/empresas                            # Company management (with search, filters, row selection)
/cadastros/pessoas                   # People registry
/cadastros/produtos                  # Products registry
/cadastros/transportadoras           # Carriers registry
/comercial/orcamentos                # Quotes
/comercial/pedidos                   # Orders
/fiscal/nfe, /fiscal/nfce            # Fiscal documents
/fiscal/cte, /fiscal/mdfe            # Transport documents
/admin                               # Admin dashboard (middleware: admin)
/admin/contadores                    # Office management
/admin/cobrancas                     # Invoice/billing management
/admin/mapa                          # Offices × Companies map
/settings/*                          # User settings
```

## Code Style Guidelines

### Backend (Laravel/PHP)

- **PSR-12** coding standard enforced via Laravel Pint
- Use **dependency injection** via constructor (see `OrcamentoController`, `PedidoController`)
- Controllers use `HasPagination` trait for consistent pagination
- Always scope queries to `current_company`: `$company = app('current_company')`
- Admin controllers live in `App\Http\Controllers\Admin\` namespace
- Use **Form Requests** for validation (`StoreOrcamentoRequest`, `UpdatePedidoRequest`)
- Use **API Resources** for responses (`OrcamentoResource`, `PedidoResource`)
- Services handle business logic (`OrcamentoService`, `PedidoService`)
- Authorize resources manually: `$this->authorizeResource($model)` or check `company_id`
- Return JSON responses: `response()->json($data, $statusCode)`
- Use cache for dashboard queries: `Cache::remember($key, 300, fn() => ...)`
- Plan limits enforced in controllers (e.g., `max_companies` check in `CompanyController@store`)

### Frontend (Nuxt/TypeScript)

- **Vue 3 Composition API** with `<script setup lang="ts">`
- Use **Nuxt composables**: `useApi()`, `useApiMutation()`, `useCurrentCompany()`
- Additional composables: `useCnpjSearch()`, `useCepSearch()`, `useDashboard()`
- Form validation with **Zod schemas** and `@nuxt/ui` `UForm`
- Table display uses **TanStack Table** via `@nuxt/ui` `UTable`
- Modal pattern: `AddModal.vue`, `EditModal.vue`, `DeleteModal.vue` per entity
- Types defined in `app/types/index.d.ts` - extend there for new entities
- Utilities in `app/utils/index.ts` - use `formatCurrency()` for BRL values
- Column visibility: allow users to toggle via `UDropdownMenu`
- Row selection: use `v-model:row-selection` with `UCheckbox` in column definitions
- Column filtering: use `v-model:column-filters` with search inputs
- Pagination: use `getPaginationRowModel()` from `@tanstack/table-core`
- Toast notifications: `toast.add({ title, color: 'success'|'error' })`
- Loading states: `const loading = ref(false)` with `:loading="loading"`
- Route middleware: `admin.ts` guards admin pages, Sanctum `guestOnly` for auth pages

### Naming Conventions

- **Models**: PascalCase singular (`Pessoa`, `Produto`, `Pedido`, `Invoice`)
- **Controllers**: PascalCase with `Controller` suffix (`PessoaController`, `Admin\OfficeController`)
- **Routes**: kebab-case (`/cadastros/pessoas`, `/comercial/pedidos`, `/admin/cobrancas`)
- **Components**: PascalCase (`AddModal.vue`, `EditModal.vue`)
- **Database tables**: snake_case plural (`pessoas`, `produtos`, `pedido_itens`, `invoices`)
- **API endpoints**: snake_case (`/api/pessoas`, `/api/admin/offices`)
- **Middleware**: camelCase files (`admin.ts`), PascalCase classes (`EnsureAdmin`)

### Error Handling

- Backend: return JSON with `message` key and appropriate status code
- Frontend: catch errors with `catch (e: unknown)`, cast to typed error object
- Extract error messages: `const err = e as { response?: { _data?: { message?: string } } }` then `err?.response?._data?.message`
- Display via `toast.add({ color: 'error' })`

### Multi-Tenancy Pattern

- Every query must be scoped to company: `$company->pessoas()` not `Pessoa::all()`
- Frontend passes `X-Company-Id` header automatically via `useApi()` composable
- Current company stored in localStorage: `current_company_id`
- Office-level queries scoped by `$user->office_id` (or `$user->companies()` for company_user role)
- Admin queries are unscoped (full platform access)

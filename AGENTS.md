# AGENTS.md

## Project Overview

InovAI is a multi-tenant SaaS application for Brazilian accounting firms to manage companies and issue fiscal documents (NF-e, NFC-e, CT-e, MDF-e).

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

## Code Style Guidelines

### Backend (Laravel/PHP)

- **PSR-12** coding standard enforced via Laravel Pint
- Use **dependency injection** via constructor (see `OrcamentoController`, `PedidoController`)
- Controllers use `HasPagination` trait for consistent pagination
- Always scope queries to `current_company`: `$company = app('current_company')`
- Use **Form Requests** for validation (`StoreOrcamentoRequest`, `UpdatePedidoRequest`)
- Use **API Resources** for responses (`OrcamentoResource`, `PedidoResource`)
- Services handle business logic (`OrcamentoService`, `PedidoService`)
- Authorize resources manually: `$this->authorizeResource($model)` or check `company_id`
- Return JSON responses: `response()->json($data, $statusCode)`
- Use cache for dashboard queries: `Cache::remember($key, 300, fn() => ...)`

### Frontend (Nuxt/TypeScript)

- **Vue 3 Composition API** with `<script setup lang="ts">`
- Use **Nuxt composables**: `useApi()`, `useApiMutation()`, `useCurrentCompany()`
- Form validation with **Zod schemas** and `@nuxt/ui` `UForm`
- Table display uses **TanStack Table** via `@nuxt/ui` `UTable`
- Modal pattern: `AddModal.vue`, `EditModal.vue`, `DeleteModal.vue` per entity
- Types defined in `app/types/index.d.ts` - extend there for new entities
- Utilities in `app/utils/index.ts` - use `formatCurrency()` for BRL values
- Column visibility: allow users to toggle via `UDropdownMenu`
- Pagination: use `getPaginationRowModel()` from `@tanstack/table-core`
- Toast notifications: `toast.add({ title, color: 'success'|'error' })`
- Loading states: `const loading = ref(false)` with `:loading="loading"`

### Naming Conventions

- **Models**: PascalCase singular (`Pessoa`, `Produto`, `Pedido`)
- **Controllers**: PascalCase with `Controller` suffix (`PessoaController`)
- **Routes**: kebab-case (`/cadastros/pessoas`, `/comercial/pedidos`)
- **Components**: PascalCase (`AddModal.vue`, `EditModal.vue`)
- **Database tables**: snake_case plural (`pessoas`, `produtos`, `pedido_itens`)
- **API endpoints**: snake_case (`/api/pessoas`, `/api/orcamento-itens`)

### Error Handling

- Backend: return JSON with `message` key and appropriate status code
- Frontend: catch errors in try/catch, display via `toast.add({ color: 'error' })`
- Use `e?.response?._data?.message` to extract Laravel error messages

### Multi-Tenancy Pattern

- Every query must be scoped to company: `$company->pessoas()` not `Pessoa::all()`
- Frontend passes `X-Company-Id` header automatically via `useApi()` composable
- Current company stored in localStorage: `current_company_id`

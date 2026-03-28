# AGENTS.md

## Project Overview

InovAI is a multi-tenant SaaS for Brazilian accounting firms. **Backend**: Laravel 13 (PHP 8.3+) with PostgreSQL 16, Redis 7, Sanctum 4, Spatie Permission 7. **Frontend**: Nuxt 4 (SPA mode) with TypeScript, Nuxt UI v4, TanStack Table v8, Zod 4, nuxt-auth-sanctum. Multi-tenancy via `X-Company-Id` header. All user-facing text in Portuguese.

**Infrastructure**: Docker Compose (PostgreSQL 16 + Redis 7). Backend on `0.0.0.0:8000`, frontend on `localhost:3000`.

## Build/Lint/Test Commands

### Backend (Laravel) — run from `backend/`
```bash
composer install                          # Install dependencies
php artisan migrate && php artisan db:seed  # Setup database
php artisan test                          # All tests (SQLite in-memory)
php artisan test --filter=PessoaControllerTest           # Single test file
php artisan test --filter=PessoaControllerTest::test_can_list_pessoas  # Single method
./vendor/bin/pint                         # Lint/fix (PSR-12, default preset)
./vendor/bin/pint --test                  # Lint without fixing
php artisan optimize:clear                # Clear all caches
composer dev                              # Serve + queue + pail logs + vite
```

### Frontend (Nuxt) — run from `frontend/`
```bash
pnpm install                              # Install dependencies
pnpm dev                                  # Dev server on :3000
pnpm build                                # Production build
pnpm lint                                 # ESLint
pnpm typecheck                            # TypeScript checking
pnpm lint && pnpm typecheck               # Both (run this before committing)
```

### Docker
```bash
docker compose up -d                      # Start PostgreSQL + Redis
```

## Code Style Guidelines

### Backend (Laravel/PHP)

**PSR-12** enforced via Pint (default preset, no custom config). 4-space indent, LF line endings.

**Controllers**: Constructor DI, `HasPagination` trait (default 20/page, max 100), always scope to `current_company`.
- Simple CRUD (Pessoa, Produto, Transportadora): Inline `$request->validate()`, return `$model` directly
- Complex CRUD (Orcamento, Pedido, Nfe): Form Request classes, API Resources, injected Services
- Service-injected (Conta, Estoque): Constructor DI for service, inline validation, no Form Request

**Auth controllers**: Single-action `__invoke` pattern (`LoginController`, `RegisterController`, etc.).
**Admin controllers** (`Admin\*`): No tenant scoping, admin middleware handles auth. Some use direct `->paginate()` instead of `HasPagination`.

**Form Requests**: Place in `app/Http/Requests/`, return `true` from `authorize()`. `NfeController` reuses `StoreNfeRequest` for both create and update.
**API Resources**: Use `Resource::make($model)` or `Resource::collection()`, `$this->whenLoaded('relation')`.
**Services**: Business logic in `app/Services/`. Use `DB::transaction()` for multi-record operations. Complex entities use item delete+recreate pattern inside transactions. Some services call `app('current_company')` directly; others receive `$company` as parameter.
**Authorization**: Private `authorizeXxx()` methods checking `company_id` ownership. `abort(403, 'Sem permissao.')`.
**Models**: Use `$fillable`/`$hidden` properties (PHP 8 attributes only on `User` model). `is_active` boolean on most entities. Sensitive data encrypted via casts (`Company.certificado_pfx`, `certificado_senha`). `Conta` has a global scope for company scoping; other models rely on controller-level scoping.
**Migrations**: `$table->id()`, `constrained()` + `cascadeOnDelete()`. Grouped by domain (cadastros, comercial, fiscal, estoque, financeiro). Composite indexes on `(company_id, status)`. Status fields are Portuguese strings. Decimal precision: `15,2` money, `15,4` quantities/prices, `5,2` tax rates.
**Jobs**: All in `app/Jobs/`, dispatched with default `dispatch()`. Constructor-promoted properties. 3 tries with exponential backoff `[30, 60, 120]`. `TransmitDocumentJob` catches exceptions and updates model status; others re-throw after logging.
**Scheduled Commands**: `app:check-delinquency` runs daily at 02:00 (marks overdue invoices, inactivates offices).

### Frontend (Nuxt/TypeScript)

**Vue 3 Composition API** with `<script setup lang="ts">`. 2-space indent, LF line endings.

**Data Layer Composables** (all in `app/composables/`):
- `useApi<T>(url, options)` — GET with auto `X-Company-Id`/`X-Office-Id` headers, wraps `$sanctumClient` in `useAsyncData`. URL prefixed with `/api`.
- `useApiMutation()` — Returns `{ post, put, patch, del }`, same auto-headers. URL prefixed with `/api`.
- `useApiError()` — `extractMessage(error)` + `handleError(error)` (adds toast)
- `useAppToast()` — Semantic helpers: `success()`, `error()`, `warning()`, `info()`

**Context Composables** (singleton refs, localStorage persistence):
- `useCurrentCompany()` / `useCurrentOffice()` — Active company/office selection
- `useAccessContext()` — Role-based access: `isAdmin`, `isAccountant`, `canSee(module)`, `effectiveRole`
- `useCompanyModules()` — Module availability per company: `hasModule()`, `activeModules`

**Domain Composables** (build on data layer, expose CRUD operations):
- `useNfe()` / `useCte()` / `useMdfe()` — Fiscal document lifecycle (sign, transmit, cancel, carta correcao)
- `useContas()` — Financial accounts (create, update, delete, baixar parcela, estornar)
- `useEmitente()` — Company emitter settings (dados, numeracao, csc, certificado)
- `useEstoque()` — Inventory (positions, movimentacoes, ajuste)

**Utility Composables**: `useDashboard()` (keyboard shortcuts), `useCepSearch()` / `useCnpjSearch()` (Brazilian lookups), `useConfirmation()` (generic delete state).

**Forms with Zod**: Define schema with `z.object()`, type with `z.output<typeof schema>`, submit via `useTemplateRef('formRef')` → `formRef.value?.submit()`.

**Tables**: TanStack Table v8 with `UTable`. Use `v-model:column-filters`, `v-model:row-selection`, `v-model:pagination`. Render cells with `h()` functions. `getPaginationRowModel()` from `@tanstack/table-core`.

**Modal Pattern**: Three modals per entity (`AddModal.vue`, `EditModal.vue`, `DeleteModal.vue`). AddModal contains its own trigger button. Edit/Delete use `<slot />` for external trigger. All use `v-model:open`, emit `@created/@updated/@deleted`. DeleteModal uses `color="error"` button. **Action modals** also exist: `CancelModal`, `BaixaModal`, `EncerrarModal`, `AjusteModal`, `GenerateModal`.

**Layouts**: `default.vue` (UDashboardPanel + sidebar + search command palette), `auth.vue` (centered card). List pages use `UDashboardPanel` with `#header`/`#body` slots. Settings pages use `UPageHeader` + `UPageCard` (no UDashboardPanel).

**Types**: Define in `app/types/index.d.ts`. Use `PaginatedResponse<T>` for all list endpoints.

**Route Middleware**: `admin.ts` (redirects non-admins), `escritorio.ts` (redirects admins without office). Sanctum global middleware handles auth redirects.

**Special Controllers**: `EmitenteController` operates on `current_company` directly (singleton, no model ID). `FornecedorController` is a filtered view of `Pessoa` (not a separate entity).

### Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| Models | PascalCase singular | `Pessoa`, `NotaFiscal`, `PedidoItem` |
| Controllers | PascalCase + Controller | `PessoaController`, `Admin\PlanController` |
| Services | PascalCase | `OrcamentoService`, `PlanLimitService` |
| Routes (URL) | kebab-case | `/cadastros/pessoas`, `/api/default-company` |
| Components | PascalCase | `AddModal.vue`, `CompanySelector.vue` |
| Component dirs | kebab-case plural | `pessoas/`, `transportadoras/` |
| Composables | camelCase with `use` | `useCepSearch`, `useAccessContext` |
| DB tables | snake_case plural | `pessoas`, `nota_fiscal_itens` |
| API endpoints | snake_case | `/api/pessoas`, `/api/orcamentos` |
| Foreign keys | `{table_singular}_id` | `company_id`, `pessoa_id` |
| Status values | Portuguese strings | `rascunho`, `aprovado`, `faturado` |
| Icons | `i-lucide-*` | `i-lucide-building-2` |

### Error Handling

**Backend**: Return JSON with `message` key: `response()->json(['message' => 'Error'], 422)`. Use `abort(403, 'Sem permissao.')` for auth. Messages in Portuguese.

**Frontend**: Use `useApiError()` composable. `extractMessage` pulls `err?.response?._data?.message`. `handleError` adds toast automatically.

### Multi-Tenancy Pattern

Three-level context: **Platform Admin** > **Office (Escritório)** > **Company (Empresa)**.

- **Backend**: `ResolveTenant` middleware reads `X-Company-Id` header, auto-selects company if user has exactly one, validates ownership by role, caches in Redis (5 min). Scope all queries via `$company->pessoas()`. Access via `app('current_company')`. `EnsureActiveSubscription` middleware checks subscription status (admins bypass).
- **Frontend**: `useApi()`/`useApiMutation()` auto-inject headers. Current company in localStorage.
- **Roles**: `admin` (platform), `office_user`/`accountant` (office), `company_user` (company). Offices have parent-child hierarchy.
- **Route nesting**: `auth:sanctum` → `EnsureActiveSubscription` → `ResolveTenant` (company scope) / `EnsureAdmin` (admin scope).

### Import Organization

**Backend** — alphabetize within groups (App\*, Illuminate\*, other):
```php
use App\Http\Requests\StoreOrcamentoRequest;
use App\Http\Resources\OrcamentoResource;
use App\Services\OrcamentoService;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
```

**Frontend** — Vue built-ins, type imports, components, utilities:
```typescript
import { computed, h, ref } from 'vue'
import type { TableColumn } from '@nuxt/ui'
import type { Pessoa, PaginatedResponse } from '~/types'
import { UButton, UBadge } from '#components'
import { getPaginationRowModel } from '@tanstack/table-core'
```

### Stylistic Rules

- **ESLint** (via `nuxt.config.ts`): `commaDangle: 'never'`, `braceStyle: '1tbs'`, `vue/max-attributes-per-line: ['error', { singleline: 3 }]`, `vue/no-multiple-template-root: off`
- **Theme**: Primary color `green`, neutral `zinc`, font `Public Sans`
- **`.client.vue` suffix**: Use for chart components needing browser APIs (Unovis)
- **DO NOT add comments** to code unless explicitly requested
# AGENTS.md

## Project Overview

InovAI is a multi-tenant SaaS for Brazilian accounting firms. **Backend**: Laravel 13 (PHP 8.3+) with PostgreSQL 16, Redis 7, Sanctum 4, Spatie Permission 7. **Frontend**: Nuxt 4 with TypeScript, Nuxt UI v4, TanStack Table, Zod. Multi-tenancy via `X-Company-Id` header. SPA mode (no SSR). All user-facing text in Portuguese.

**Infrastructure**: Docker Compose with PostgreSQL 16 and Redis 7. Backend runs on `0.0.0.0:8000`, frontend on `localhost:3000`.

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
pnpm lint && pnpm typecheck               # Both
```

### Docker
```bash
docker compose up -d                      # Start PostgreSQL + Redis
```

## Code Style Guidelines

### Backend (Laravel/PHP)

**PSR-12** enforced via Pint (default preset, no custom config). 4-space indent, LF line endings.

**Controllers**: Constructor dependency injection, `HasPagination` trait, always scope to `current_company`. Two patterns:
- Simple CRUD (Pessoa, Produto, Transportadora): Inline `$request->validate()`, return `$model` directly
- Complex CRUD (Orcamento, Pedido): Form Request classes, API Resources, injected Services

```php
class OrcamentoController extends Controller
{
    use HasPagination;

    public function __construct(private OrcamentoService $service) {}

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');
        $query = $company->orcamentos()->with('pessoa');
        return response()->json($this->paginate($query, $request));
    }
}
```

**Form Requests**: Place in `app/Http/Requests/`, return `true` from `authorize()`.
```php
class StoreOrcamentoRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['pessoa_id' => ['nullable', 'exists:pessoas,id']]; }
}
```

**API Resources**: Use `Resource::make($model)` or `Resource::collection()`, `$this->whenLoaded('relation')`.

**Services**: Business logic in `app/Services/`, `DB::transaction()` for multi-record operations.
```php
class OrcamentoService {
    public function createOrcamento(array $data, $company): Orcamento {
        return DB::transaction(function () use ($data, $company) { /* logic */ });
    }
}
```

**Authorization**: Manual `authorizeXxx()` private methods checking `company_id` ownership, or `abort(403, 'Sem permissao.')`.
```php
private function authorizePessoa(Pessoa $pessoa): void {
    $company = app('current_company');
    if ($pessoa->company_id !== $company->id) abort(403, 'Sem permissao.');
}
```

**Models**: Use PHP 8 `#[Fillable]` and `#[Hidden]` attributes. Decimal precision: `15,2` for money, `15,4` for quantities, `5,2` for tax rates. All tenant-scoped tables have `company_id` FK. `is_active` boolean on most entities.

**Migrations**: `$table->id()`, `constrained()` + `cascadeOnDelete()`, composite indexes on `(company_id, status)`. Status fields are strings with Portuguese values.

### Frontend (Nuxt/TypeScript)

**Vue 3 Composition API** with `<script setup lang="ts">`. 2-space indent, LF line endings.

**Composables** (all in `app/composables/`):
- `useApi<T>(url, options)` — GET with auto `X-Company-Id`/`X-Office-Id` headers, wraps `$sanctumClient` in `useAsyncData`
- `useApiMutation()` — Returns `{ post, put, patch, del }`, same auto-headers
- `useApiError()` — `extractMessage(error)` + `handleError(error)` (adds toast)
- `useAppToast()` — Semantic helpers: `success()`, `error()`, `warning()`, `info()`
- `useCurrentCompany()`, `useCurrentOffice()` — localStorage persistence, singleton refs
- `useAccessContext()` — Role-based access: `isAdmin`, `isAccountant`, `canSee(module)`

```typescript
const { data, status, refresh } = useApi<PaginatedResponse<Pessoa>>('/pessoas', { lazy: true })
const { post, put, del } = useApiMutation()
const { extractMessage } = useApiError()
```

**Forms with Zod**: Define schema with `z.object()`, type with `z.output<typeof schema>`, submit via `useTemplateRef('formRef')`.
```typescript
const schema = z.object({ tipo: z.enum(['cliente', 'fornecedor']), razao_social: z.string().min(2) })

async function onSubmit(event: FormSubmitEvent<z.output<typeof schema>>) {
  loading.value = true
  try {
    await post('/pessoas', event.data)
    useAppToast().success('Salvo com sucesso')
    open.value = false
    emit('created')
  } catch (error) {
    useAppToast().error(extractMessage(error))
  } finally { loading.value = false }
}
```

**Tables**: TanStack Table with `UTable`. Use `v-model:column-filters`, `v-model:row-selection`, `v-model:pagination`. Render cells with `h()` functions. Use `getPaginationRowModel()` from `@tanstack/table-core`.

**Modal Pattern**: Three modals per entity (`AddModal.vue`, `EditModal.vue`, `DeleteModal.vue`). AddModal contains its own trigger button. Edit/Delete use `<slot />` for external trigger. All use `v-model:open`, emit `@created/@updated/@deleted`.

**Types**: Define in `app/types/index.d.ts`. Use `PaginatedResponse<T>` for all list endpoints.

### Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| Models | PascalCase singular | `Pessoa`, `NotaFiscal`, `PedidoItem` |
| Controllers | PascalCase + Controller | `PessoaController`, `Admin\PlanController` |
| Services | PascalCase | `OrcamentoService` |
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

**Frontend**: Use `useApiError()` composable:
```typescript
const { extractMessage, handleError } = useApiError()
// extractMessage pulls err?.response?._data?.message
// handleError adds toast automatically
```

### Multi-Tenancy Pattern

Three-level context: **Platform Admin** > **Office (Escritório)** > **Company (Empresa)**.

- **Backend**: `ResolveTenant` middleware reads `X-Company-Id` header, validates ownership by role, caches in Redis (5 min). Scope all queries: `$company->pessoas()` never `Pessoa::all()`. Access via `app('current_company')`.
- **Frontend**: `useApi()`/`useApiMutation()` auto-inject `X-Company-Id` and `X-Office-Id` headers. Current company in localStorage: `current_company_id`.
- **Roles**: `admin` (platform), `office_user`/`accountant` (office), `company_user` (company). Use `useAccessContext().canSee(module)` for menu visibility.

### Import Organization

**Backend** — alphabetize within groups (App\*, Illuminate\*, other):
```php
namespace App\Http\Controllers;

use App\Http\Requests\StoreOrcamentoRequest;
use App\Http\Resources\OrcamentoResource;
use App\Services\OrcamentoService;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
```

**Frontend** — Vue built-ins, then type imports, then components, then utilities:
```typescript
import { computed, h, ref } from 'vue'
import type { TableColumn } from '@nuxt/ui'
import type { Pessoa, PaginatedResponse } from '~/types'
import { UButton, UBadge } from '#components'
import { getPaginationRowModel } from '@tanstack/table-core'
```

### ESLint Rules (Frontend)

Flat config in `eslint.config.mjs`. Key overrides: `vue/max-attributes-per-line: ['error', { singleline: 3 }]`, `vue/no-multiple-template-root: off`, `commaDangle: 'never'`, `braceStyle: '1tbs'`.

# AGENTS.md

## Project Overview

InovAI is a multi-tenant SaaS for Brazilian accounting firms. **Backend**: Laravel 11 (PHP 8.3+) with PostgreSQL, Redis, Sanctum. **Frontend**: Nuxt 3 with TypeScript, Nuxt UI, TanStack Table. Multi-tenancy via `X-Company-Id` header.

## Build/Lint/Test Commands

### Backend (Laravel)
```bash
composer install && php artisan migrate && php artisan db:seed
php artisan test                          # All tests
php artisan test --filter=PessoaControllerTest           # Single test file
php artisan test --filter=PessoaControllerTest::test_can_list_pessoas  # Single method
./vendor/bin/pint                         # Lint (PSR-12)
php artisan optimize:clear                # Clear caches
```

### Frontend (Nuxt)
```bash
pnpm install
pnpm dev                                  # Dev server (proxies to localhost:8000)
pnpm build                                # Production build
pnpm lint && pnpm typecheck               # Lint + typecheck
```

## Code Style Guidelines

### Backend (Laravel/PHP)

**PSR-12** enforced via Pint.

**Controllers**: Constructor dependency injection, `HasPagination` trait, always scope to `current_company`.
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
    public function rules(): array { return [ 'pessoa_id' => ['nullable', 'exists:pessoas,id'] ]; }
}
```

**API Resources**: Use `new Resource($model)` or `Resource::collection()`, `$this->whenLoaded('relation')`.
```php
class OrcamentoResource extends JsonResource
{
    public function toArray(Request $request): array {
        return ['id' => $this->id, 'pessoa' => new PessoaResource($this->whenLoaded('pessoa'))];
    }
}
```

**Services**: Business logic in `app/Services/`, `DB::transaction()` for multi-record operations.
```php
class OrcamentoService {
    public function create(array $data, $company): Orcamento {
        return DB::transaction(function () use ($data, $company) { /* logic */ });
    }
}
```

**Authorization**: Manual checks or `abort(403, 'Sem permissão.')`.
```php
private function authorizePessoa(Pessoa $pessoa): void {
    $company = app('current_company');
    if ($pessoa->company_id !== $company->id) abort(403, 'Sem permissão.');
}
```

### Frontend (Nuxt/TypeScript)

**Vue 3 Composition API** with `<script setup lang="ts">`.

**Composables**:
- `useApi<T>(url, options)` - GET with auto company/office headers
- `useApiMutation()` - Returns `{ post, put, patch, del }`
- `useCurrentCompany()`, `useCurrentOffice()`, `useDashboard()`

```typescript
const { data, status, refresh } = useApi<PaginatedResponse<Pessoa>>('/pessoas', { lazy: true })
const { post, put, del } = useApiMutation()
```

**Forms with Zod**:
```typescript
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const schema = z.object({ tipo: z.enum(['cliente', 'fornecedor']), razao_social: z.string().min(2) })

async function onSubmit(event: FormSubmitEvent<z.output<typeof schema>>) {
    loading.value = true
    try {
        await post('/pessoas', event.data)
        toast.add({ title: 'Sucesso', color: 'success' })
    } catch (error) {
        const err = error as { response?: { _data?: { message?: string } } }
        toast.add({ title: 'Erro', description: err?.response?._data?.message, color: 'error' })
    } finally { loading.value = false }
}
```

**Tables with TanStack**: `v-model:column-filters`, `v-model:row-selection`, `v-model:pagination`.

**Modal Pattern**: Three modals per entity (`AddModal.vue`, `EditModal.vue`, `DeleteModal.vue`). Use `v-model:open`, emit `@created/@updated/@deleted`, `useTemplateRef('formRef')`.

**Types**: Define in `app/types/index.d.ts`. Use `PaginatedResponse<T>`.

### Naming Conventions

| Type        | Convention                    | Example                      |
|-------------|-------------------------------|------------------------------|
| Models      | PascalCase singular           | `Pessoa`, `Pedido`           |
| Controllers | PascalCase + Controller       | `PessoaController`           |
| Routes      | kebab-case                    | `/cadastros/pessoas`         |
| Components  | PascalCase                    | `AddModal.vue`               |
| Tables      | snake_case plural             | `pessoas`, `pedido_itens`    |
| API endpoints| snake_case                   | `/api/pessoas`               |

### Error Handling

**Backend**: Return JSON with `message` key: `response()->json(['message' => 'Error'], 422)`. Use `abort(403, 'Sem permissão.')` for auth failures.

**Frontend**: Catch errors, extract `err?.response?._data?.message`, show toast.

### Multi-Tenancy Pattern

- Backend: Scope all queries to company: `$company->pessoas()` not `Pessoa::all()`
- Frontend: `useApi()` auto-adds `X-Company-Id` header
- Current company in localStorage: `current_company_id`

### Import Organization

**Backend**:
```php
namespace App\Http\Controllers;

use App\Http\Requests\StoreOrcamentoRequest;
use App\Http\Resources\OrcamentoResource;
use App\Models\Orcamento;
use App\Services\OrcamentoService;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
```

**Frontend**:
```typescript
// Vue built-ins first
import { computed, h, ref } from 'vue'
// Type imports
import type { TableColumn } from '@nuxt/ui'
import type { Pessoa, PaginatedResponse } from '~/types'
// Component imports (auto-resolved)
import { UButton, UBadge } from '#components'
// Utility imports
import { getPaginationRowModel } from '@tanstack/table-core'
```

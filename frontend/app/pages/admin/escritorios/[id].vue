<script setup lang="ts">
import type { TableColumn, NavigationMenuItem } from '@nuxt/ui'
import type { Office, Company, Invoice, User } from '~/types'

definePageMeta({ middleware: 'admin' })

const route = useRoute()
const router = useRouter()

const officeId = computed(() => Number(route.params.id))
const officeUrl = computed(() => `/admin/offices/${officeId.value}`)

// officeId validation
if (isNaN(officeId.value)) {
  console.error('[Office details] Invalid officeId:', officeId.value)
}

const { data: office, status, refresh } = useApi<Office>(officeUrl, {
  lazy: false
})

const selectedTab = ref<'dados' | 'plano' | 'empresas' | 'faturas' | 'usuarios'>('dados')

const links = computed<NavigationMenuItem[][]>(() => [[
  {
    label: 'Dados Gerais',
    icon: 'i-lucide-building-2',
    active: selectedTab.value === 'dados',
    onSelect: (e?: Event) => {
      e?.preventDefault?.()
      selectedTab.value = 'dados'
    }
  },
  {
    label: 'Plano',
    icon: 'i-lucide-crown',
    active: selectedTab.value === 'plano',
    onSelect: (e?: Event) => {
      e?.preventDefault?.()
      selectedTab.value = 'plano'
    }
  },
  {
    label: 'Empresas',
    icon: 'i-lucide-building',
    active: selectedTab.value === 'empresas',
    onSelect: (e?: Event) => {
      e?.preventDefault?.()
      selectedTab.value = 'empresas'
    }
  },
  {
    label: 'Faturas',
    icon: 'i-lucide-file-text',
    active: selectedTab.value === 'faturas',
    onSelect: (e?: Event) => {
      e?.preventDefault?.()
      selectedTab.value = 'faturas'
    }
  },
  {
    label: 'Usuários',
    icon: 'i-lucide-users',
    active: selectedTab.value === 'usuarios',
    onSelect: (e?: Event) => {
      e?.preventDefault?.()
      selectedTab.value = 'usuarios'
    }
  }
]])

const companies = computed(() => office.value?.companies || [])
const invoices = computed(() => office.value?.invoices || [])
const users = computed(() => office.value?.users || [])

const companyColumns: TableColumn<Company>[] = [
  {
    accessorKey: 'razao_social',
    header: 'Razão Social',
    cell: ({ row }) => h('div', [
      h('p', { class: 'font-medium text-highlighted' }, row.original.razao_social),
      row.original.fantasia ? h('p', { class: 'text-sm text-muted' }, row.original.fantasia) : null
    ])
  },
  {
    accessorKey: 'cnpj',
    header: 'CNPJ',
    cell: ({ row }) => h('span', { class: 'font-mono text-sm' }, row.original.cnpj)
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    cell: ({ row }) => h('span', { class: row.original.is_active ? 'text-success' : 'text-error' }, row.original.is_active ? 'Ativo' : 'Inativo')
  }
]

const invoiceColumns: TableColumn<Invoice>[] = [
  {
    accessorKey: 'reference',
    header: 'Referência',
    cell: ({ row }) => h('span', { class: 'font-medium' }, String(row.original.reference))
  },
  {
    accessorKey: 'amount',
    header: 'Valor',
    cell: ({ row }) => h('span', { class: 'font-medium' }, `R$ ${Number(row.original.amount).toFixed(2).replace('.', ',')}`)
  },
  {
    accessorKey: 'due_at',
    header: 'Vencimento',
    cell: ({ row }) => h('span', {}, row.original.due_at ? new Date(row.original.due_at).toLocaleDateString('pt-BR') : '—')
  },
  {
    accessorKey: 'paid_at',
    header: 'Pago em',
    cell: ({ row }) => h('span', { class: row.original.paid_at ? 'text-success' : 'text-muted' }, row.original.paid_at ? new Date(row.original.paid_at).toLocaleDateString('pt-BR') : '—')
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const colors: Record<string, 'success' | 'warning' | 'error' | 'info'> = {
        paid: 'success',
        pending: 'warning',
        overdue: 'error',
        cancelled: 'info'
      }
      const labels: Record<string, string> = {
        paid: 'Pago',
        pending: 'Pendente',
        overdue: 'Vencido',
        cancelled: 'Cancelado'
      }
      return h('span', { class: `text-${colors[row.original.status] || 'info'}` }, labels[row.original.status] || row.original.status)
    }
  }
]

const userColumns: TableColumn<User>[] = [
  {
    accessorKey: 'name',
    header: 'Nome',
    cell: ({ row }) => h('div', [
      h('p', { class: 'font-medium text-highlighted' }, row.original.name),
      h('p', { class: 'text-sm text-muted' }, row.original.email)
    ])
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    cell: ({ row }) => h('span', { class: row.original.is_active ? 'text-success' : 'text-error' }, row.original.is_active ? 'Ativo' : 'Inativo')
  }
]
</script>

<template>
  <UDashboardPanel id="escritorio-detalhes">
    <template #header>
      <UDashboardNavbar :title="office?.name || 'Detalhes do Escritório'">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            color="neutral"
            variant="ghost"
            icon="i-lucide-list"
            :square="true"
            @click="router.push('/admin/escritorios')"
          />
          <AdminEscritoriosAssignPlanModal v-if="office" :office="office" @assigned="refresh()">
            <UButton label="Trocar Plano" icon="i-lucide-crown" color="neutral" />
          </AdminEscritoriosAssignPlanModal>
          <UsuariosAddModal v-if="office" :office-id="office.id" @created="refresh()" />
          <CompanySelector />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <UNavigationMenu :items="links" highlight class="-mx-1 flex-1" />
      </UDashboardToolbar>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <div v-else-if="!office" class="flex flex-col items-center justify-center py-12">
        <UIcon name="i-lucide-building-2" class="size-12 text-muted mb-4" />
        <p class="text-muted">
          Escritório não encontrado.
        </p>
      </div>

      <div v-else class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-5xl mx-auto">
        <!-- Tab: Dados Gerais -->
        <div v-show="selectedTab === 'dados'">
          <UPageCard variant="subtle">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <p class="text-sm text-muted mb-1">
                  Nome / Razão Social
                </p>
                <p class="font-medium">
                  {{ office.name }}
                </p>
              </div>
              <div>
                <p class="text-sm text-muted mb-1">
                  CNPJ
                </p>
                <p class="font-medium font-mono">
                  {{ office.cnpj || '—' }}
                </p>
              </div>
              <div>
                <p class="text-sm text-muted mb-1">
                  E-mail
                </p>
                <p class="font-medium">
                  {{ office.email || '—' }}
                </p>
              </div>
              <div>
                <p class="text-sm text-muted mb-1">
                  Telefone
                </p>
                <p class="font-medium">
                  {{ office.phone || '—' }}
                </p>
              </div>
              <div>
                <p class="text-sm text-muted mb-1">
                  Tipo
                </p>
                <p class="font-medium">
                  {{ office.type === 'contador' ? 'Contador' : 'Cliente Direto' }}
                </p>
              </div>
              <div>
                <p class="text-sm text-muted mb-1">
                  Status
                </p>
                <UBadge :color="office.is_active ? 'success' : 'error'" variant="subtle">
                  {{ office.is_active ? 'Ativo' : 'Inativo' }}
                </UBadge>
              </div>
              <div v-if="office.notes" class="md:col-span-2">
                <p class="text-sm text-muted mb-1">
                  Observações
                </p>
                <p class="font-medium">
                  {{ office.notes }}
                </p>
              </div>
            </div>
          </UPageCard>
        </div>

        <!-- Tab: Plano -->
        <div v-show="selectedTab === 'plano'">
          <div v-if="!office.subscription" class="flex flex-col items-center justify-center py-12">
            <UIcon name="i-lucide-crown" class="size-12 text-muted mb-4" />
            <p class="text-muted">
              Nenhum plano contratado.
            </p>
          </div>
          <UPageCard v-else variant="subtle">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <p class="text-sm text-muted mb-1">
                  Plano
                </p>
                <p class="font-medium text-lg">
                  {{ office.subscription.plan?.name }}
                </p>
              </div>
              <div>
                <p class="text-sm text-muted mb-1">
                  Valor
                </p>
                <p class="font-medium">
                  R$ {{ Number(office.subscription.plan?.price || 0).toFixed(2).replace('.', ',') }}/mês
                </p>
              </div>
              <div>
                <p class="text-sm text-muted mb-1">
                  Status da Assinatura
                </p>
                <UBadge
                  :color="({
                    active: 'success',
                    trial: 'warning',
                    cancelled: 'error',
                    expired: 'error'
                  } as Record<string, 'success' | 'warning' | 'error'>)[office.subscription.status]"
                  variant="subtle"
                >
                  {{
                    { active: 'Ativo', trial: 'Trial', cancelled: 'Cancelado', expired: 'Expirado' }[office.subscription.status]
                  }}
                </UBadge>
              </div>
              <div>
                <p class="text-sm text-muted mb-1">
                  Início
                </p>
                <p class="font-medium">
                  {{ new Date(office.subscription.starts_at).toLocaleDateString('pt-BR') }}
                </p>
              </div>
              <div>
                <p class="text-sm text-muted mb-1">
                  Vencimento
                </p>
                <p class="font-medium">
                  {{ office.subscription.ends_at ? new Date(office.subscription.ends_at).toLocaleDateString('pt-BR') : '—' }}
                </p>
              </div>
              <div>
                <p class="text-sm text-muted mb-1">
                  Limite de Empresas
                </p>
                <p class="font-medium">
                  {{ office.subscription.plan?.max_companies === 999 ? 'Ilimitado' : office.subscription.plan?.max_companies }}
                </p>
              </div>
              <div>
                <p class="text-sm text-muted mb-1">
                  Limite de NFes/Mês
                </p>
                <p class="font-medium">
                  {{ office.subscription.plan?.max_nfs_month === 0 ? 'Ilimitado' : office.subscription.plan?.max_nfs_month }}
                </p>
              </div>
            </div>
          </UPageCard>
        </div>

        <!-- Tab: Empresas -->
        <div v-show="selectedTab === 'empresas'">
          <div v-if="companies.length === 0" class="flex flex-col items-center justify-center py-12">
            <UIcon name="i-lucide-building" class="size-12 text-muted mb-4" />
            <p class="text-muted">
              Nenhuma empresa vinculada.
            </p>
          </div>
          <UTable
            v-else
            :data="companies"
            :columns="companyColumns"
            :ui="{
              base: 'table-fixed border-separate border-spacing-0',
              thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
              tbody: '[&>tr]:last:[&>td]:border-b-0',
              th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
              td: 'border-b border-default'
            }"
          >
            <template #empty>
              <div class="flex flex-col items-center justify-center py-8">
                <UIcon name="i-lucide-building" class="size-10 text-muted mb-2" />
                <p class="text-muted">
                  Nenhuma empresa vinculada
                </p>
              </div>
            </template>
          </UTable>
        </div>

        <!-- Tab: Faturas -->
        <div v-show="selectedTab === 'faturas'">
          <div v-if="invoices.length === 0" class="flex flex-col items-center justify-center py-12">
            <UIcon name="i-lucide-file-text" class="size-12 text-muted mb-4" />
            <p class="text-muted">
              Nenhuma fatura encontrada.
            </p>
          </div>
          <UTable
            v-else
            :data="invoices"
            :columns="invoiceColumns"
            :ui="{
              base: 'table-fixed border-separate border-spacing-0',
              thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
              tbody: '[&>tr]:last:[&>td]:border-b-0',
              th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
              td: 'border-b border-default'
            }"
          >
            <template #empty>
              <div class="flex flex-col items-center justify-center py-8">
                <UIcon name="i-lucide-file-text" class="size-10 text-muted mb-2" />
                <p class="text-muted">
                  Nenhuma fatura
                </p>
              </div>
            </template>
          </UTable>
        </div>

        <!-- Tab: Usuários -->
        <div v-show="selectedTab === 'usuarios'">
          <div v-if="users.length === 0" class="flex flex-col items-center justify-center py-12">
            <UIcon name="i-lucide-users" class="size-12 text-muted mb-4" />
            <p class="text-muted">
              Nenhum usuário vinculado.
            </p>
          </div>
          <UTable
            v-else
            :data="users"
            :columns="userColumns"
            :ui="{
              base: 'table-fixed border-separate border-spacing-0',
              thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
              tbody: '[&>tr]:last:[&>td]:border-b-0',
              th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
              td: 'border-b border-default'
            }"
          >
            <template #empty>
              <div class="flex flex-col items-center justify-center py-8">
                <UIcon name="i-lucide-users" class="size-10 text-muted mb-2" />
                <p class="text-muted">
                  Nenhum usuário
                </p>
              </div>
            </template>
          </UTable>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>

<script setup lang="ts">
import type { TableColumn, NavigationMenuItem } from '@nuxt/ui'
import type { Office, Company, Invoice, User } from '~/types'

definePageMeta({ middleware: 'admin' })

const route = useRoute()
const router = useRouter()
const toast = useToast()

const officeId = computed(() => Number(route.params.id))
const officeUrl = computed(() => `/admin/offices/${officeId.value}`)

if (isNaN(officeId.value)) {
  console.error('[Office details] Invalid officeId:', officeId.value)
}

const { data: office, status, refresh } = useApi<Office>(officeUrl, {
  lazy: false
})

const selectedTab = ref<'empresas' | 'faturas' | 'usuarios'>('empresas')

const showAssignPlanModal = ref(false)
const showEditPlanModal = ref(false)
const showDeletePlanModal = ref(false)
const loading = ref(false)

const { del: apiDelete } = useApiMutation()

const links = computed<NavigationMenuItem[][]>(() => [[
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

const statCards = computed(() => [
  {
    label: 'Empresas',
    value: companies.value.length,
    icon: 'i-lucide-building',
    color: 'text-primary'
  },
  {
    label: 'Usuários',
    value: users.value.length,
    icon: 'i-lucide-users',
    color: 'text-info'
  },
  {
    label: 'Plano',
    value: office.value?.subscription?.plan?.name || '—',
    icon: 'i-lucide-crown',
    color: 'text-warning'
  },
  {
    label: 'Valor',
    value: `R$ ${Number(office.value?.subscription?.plan?.price || 0).toFixed(2).replace('.', ',')}/mês`,
    icon: 'i-lucide-dollar-sign',
    color: 'text-success'
  }
])

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
    accessorKey: 'ambiente',
    header: 'Ambiente',
    cell: ({ row }) => {
      const labels = { producao: 'Prod', homologacao: 'Homol' }
      const colors = { producao: 'success', homologacao: 'warning' }
      return h('span', { class: `text-${colors[row.original.ambiente as keyof typeof colors] || 'info'}` }, labels[row.original.ambiente as keyof typeof labels] || row.original.ambiente)
    }
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

async function handleDeletePlan() {
  if (!office.value?.subscription?.plan?.id) return
  loading.value = true
  try {
    await apiDelete(`/admin/offices/${office.value.id}/plan`)
    toast.add({ title: 'Plano excluído com sucesso', color: 'success' })
    refresh()
  } catch (error) {
    console.error('Erro ao excluir plano:', error)
    toast.add({ title: 'Erro ao excluir plano', description: 'Ocorreu um erro inesperado.', color: 'error' })
  } finally {
    loading.value = false
    showDeletePlanModal.value = false
  }
}
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
          <CompanySelector />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <UNavigationMenu :items="links" highlight class="-mx-1 flex-1" />
        <template #right>
          <UsuariosAddModal v-if="office" :office-id="office.id" @created="refresh()" />
        </template>
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

      <div v-else class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <UCard
            v-for="card in statCards"
            :key="card.label"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-muted">
                  {{ card.label }}
                </p>
                <p class="text-2xl font-bold mt-1">
                  {{ card.value }}
                </p>
              </div>
              <UIcon :name="card.icon" :class="['size-8', card.color]" />
            </div>
          </UCard>
        </div>

        <!-- Info Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Card: Dados do Escritório -->
          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-semibold text-lg">
                    Dados do Escritório
                  </p>
                </div>
                <UBadge :color="office.is_active ? 'success' : 'error'" variant="subtle">
                  {{ office.is_active ? 'Ativo' : 'Inativo' }}
                </UBadge>
              </div>
            </template>
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-muted mb-1">
                    Nome
                  </p>
                  <p class="font-medium">
                    {{ office.name }}
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
              </div>
              <div class="grid grid-cols-2 gap-4">
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
                    Telefone
                  </p>
                  <p class="font-medium">
                    {{ office.phone || '—' }}
                  </p>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-muted mb-1">
                    E-mail
                  </p>
                  <p class="font-medium truncate" :title="office.email || undefined">
                    {{ office.email || '—' }}
                  </p>
                </div>
                <div v-if="office.is_reseller">
                  <p class="text-sm text-muted mb-1">
                    Revendedor
                  </p>
                  <UChip color="secondary" size="sm">
                    Sim
                  </UChip>
                </div>
              </div>
              <div v-if="office.notes">
                <USeparator />
                <p class="text-sm text-muted mb-1">
                  Observações
                </p>
                <p class="font-medium text-sm">
                  {{ office.notes }}
                </p>
              </div>
            </div>
          </UCard>

          <!-- Card: Dados do Plano -->
          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-semibold text-lg">
                    Dados do Plano
                  </p>
                </div>
                <UBadge
                  v-if="office.subscription"
                  :color="({
                    active: 'success',
                    trial: 'warning',
                    cancelled: 'error',
                    expired: 'error'
                  } as Record<string, 'success' | 'warning' | 'error' | 'info'>)[office.subscription.status] || 'info'"
                  variant="subtle"
                >
                  {{
                    { active: 'Ativo', trial: 'Trial', cancelled: 'Cancelado', expired: 'Expirado' }[office.subscription?.status] || 'Sem plano'
                  }}
                </UBadge>
              </div>
            </template>
            <div v-if="!office.subscription" class="flex flex-col items-center justify-center py-8">
              <UIcon name="i-lucide-crown" class="size-10 text-muted mb-3" />
              <p class="text-muted text-sm">
                Nenhum plano contratado.
              </p>
            </div>
            <div v-else class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
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
              </div>
              <div class="grid grid-cols-2 gap-4">
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
              </div>
              <USeparator />
              <div class="grid grid-cols-2 gap-4">
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
            </div>

            <template #footer>
              <div v-if="office.subscription" class="flex gap-2">
                <UButton
                  label="Editar Plano"
                  icon="i-lucide-pencil"
                  variant="outline"
                  class="flex-1"
                  @click="showEditPlanModal = true"
                />
                <UButton
                  label="Excluir Plano"
                  icon="i-lucide-trash"
                  color="error"
                  variant="outline"
                  class="flex-1"
                  @click="showDeletePlanModal = true"
                />
              </div>
              <UButton
                v-else
                label="Atribuir Plano"
                icon="i-lucide-crown"
                class="w-full"
                @click="showAssignPlanModal = true"
              />
            </template>
          </UCard>
        </div>

        <!-- Tab Content -->
        <div v-if="selectedTab === 'empresas'">
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
          />
        </div>

        <div v-if="selectedTab === 'faturas'">
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
          />
        </div>

        <div v-if="selectedTab === 'usuarios'">
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
          />
        </div>
      </div>
    </template>
  </UDashboardPanel>

  <UModal
    v-model:open="showDeletePlanModal"
    title="Excluir plano"
  >
    <template #body>
      <p class="text-muted">
        Deseja excluir o plano <strong>{{ office?.subscription?.plan?.name }}</strong> do escritório <strong>{{ office?.name }}</strong>?
      </p>
    </template>
    <template #footer="{ close }">
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="close"
      />
      <UButton
        label="Excluir"
        color="error"
        :loading="loading"
        @click="handleDeletePlan"
      />
    </template>
  </UModal>

  <AdminEscritoriosAssignPlanModal
    v-if="office"
    v-model:open="showAssignPlanModal"
    :office="office"
    @assigned="refresh()"
  />

  <AdminEscritoriosEditPlanModal
    v-if="office"
    v-model:open="showEditPlanModal"
    :office="office"
    @updated="refresh()"
  />
</template>

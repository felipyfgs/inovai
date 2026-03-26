<script setup lang="ts">
import type { TableColumn, NavigationMenuItem } from '@nuxt/ui'
import type { Office, Company, Invoice, User } from '~/types'

const UBadge = resolveComponent('UBadge')
const UButton = resolveComponent('UButton')
const UDropdownMenu = resolveComponent('UDropdownMenu')

definePageMeta({ middleware: 'admin' })

const route = useRoute()
const router = useRouter()

const officeId = computed(() => Number(route.params.id))
const officeUrl = computed(() => `/admin/offices/${officeId.value}`)

if (isNaN(officeId.value)) {
  console.error('[Office details] Invalid officeId:', officeId.value)
}

const { data: office, status, refresh } = useApi<Office>(officeUrl, {
  lazy: false
})

const selectedTab = ref('escritorio')

const showAssignPlanModal = ref(false)
const showEditPlanModal = ref(false)
const deletingPlan = ref(false)

const editingUser = ref<User | null>(null)

const companies = computed(() => office.value?.companies || [])
const invoices = computed(() => office.value?.invoices || [])
const users = computed(() => office.value?.users || [])

const subscriptionStatus = computed(() => {
  const s = office.value?.subscription?.status
  const colors: Record<string, 'success' | 'warning' | 'error' | 'info'> = {
    active: 'success',
    trial: 'warning',
    cancelled: 'error',
    expired: 'error'
  }
  const labels: Record<string, string> = {
    active: 'Ativo',
    trial: 'Trial',
    cancelled: 'Cancelado',
    expired: 'Expirado'
  }
  return {
    label: labels[s || ''] || 'Sem plano',
    color: (colors[s || ''] || 'info') as 'success' | 'warning' | 'error' | 'info'
  }
})

const tableUi = {
  base: 'table-fixed border-separate border-spacing-0',
  thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
  tbody: '[&>tr]:last:[&>td]:border-b-0',
  th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
  td: 'border-b border-default'
}

const companySearch = ref('')
const companyAmbienteFilter = ref('all')

const filteredCompanies = computed(() => {
  let list = companies.value
  if (companySearch.value) {
    const q = companySearch.value.toLowerCase()
    list = list.filter(c => c.razao_social.toLowerCase().includes(q) || c.fantasia?.toLowerCase().includes(q) || c.cnpj.includes(q))
  }
  if (companyAmbienteFilter.value !== 'all') {
    list = list.filter(c => c.ambiente === companyAmbienteFilter.value)
  }
  return list
})

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
      return h(UBadge, { variant: 'subtle', color: (colors[row.original.ambiente as keyof typeof colors] || 'info') as 'success' | 'warning' | 'info' }, () => labels[row.original.ambiente as keyof typeof labels] || row.original.ambiente)
    }
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    cell: ({ row }) => h(UBadge, { variant: 'subtle', color: row.original.is_active ? 'success' : 'error' }, () => row.original.is_active ? 'Ativo' : 'Inativo')
  }
]

const invoiceStatusFilter = ref('all')
const { put } = useApiMutation()

async function markAsPaid(invoice: Invoice) {
  try {
    await put(`/admin/invoices/${invoice.id}`, { status: 'paid' })
    useAppToast().success('Fatura marcada como paga')
    refresh()
  } catch {
    useAppToast().error('Erro ao atualizar fatura')
  }
}

const invoiceColumns: TableColumn<Invoice>[] = [
  {
    accessorKey: 'reference',
    header: 'Referência',
    cell: ({ row }) => h('span', { class: 'font-medium' }, String(row.original.reference))
  },
  {
    accessorKey: 'amount',
    header: 'Valor',
    cell: ({ row }) => h('span', { class: 'font-medium' }, formatCurrency(row.original.amount))
  },
  {
    accessorKey: 'due_at',
    header: 'Vencimento',
    cell: ({ row }) => row.original.due_at ? new Date(row.original.due_at).toLocaleDateString('pt-BR') : '—'
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
      return h(UBadge, { variant: 'subtle', color: colors[row.original.status] || 'info' }, () => labels[row.original.status] || row.original.status)
    }
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      const items = [
        { type: 'label' as const, label: 'Ações' },
        ...(row.original.status === 'pending' || row.original.status === 'overdue'
          ? [{
              label: 'Marcar como pago',
              icon: 'i-lucide-check-circle',
              onSelect: () => markAsPaid(row.original)
            }]
          : [])
      ]
      if (items.length <= 1) return null
      return h('div', { class: 'text-right' }, h(UDropdownMenu, {
        content: { align: 'end' },
        items
      }, () => h(UButton, { icon: 'i-lucide-ellipsis-vertical', color: 'neutral', variant: 'ghost', class: 'ml-auto' })))
    }
  }
]

const userSearch = ref('')

const filteredUsers = computed(() => {
  if (!userSearch.value) return users.value
  const q = userSearch.value.toLowerCase()
  return users.value.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q))
})

async function toggleUserActive(user: User) {
  const { patch } = useApiMutation()
  try {
    await patch(`/users/${user.id}`, { is_active: !user.is_active })
    useAppToast().success(user.is_active ? 'Usuário desativado' : 'Usuário ativado')
    refresh()
  } catch {
    useAppToast().error('Erro ao alterar status do usuário')
  }
}

async function sendResetLink(user: User) {
  const { post } = useApiMutation()
  try {
    await post('/forgot-password', { email: user.email })
    useAppToast().success('Link de recuperação enviado')
  } catch {
    useAppToast().error('Erro ao enviar link de recuperação')
  }
}

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
    cell: ({ row }) => h(UBadge, { variant: 'subtle', color: row.original.is_active ? 'success' : 'error' }, () => row.original.is_active ? 'Ativo' : 'Inativo')
  },
  {
    id: 'actions',
    cell: ({ row }) => h('div', { class: 'text-right' }, h(UDropdownMenu, {
      content: { align: 'end' },
      items: [
        { type: 'label' as const, label: 'Ações' },
        {
          label: 'Editar',
          icon: 'i-lucide-pencil',
          onSelect: () => { editingUser.value = row.original }
        },
        { type: 'separator' as const },
        {
          label: row.original.is_active ? 'Desativar' : 'Ativar',
          icon: row.original.is_active ? 'i-lucide-user-x' : 'i-lucide-user-check',
          onSelect: () => toggleUserActive(row.original)
        },
        { type: 'separator' as const },
        {
          label: 'Enviar link de recuperação',
          icon: 'i-lucide-key',
          onSelect: () => sendResetLink(row.original)
        }
      ]
    }, () => h(UButton, { icon: 'i-lucide-ellipsis-vertical', color: 'neutral', variant: 'ghost', class: 'ml-auto' })))
  }
]

const tabItems = computed<NavigationMenuItem[][]>(() => [[
  {
    label: 'Escritório',
    icon: 'i-lucide-building-2',
    active: selectedTab.value === 'escritorio',
    onSelect: (e?: Event) => {
      e?.preventDefault?.()
      selectedTab.value = 'escritorio'
    }
  },
  {
    label: `Empresas (${companies.value.length})`,
    icon: 'i-lucide-building',
    active: selectedTab.value === 'empresas',
    onSelect: (e?: Event) => {
      e?.preventDefault?.()
      selectedTab.value = 'empresas'
    }
  },
  {
    label: `Faturas (${invoices.value.length})`,
    icon: 'i-lucide-file-text',
    active: selectedTab.value === 'faturas',
    onSelect: (e?: Event) => {
      e?.preventDefault?.()
      selectedTab.value = 'faturas'
    }
  },
  {
    label: `Usuários (${users.value.length})`,
    icon: 'i-lucide-users',
    active: selectedTab.value === 'usuarios',
    onSelect: (e?: Event) => {
      e?.preventDefault?.()
      selectedTab.value = 'usuarios'
    }
  }
]])
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
        <UNavigationMenu :items="tabItems" highlight class="-mx-1 flex-1" />
        <template #right>
          <UsuariosAddModal v-if="office && selectedTab === 'usuarios'" :office-id="office.id" @created="refresh()" />
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
        <!-- Tab: Escritório -->
        <div v-if="selectedTab === 'escritorio'">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <UCard>
              <template #header>
                <div class="flex items-center justify-between">
                  <p class="font-semibold">
                    Dados do Escritório
                  </p>
                  <UBadge :color="office.is_active ? 'success' : 'error'" variant="subtle">
                    {{ office.is_active ? 'Ativo' : 'Inativo' }}
                  </UBadge>
                </div>
              </template>
              <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <USeparator />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                      Insc. Estadual
                    </p>
                    <p class="font-medium">
                      {{ office.ie || '—' }}
                    </p>
                  </div>
                </div>
                <USeparator />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <p class="text-sm text-muted mb-1">
                      E-mail
                    </p>
                    <p class="font-medium truncate" :title="office.email || undefined">
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
                </div>
                <div v-if="office.logradouro || office.municipio">
                  <USeparator />
                  <p class="text-sm text-muted mb-1">
                    Endereço
                  </p>
                  <p class="font-medium text-sm">
                    {{ [office.logradouro, office.numero, office.complemento].filter(Boolean).join(', ') || '—' }}
                  </p>
                  <p class="text-sm text-muted">
                    {{ [office.bairro, office.municipio, office.uf, office.cep].filter(Boolean).join(' — ') }}
                  </p>
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

            <UCard>
              <template #header>
                <div class="flex items-center justify-between">
                  <p class="font-semibold">
                    Dados do Plano
                  </p>
                  <UBadge
                    v-if="office.subscription"
                    :color="subscriptionStatus.color"
                    variant="subtle"
                  >
                    {{ subscriptionStatus.label }}
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
                <USeparator />
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
                    @click="deletingPlan = true"
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
        </div>

        <!-- Tab: Empresas -->
        <div v-if="selectedTab === 'empresas'">
          <UDashboardToolbar class="mb-4">
            <UInput
              v-model="companySearch"
              class="max-w-sm"
              icon="i-lucide-search"
              placeholder="Buscar por razão social, CNPJ..."
            />
            <template #right>
              <USelect
                v-model="companyAmbienteFilter"
                :items="[
                  { label: 'Todos', value: 'all' },
                  { label: 'Produção', value: 'producao' },
                  { label: 'Homologação', value: 'homologacao' }
                ]"
                class="min-w-36"
              />
            </template>
          </UDashboardToolbar>

          <div v-if="filteredCompanies.length === 0" class="flex flex-col items-center justify-center py-12">
            <UIcon name="i-lucide-building" class="size-12 text-muted mb-4" />
            <p class="text-muted">
              Nenhuma empresa vinculada.
            </p>
          </div>
          <UTable
            v-else
            :data="filteredCompanies"
            :columns="companyColumns"
            :ui="tableUi"
          />
        </div>

        <!-- Tab: Faturas -->
        <div v-if="selectedTab === 'faturas'">
          <UDashboardToolbar class="mb-4">
            <template #right>
              <USelect
                v-model="invoiceStatusFilter"
                :items="[
                  { label: 'Todos', value: 'all' },
                  { label: 'Pendentes', value: 'pending' },
                  { label: 'Pagos', value: 'paid' },
                  { label: 'Vencidos', value: 'overdue' },
                  { label: 'Cancelados', value: 'cancelled' }
                ]"
                class="min-w-36"
              />
            </template>
          </UDashboardToolbar>

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
            :ui="tableUi"
          />
        </div>

        <!-- Tab: Usuários -->
        <div v-if="selectedTab === 'usuarios'">
          <UDashboardToolbar class="mb-4">
            <UInput
              v-model="userSearch"
              class="max-w-sm"
              icon="i-lucide-search"
              placeholder="Buscar por nome, e-mail..."
            />
          </UDashboardToolbar>

          <div v-if="filteredUsers.length === 0" class="flex flex-col items-center justify-center py-12">
            <UIcon name="i-lucide-users" class="size-12 text-muted mb-4" />
            <p class="text-muted">
              Nenhum usuário vinculado.
            </p>
          </div>
          <UTable
            v-else
            :data="filteredUsers"
            :columns="userColumns"
            :ui="tableUi"
          />
        </div>
      </div>
    </template>
  </UDashboardPanel>

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

  <AdminEscritoriosDeletePlanModal
    v-if="deletingPlan && office"
    :office="office"
    @deleted="() => { deletingPlan = false; refresh() }"
  />

  <UsuariosEditModal
    v-if="editingUser"
    :user="editingUser"
    @updated="() => { editingUser = null; refresh() }"
  />
</template>

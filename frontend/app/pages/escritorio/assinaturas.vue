<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { Row } from '@tanstack/table-core'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Company, PaginatedResponse } from '~/types'

const UBadge = resolveComponent('UBadge')
const UButton = resolveComponent('UButton')
const UDropdownMenu = resolveComponent('UDropdownMenu')

definePageMeta({ middleware: 'escritorio' })

const table = useTemplateRef('table')
const search = ref('')
const statusFilter = ref('all')
const columnVisibility = ref()
const rowSelection = ref({})
const pagination = ref({ pageIndex: 0, pageSize: 20 })

const assigningCompany = ref<Company | null>(null)
const cancellingCompany = ref<Company | null>(null)

const queryParams = computed(() => ({
  search: search.value || undefined,
  status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
  per_page: pagination.value.pageSize
}))

const { data, status, refresh } = useApi<PaginatedResponse<Company>>('/company-subscriptions', {
  lazy: true,
  query: queryParams
})

const companies = computed(() => data.value?.data || [])

const statusConfig: Record<string, { label: string, color: 'success' | 'warning' | 'error' | 'neutral' }> = {
  active: { label: 'Ativo', color: 'success' },
  trial: { label: 'Trial', color: 'warning' },
  cancelled: { label: 'Cancelado', color: 'neutral' },
  expired: { label: 'Expirado', color: 'error' },
  none: { label: 'Sem plano', color: 'neutral' }
}

const defaultStatusConfig = statusConfig.none

function getRowItems(row: Row<Company>) {
  const sub = row.original.subscription
  return [
    { type: 'label' as const, label: 'Ações' },
    {
      label: sub ? 'Trocar Plano' : 'Atribuir Plano',
      icon: 'i-lucide-credit-card',
      onSelect() { assigningCompany.value = row.original }
    },
    ...(sub && sub.status === 'active'
      ? [{
          label: 'Cancelar Assinatura',
          icon: 'i-lucide-x-circle',
          color: 'error' as const,
          onSelect() { cancellingCompany.value = row.original }
        }]
      : [])
  ]
}

const columns: TableColumn<Company>[] = [
  {
    accessorKey: 'razao_social',
    header: 'Empresa',
    cell: ({ row }) => h('div', [
      h('p', { class: 'font-medium text-highlighted' }, row.original.fantasia || row.original.razao_social),
      h('p', { class: 'text-sm text-muted' }, row.original.cnpj || '—')
    ])
  },
  {
    id: 'plano',
    header: 'Plano',
    cell: ({ row }) => {
      const plan = row.original.subscription?.office_plan || row.original.office_plan
      if (!plan) return h('span', { class: 'text-muted' }, '—')
      return h(UBadge, { variant: 'subtle', color: 'primary' }, () => plan.name)
    }
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    cell: ({ row }) => {
      const sub = row.original.subscription
      if (!sub) return h(UBadge, { variant: 'outline', color: 'neutral' }, () => 'Sem plano')
      const cfg = statusConfig[sub.status] ?? defaultStatusConfig!
      return h(UBadge, { variant: 'subtle', color: cfg.color }, () => cfg.label)
    }
  },
  {
    id: 'valor',
    header: 'Valor',
    cell: ({ row }) => {
      const plan = row.original.subscription?.office_plan || row.original.office_plan
      if (!plan) return h('span', { class: 'text-muted' }, '—')
      return h('span', { class: 'font-medium' }, formatCurrency(plan.price))
    }
  },
  {
    id: 'actions',
    cell: ({ row }) => h('div', { class: 'text-right' }, h(UDropdownMenu, {
      content: { align: 'end' },
      items: getRowItems(row)
    }, () => h(UButton, { icon: 'i-lucide-ellipsis-vertical', color: 'neutral', variant: 'ghost', class: 'ml-auto' })))
  }
]

async function cancelSubscription() {
  if (!cancellingCompany.value) return
  try {
    const { del } = useApiMutation()
    await del(`/companies/${cancellingCompany.value.id}/subscription`)
    useAppToast().success('Assinatura cancelada')
    cancellingCompany.value = null
    refresh()
  } catch {
    useAppToast().error('Erro ao cancelar assinatura')
  }
}
</script>

<template>
  <UDashboardPanel id="escritorio-assinaturas">
    <template #header>
      <UDashboardNavbar title="Assinaturas">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Buscar por nome, CNPJ..."
        />
        <template #right>
          <div class="flex flex-wrap items-center gap-1.5">
            <USelect
              v-model="statusFilter"
              :items="[
                { label: 'Todos', value: 'all' },
                { label: 'Ativos', value: 'active' },
                { label: 'Sem plano', value: 'none' },
                { label: 'Cancelados', value: 'cancelled' },
                { label: 'Expirados', value: 'expired' }
              ]"
              class="min-w-36"
            />
            <UDropdownMenu
              :items="table?.tableApi?.getAllColumns().filter((column: any) => column.getCanHide()).map((column: any) => ({
                label: upperFirst(column.id),
                type: 'checkbox' as const,
                checked: column.getIsVisible(),
                onUpdateChecked(checked: boolean) {
                  table?.tableApi?.getColumn(column.id)?.toggleVisibility(!!checked)
                },
                onSelect(e?: Event) {
                  e?.preventDefault()
                }
              }))"
              :content="{ align: 'end' }"
            >
              <UButton
                label="Exibir"
                color="neutral"
                variant="outline"
                trailing-icon="i-lucide-settings-2"
              />
            </UDropdownMenu>
          </div>
        </template>
      </UDashboardToolbar>
    </template>

    <template #body>
      <UTable
        ref="table"
        v-model:column-visibility="columnVisibility"
        v-model:row-selection="rowSelection"
        v-model:pagination="pagination"
        :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
        class="shrink-0"
        :data="companies"
        :columns="columns"
        :loading="status === 'pending'"
        :ui="{
          base: 'table-fixed border-separate border-spacing-0',
          thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
          tbody: '[&>tr]:last:[&>td]:border-b-0',
          th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
          td: 'border-b border-default',
          separator: 'h-0'
        }"
      />

      <div class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-auto">
        <div class="text-sm text-muted">
          {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} de
          {{ table?.tableApi?.getFilteredRowModel().rows.length || 0 }} empresa(s) selecionada(s).
        </div>
        <UPagination
          :default-page="(table?.tableApi?.getState().pagination.pageIndex || 0) + 1"
          :items-per-page="table?.tableApi?.getState().pagination.pageSize"
          :total="table?.tableApi?.getFilteredRowModel().rows.length"
          @update:page="(p: number) => table?.tableApi?.setPageIndex(p - 1)"
        />
      </div>
    </template>
  </UDashboardPanel>

  <AsnaturasAssignPlanModal
    v-if="assigningCompany"
    :company="assigningCompany"
    @assigned="() => { assigningCompany = null; refresh() }"
  />

  <UModal
    :open="!!cancellingCompany"
    title="Cancelar Assinatura"
    description="Tem certeza que deseja cancelar a assinatura desta empresa?"
    :ui="{ footer: 'justify-end' }"
    @update:open="(v: boolean) => !v && (cancellingCompany = null)"
  >
    <template #body>
      <p>Cancelar a assinatura de <strong>{{ cancellingCompany?.fantasia || cancellingCompany?.razao_social }}</strong>.</p>
    </template>
    <template #footer>
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="cancellingCompany = null"
      />
      <UButton label="Confirmar Cancelamento" color="error" @click="cancelSubscription" />
    </template>
  </UModal>
</template>

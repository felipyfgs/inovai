<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { Row } from '@tanstack/table-core'
import { UBadge, UButton, UDropdownMenu } from '#components'
import type { Plan } from '~/types'

definePageMeta({ middleware: 'admin' })

const toast = useToast()

const { data, refresh } = useApi<Plan[]>('/admin/plans', { lazy: true })

const plans = computed(() => data.value || [])

const editingPlan = ref<Plan | null>(null)
const deletingPlan = ref<Plan | null>(null)

function getRowItems(row: Row<Plan>) {
  return [
    { type: 'label' as const, label: 'Ações' },
    {
      label: 'Editar',
      icon: 'i-lucide-pencil',
      onSelect() { editingPlan.value = row.original }
    },
    { type: 'separator' as const },
    {
      label: 'Excluir',
      icon: 'i-lucide-trash',
      color: 'error' as const,
      onSelect() { deletingPlan.value = row.original }
    }
  ]
}

const _columns: TableColumn<Plan>[] = [
  {
    accessorKey: 'name',
    header: 'Plano',
    cell: ({ row }) => h('div', [
      h('p', { class: 'font-medium text-highlighted' }, row.original.name),
      row.original.description ? h('p', { class: 'text-sm text-muted' }, row.original.description) : null
    ])
  },
  {
    accessorKey: 'price',
    header: 'Preço',
    cell: ({ row }) => h('span', { class: 'font-medium' }, `R$ ${Number(row.original.price).toFixed(2).replace('.', ',')}`)
  },
  {
    accessorKey: 'max_companies',
    header: 'Empresas',
    cell: ({ row }) => h('span', {}, row.original.max_companies === 999 ? 'Ilimitado' : row.original.max_companies)
  },
  {
    accessorKey: 'max_nfs_month',
    header: 'NFes/Mês',
    cell: ({ row }) => h('span', {}, row.original.max_nfs_month === 0 ? 'Ilimitado' : row.original.max_nfs_month)
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
      items: getRowItems(row)
    }, () => h(UButton, { icon: 'i-lucide-ellipsis-vertical', color: 'neutral', variant: 'ghost', class: 'ml-auto' })))
  }
]

async function handleDelete() {
  if (!deletingPlan.value) return
  const { del } = useApiMutation()
  try {
    await del(`/admin/plans/${deletingPlan.value.id}`)
    toast.add({ title: 'Plano removido com sucesso', color: 'success' })
    deletingPlan.value = null
    refresh()
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao remover', color: 'error' })
  }
}
</script>

<template>
  <UDashboardPanel id="admin-planos">
    <template #header>
      <UDashboardNavbar title="Planos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <AdminPlanosAddModal @created="refresh()" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div v-if="!data" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <div v-else-if="plans.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
        <UIcon name="i-lucide-package" class="size-12 text-muted mb-4" />
        <p class="text-muted">
          Nenhum plano cadastrado.
        </p>
        <p class="text-sm text-muted">
          Crie um plano para começar.
        </p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <UCard v-for="plan in plans" :key="plan.id">
          <template #header>
            <div class="flex items-center justify-between">
              <div>
                <p class="font-medium">
                  {{ plan.name }}
                </p>
                <p class="text-sm text-muted">
                  {{ plan.description || '—' }}
                </p>
              </div>
              <UBadge :color="plan.is_active ? 'success' : 'error'" variant="subtle">
                {{ plan.is_active ? 'Ativo' : 'Inativo' }}
              </UBadge>
            </div>
          </template>

          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-muted">Preço</span>
              <span class="font-medium">R$ {{ Number(plan.price).toFixed(2).replace('.', ',') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted">Empresas</span>
              <span class="font-medium">{{ plan.max_companies === 999 ? 'Ilimitado' : plan.max_companies }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted">NFes/Mês</span>
              <span class="font-medium">{{ plan.max_nfs_month === 0 ? 'Ilimitado' : plan.max_nfs_month }}</span>
            </div>
          </div>

          <template #footer>
            <div class="flex gap-2">
              <AdminPlanosEditModal :plan="plan" @updated="refresh()">
                <UButton
                  variant="outline"
                  size="sm"
                  icon="i-lucide-pencil"
                  class="flex-1"
                />
              </AdminPlanosEditModal>
              <UButton
                variant="outline"
                color="error"
                size="sm"
                icon="i-lucide-trash"
                @click="deletingPlan = plan"
              />
            </div>
          </template>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>

  <UModal
    :open="!!deletingPlan"
    title="Confirmar exclusão"
    description="Esta ação não pode ser desfeita."
    @update:open="(v) => { if (!v) deletingPlan = null }"
  >
    <template #body>
      <p>
        Deseja excluir o plano <strong>{{ deletingPlan?.name }}</strong>? Esta ação não pode ser desfeita.
      </p>
    </template>
    <template #footer="{ close }">
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="close(); deletingPlan = null"
      />
      <UButton label="Excluir" color="error" @click="handleDelete" />
    </template>
  </UModal>
</template>

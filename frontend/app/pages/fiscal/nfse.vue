<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { Nfse, PaginatedResponse } from '~/types'
import { h, ref } from 'vue'
import { UBadge, UButton, UDropdownMenu } from '#components'

const { data, status, refresh } = useApi<PaginatedResponse<Nfse>>('/nfses', {
  lazy: true
})

const addOpen = ref(false)
const deletingNfse = ref<Nfse | null>(null)
const cancelingNfse = ref<Nfse | null>(null)
const cancelJustificativa = ref('')

const nfses = computed(() => data.value?.data || [])

const statusColors: Record<string, 'neutral' | 'warning' | 'success' | 'error'> = {
  rascunho: 'neutral',
  assinatura: 'warning',
  transmitida: 'warning',
  autorizada: 'success',
  rejeitada: 'error',
  cancelada: 'error'
}

const statusLabels: Record<string, string> = {
  rascunho: 'Rascunho',
  assinatura: 'Assinando',
  transmitida: 'Transmitida',
  autorizada: 'Autorizada',
  rejeitada: 'Rejeitada',
  cancelada: 'Cancelada'
}

const columns: TableColumn<Nfse>[] = [
  {
    accessorKey: 'data_emissao',
    header: 'Data Emissão',
    cell: ({ row }) => new Date(row.original.data_emissao).toLocaleDateString('pt-BR')
  },
  {
    accessorKey: 'numero',
    header: 'Número',
    cell: ({ row }) => `${row.original.serie}/${row.original.numero}`
  },
  {
    accessorKey: 'tomador_nome',
    header: 'Tomador',
    cell: ({ row }) => row.original.tomador_nome ?? '-'
  },
  {
    accessorKey: 'codigo_verificacao',
    header: 'Código Verificação',
    cell: ({ row }) => row.original.codigo_verificacao ?? '-'
  },
  {
    accessorKey: 'valor_total',
    header: 'Valor',
    cell: ({ row }) => formatCurrency(Number(row.original.valor_total))
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => h(UBadge, {
      color: statusColors[row.original.status] ?? 'neutral',
      variant: 'subtle',
      label: statusLabels[row.original.status] ?? row.original.status
    })
  },
  {
    id: 'actions',
    header: '',
    cell: ({ row }) => {
      const nfse = row.original
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const items: any[] = [
        { type: 'label' as const, label: 'Ações' }
      ]

      if (nfse.status === 'rascunho') {
        items.push({
          label: 'Editar',
          icon: 'i-lucide-pencil',
          onSelect: () => { }
        })
      }

      if (nfse.status === 'autorizada') {
        items.push({
          label: 'Cancelar',
          icon: 'i-lucide-x',
          color: 'error' as const,
          onSelect: () => {
            cancelingNfse.value = nfse
          }
        })
      }

      if (nfse.status === 'rascunho') {
        items.push({ type: 'separator' as const })
        items.push({
          label: 'Excluir',
          icon: 'i-lucide-trash',
          color: 'error' as const,
          onSelect: () => {
            deletingNfse.value = nfse
          }
        })
      }

      return h(UDropdownMenu, { items }, {
        default: () => h(UButton, { icon: 'i-lucide-ellipsis', variant: 'ghost', size: 'xs' })
      })
    }
  }
]

const { del, post } = useApiMutation()

async function onDelete() {
  if (!deletingNfse.value) return
  try {
    await del(`/nfses/${deletingNfse.value.id}`)
    useAppToast().success('NFS-e removida com sucesso.')
    deletingNfse.value = null
    await refresh()
  } catch {
    useAppToast().error('Erro ao remover NFS-e.')
  }
}

async function onCancel() {
  if (!cancelingNfse.value || !cancelJustificativa.value) return
  try {
    await post(`/nfses/${cancelingNfse.value.id}/cancelar`, { justificativa: cancelJustificativa.value })
    useAppToast().success('NFS-e cancelada com sucesso.')
    cancelingNfse.value = null
    cancelJustificativa.value = ''
    await refresh()
  } catch {
    useAppToast().error('Erro ao cancelar NFS-e.')
  }
}
</script>

<template>
  <UDashboardPanel id="nfse">
    <template #header>
      <UDashboardNavbar title="NFS-e">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <BackToAdmin />
          <CompanySelector />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <div class="flex-1" />

        <UButton
          icon="i-lucide-plus"
          label="Nova NFS-e"
          @click="addOpen = true"
        />
      </UDashboardToolbar>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <UTable
        v-else
        :columns="columns"
        :data="nfses"
      />

      <UModal v-model:open="addOpen">
        <template #header>
          <h3 class="font-semibold">
            Nova NFS-e
          </h3>
        </template>

        <template #body>
          <p class="text-muted">
            Em desenvolvimento - criação de NFS-e
          </p>
        </template>
      </UModal>

      <UModal :open="!!deletingNfse" @update:open="deletingNfse = $event ? deletingNfse : null">
        <template #header>
          <h3 class="font-semibold">
            Excluir NFS-e
          </h3>
        </template>

        <template #body>
          <p class="text-sm text-muted">
            Tem certeza que deseja excluir a NFS-e {{ deletingNfse?.numero }}?
          </p>

          <div class="flex justify-end gap-3 mt-4">
            <UButton variant="ghost" label="Cancelar" @click="deletingNfse = null" />
            <UButton color="error" label="Excluir" @click="onDelete" />
          </div>
        </template>
      </UModal>

      <UModal :open="!!cancelingNfse" @update:open="cancelingNfse = $event ? cancelingNfse : null">
        <template #header>
          <h3 class="font-semibold">
            Cancelar NFS-e
          </h3>
        </template>

        <template #body>
          <p class="text-sm text-muted mb-4">
            Justificativa para cancelamento (mínimo 15 caracteres):
          </p>

          <UTextarea v-model="cancelJustificativa" placeholder="Digite a justificativa..." />

          <div class="flex justify-end gap-3 mt-4">
            <UButton variant="ghost" label="Cancelar" @click="cancelingNfse = null" />
            <UButton
              color="error"
              label="Confirmar Cancelamento"
              :disabled="cancelJustificativa.length < 15"
              @click="onCancel"
            />
          </div>
        </template>
      </UModal>
    </template>
  </UDashboardPanel>
</template>

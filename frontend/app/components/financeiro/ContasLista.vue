<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { Conta, ContaParcela } from '~/types'
import { h, ref } from 'vue'
import { UBadge, UButton, UDropdownMenu } from '#components'

const props = defineProps<{
  tipo: 'pagar' | 'receber'
}>()

const { getContas, deleteConta, cancelarConta, baixarParcela, resumo, refreshResumo } = useContas()

const { data, status, refresh } = getContas({ tipo: props.tipo })

const addOpen = ref(false)
const deletingConta = ref<Conta | null>(null)
const baixaOpen = ref(false)
const baixaParcela = ref<ContaParcela | null>(null)
const contaId = ref<number | null>(null)

const contas = computed(() => data.value?.data || [])

function openBaixa(parcela: ContaParcela, contaIdVal: number) {
  baixaParcela.value = parcela
  contaId.value = contaIdVal
  baixaOpen.value = true
}

const statusColors: Record<string, 'success' | 'warning' | 'error' | 'neutral'> = {
  pendente: 'neutral',
  pago_parcial: 'warning',
  pago: 'success',
  vencido: 'error',
  cancelado: 'neutral'
}

const statusLabels: Record<string, string> = {
  pendente: 'Pendente',
  pago_parcial: 'Pago Parcial',
  pago: 'Pago',
  vencido: 'Vencido',
  cancelado: 'Cancelado'
}

const columns: TableColumn<Conta>[] = [
  {
    accessorKey: 'data_vencimento',
    header: 'Vencimento',
    cell: ({ row }) => new Date(row.original.data_vencimento).toLocaleDateString('pt-BR')
  },
  {
    accessorKey: 'descricao',
    header: 'Descrição'
  },
  {
    accessorKey: 'pessoa.razao_social',
    header: props.tipo === 'pagar' ? 'Fornecedor' : 'Cliente',
    cell: ({ row }) => row.original.pessoa?.razao_social ?? '-'
  },
  {
    accessorKey: 'documento',
    header: 'Documento',
    cell: ({ row }) => row.original.documento ?? '-'
  },
  {
    accessorKey: 'valor_original',
    header: 'Valor',
    cell: ({ row }) => formatCurrency(Number(row.original.valor_original))
  },
  {
    accessorKey: 'valor_baixado',
    header: 'Pago',
    cell: ({ row }) => formatCurrency(Number(row.original.valor_baixado))
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
      const conta = row.original
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const items: any[] = [
        {
          type: 'label' as const,
          label: 'Ações'
        }
      ]

      if (conta.status !== 'pago' && conta.status !== 'cancelado') {
        items.push({
          label: 'Baixar',
          icon: 'i-lucide-check',
          onSelect: () => {
            if (conta.parcelas && conta.parcelas.length > 0) {
              const pendente = conta.parcelas.find(p => p.status === 'pendente' || p.status === 'pago_parcial')
              if (pendente) openBaixa(pendente, conta.id)
            }
          }
        })
      }

      if (conta.status !== 'pago' && conta.status !== 'cancelado') {
        items.push({
          label: 'Cancelar',
          icon: 'i-lucide-x',
          color: 'error' as const,
          onSelect: async () => {
            await cancelarConta(conta.id)
            await refresh()
          }
        })
      }

      items.push({
        type: 'separator' as const
      }, {
        label: 'Excluir',
        icon: 'i-lucide-trash',
        color: 'error' as const,
        onSelect: () => {
          deletingConta.value = conta
        }
      })

      return h(UDropdownMenu, { items }, {
        default: () => h(UButton, { icon: 'i-lucide-ellipsis', variant: 'ghost', size: 'xs' })
      })
    }
  }
]

async function onDelete() {
  if (!deletingConta.value) return
  await deleteConta(deletingConta.value.id)
  deletingConta.value = null
  await refresh()
}

async function onCreated() {
  addOpen.value = false
  await refresh()
  await refreshResumo()
}
</script>

<template>
  <div class="space-y-6">
    <UPageGrid class="lg:grid-cols-5 gap-4 sm:gap-6 lg:gap-px">
      <UPageCard
        icon="i-lucide-trending-up"
        title="Total {{ tipo === 'pagar' ? 'a Pagar' : 'a Receber' }}"
        variant="subtle"
        :color="tipo === 'pagar' ? 'error' : 'success'"
        :ui="{
          container: 'gap-y-1.5',
          wrapper: 'items-start',
          leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
          title: 'font-normal text-muted text-xs uppercase'
        }"
        class="lg:rounded-none first:rounded-l-lg"
      >
        <span class="text-2xl font-semibold text-highlighted">
          {{ formatCurrency(tipo === 'pagar' ? (resumo?.total_a_pagar ?? 0) : (resumo?.total_a_receber ?? 0)) }}
        </span>
      </UPageCard>

      <UPageCard
        icon="i-lucide-alert-triangle"
        title="Vencido"
        variant="subtle"
        color="error"
        :ui="{
          container: 'gap-y-1.5',
          wrapper: 'items-start',
          leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
          title: 'font-normal text-muted text-xs uppercase'
        }"
      >
        <span class="text-2xl font-semibold text-highlighted">
          {{ formatCurrency(resumo?.total_vencido ?? 0) }}
        </span>
      </UPageCard>

      <UPageCard
        icon="i-lucide-calendar"
        title="A Vencer (30 dias)"
        variant="subtle"
        color="warning"
        :ui="{
          container: 'gap-y-1.5',
          wrapper: 'items-start',
          leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
          title: 'font-normal text-muted text-xs uppercase'
        }"
      >
        <span class="text-2xl font-semibold text-highlighted">
          {{ formatCurrency(resumo?.total_a_vencer_30 ?? 0) }}
        </span>
      </UPageCard>

      <UPageCard
        icon="i-lucide-wallet"
        title="Saldo Previsto"
        variant="subtle"
        :color="(resumo?.saldo_previsto ?? 0) >= 0 ? 'success' : 'error'"
        :ui="{
          container: 'gap-y-1.5',
          wrapper: 'items-start',
          leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
          title: 'font-normal text-muted text-xs uppercase'
        }"
      >
        <span class="text-2xl font-semibold text-highlighted">
          {{ formatCurrency(resumo?.saldo_previsto ?? 0) }}
        </span>
      </UPageCard>

      <UPageCard
        class="lg:rounded-none last:rounded-r-lg flex items-center justify-center"
        variant="subtle"
      >
        <UButton
          icon="i-lucide-plus"
          :label="`Nova Conta ${tipo === 'pagar' ? 'a Pagar' : 'a Receber'}`"
          @click="addOpen = true"
        />
      </UPageCard>
    </UPageGrid>

    <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
      <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
    </div>

    <UTable
      v-else
      :columns="columns"
      :data="contas"
    />

    <FinanceiroAddModal
      v-model:open="addOpen"
      :tipo="tipo"
      @created="onCreated"
    />

    <FinanceiroDeleteModal
      :open="!!deletingConta"
      :conta="deletingConta"
      @update:open="deletingConta = $event ? deletingConta : null"
      @deleted="onDelete"
    />

    <FinanceiroBaixaModal
      v-model:open="baixaOpen"
      :parcela="baixaParcela"
      @baixado="async (data) => {
        if (!baixaParcela) return
        await baixarParcela(baixaParcela.id, data)
        baixaOpen = false
        baixaParcela = null
        await refresh()
      }"
    />
  </div>
</template>

<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { Estoque, EstoqueMovimentacao } from '~/types'
import { h, ref } from 'vue'
import { UBadge, UButton } from '#components'

const tab = ref('posicoes')

const { getPositions, getMovimentacoes, refreshResumo } = useEstoque()

const { data: posicoesData, status: posicoesStatus, refresh: refreshPosicoes } = getPositions()
const { data: movimentacoesData, status: movimentacoesStatus } = getMovimentacoes()

const posicoesTable = useTemplateRef('posicoesTable')
const movTableRef = useTemplateRef('movTableRef')

const ajusteOpen = ref(false)

const posicoes = computed(() => posicoesData.value?.data || [])
const movimentacoes = computed(() => movimentacoesData.value?.data || [])

const posColumns: TableColumn<Estoque>[] = [
  {
    accessorKey: 'produto.descricao',
    header: 'Produto'
  },
  {
    accessorKey: 'produto.codigo',
    header: 'Código',
    cell: ({ row }) => row.original.produto?.codigo ?? '-'
  },
  {
    accessorKey: 'quantidade',
    header: 'Quantidade'
  },
  {
    accessorKey: 'localizacao',
    header: 'Localização',
    cell: ({ row }) => row.original.localizacao ?? '-'
  },
  {
    accessorKey: 'custo_medio',
    header: 'Custo Médio',
    cell: ({ row }) => formatCurrency(Number(row.original.custo_medio))
  },
  {
    accessorKey: 'valor_total',
    header: 'Valor Total',
    cell: ({ row }) => {
      const qtd = Number(row.original.quantidade)
      const custo = Number(row.original.custo_medio)
      return formatCurrency(qtd * custo)
    }
  },
  {
    id: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const qtd = Number(row.original.quantidade)
      if (qtd <= 0) {
        return h(UBadge, { color: 'error', variant: 'subtle', label: 'Sem estoque' })
      }
      return h(UBadge, { color: 'success', variant: 'subtle', label: 'Normal' })
    }
  }
]

const movColumns: TableColumn<EstoqueMovimentacao>[] = [
  {
    accessorKey: 'data',
    header: 'Data',
    cell: ({ row }) => new Date(row.original.data).toLocaleDateString('pt-BR')
  },
  {
    accessorKey: 'estoque.produto.descricao',
    header: 'Produto'
  },
  {
    accessorKey: 'tipo',
    header: 'Tipo',
    cell: ({ row }) => {
      const colors: Record<string, 'success' | 'error' | 'warning'> = {
        entrada: 'success',
        saida: 'error',
        ajuste: 'warning'
      }
      return h(UBadge, { color: colors[row.original.tipo] ?? 'neutral', variant: 'subtle', label: row.original.tipo.charAt(0).toUpperCase() + row.original.tipo.slice(1) })
    }
  },
  {
    accessorKey: 'quantidade',
    header: 'Quantidade'
  },
  {
    accessorKey: 'custo_unitario',
    header: 'Custo Unit.',
    cell: ({ row }) => formatCurrency(Number(row.original.custo_unitario))
  },
  {
    accessorKey: 'documento_tipo',
    header: 'Documento',
    cell: ({ row }) => row.original.documento_tipo ?? '-'
  },
  {
    accessorKey: 'user.name',
    header: 'Usuário',
    cell: ({ row }) => row.original.user?.name ?? '-'
  },
  {
    accessorKey: 'observacoes',
    header: 'Observações',
    cell: ({ row }) => row.original.observacoes ?? '-'
  }
]
</script>

<template>
  <UDashboardPanel id="estoque">
    <template #header>
      <UDashboardNavbar title="Estoque">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <BackToAdmin />
          <CompanySelector />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <div class="flex gap-2 flex-1">
          <UButton
            :variant="tab === 'posicoes' ? 'solid' : 'outline'"
            label="Posições"
            @click="tab = 'posicoes'"
          />
          <UButton
            :variant="tab === 'movimentacoes' ? 'solid' : 'outline'"
            label="Movimentações"
            @click="tab = 'movimentacoes'"
          />
        </div>

        <UButton
          v-if="tab === 'posicoes'"
          icon="i-lucide-plus"
          label="Ajuste de Estoque"
          @click="ajusteOpen = true"
        />
      </UDashboardToolbar>
    </template>

    <template #body>
      <div v-if="tab === 'posicoes'">
        <div v-if="posicoesStatus === 'pending'" class="flex items-center justify-center h-48">
          <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
        </div>
        <UTable
          v-else
          ref="posicoesTable"
          :columns="posColumns"
          :data="posicoes"
        />
      </div>

      <div v-else>
        <div v-if="movimentacoesStatus === 'pending'" class="flex items-center justify-center h-48">
          <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
        </div>
        <UTable
          v-else
          ref="movTableRef"
          :columns="movColumns"
          :data="movimentacoes"
        />
      </div>

      <EstoqueAjusteModal
        v-if="tab === 'posicoes'"
        v-model:open="ajusteOpen"
        @created="refreshPosicoes(); refreshResumo()"
      />
    </template>
  </UDashboardPanel>
</template>

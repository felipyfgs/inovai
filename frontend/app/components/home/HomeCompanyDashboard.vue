<script setup lang="ts">
import { UBadge } from '#components'
import { h } from 'vue'
import type { TableColumn } from '@nuxt/ui'

interface CompanyDashboard {
  faturamento_mes: number
  impostos_mes: number
  nfe_mes: number
  orcamentos_pendentes: number
  pedidos_pendentes: number
  produtos_estoque_baixo: number
  certificado_validade: string | null
}

const { currentCompany } = useCurrentCompany()

const { data: stats, status } = useApi<CompanyDashboard>('/dashboard/company', {
  lazy: true,
  watch: [currentCompany as Ref]
})

const statCards = computed(() => [
  {
    title: 'Faturamento do Mês',
    icon: 'i-lucide-trending-up',
    value: formatCurrency(stats.value?.faturamento_mes ?? 0),
    to: '/fiscal/nfe'
  },
  {
    title: 'Impostos do Mês',
    icon: 'i-lucide-receipt',
    value: formatCurrency(stats.value?.impostos_mes ?? 0),
    to: '/fiscal/nfe'
  },
  {
    title: 'NF-e do Mês',
    icon: 'i-lucide-file-text',
    value: String(stats.value?.nfe_mes ?? 0),
    to: '/fiscal/nfe'
  },
  {
    title: 'Orçamentos Pendentes',
    icon: 'i-lucide-clipboard-list',
    value: String(stats.value?.orcamentos_pendentes ?? 0),
    to: '/comercial/orcamentos'
  },
  {
    title: 'Pedidos Pendentes',
    icon: 'i-lucide-shopping-cart',
    value: String(stats.value?.pedidos_pendentes ?? 0),
    to: '/comercial/pedidos'
  }
])

const alerts = computed(() => {
  const items = []
  if (stats.value?.produtos_estoque_baixo) {
    items.push({
      label: `${stats.value.produtos_estoque_baixo} produto(s) com estoque baixo`,
      icon: 'i-lucide-package-x',
      color: 'warning' as const
    })
  }
  if (stats.value?.certificado_validade) {
    const diff = Math.ceil((new Date(stats.value.certificado_validade).getTime() - Date.now()) / (1000 * 60 * 60 * 24))
    if (diff < 0) {
      items.push({
        label: 'Certificado digital vencido!',
        icon: 'i-lucide-alert-circle',
        color: 'error' as const
      })
    } else if (diff <= 30) {
      items.push({
        label: `Certificado digital vence em ${diff} dia(s)`,
        icon: 'i-lucide-alert-triangle',
        color: 'warning' as const
      })
    }
  }
  return items
})

interface QuickAction {
  label: string
  description: string
  icon: string
  to: string
}

const quickActions: QuickAction[] = [
  { label: 'Emitir NF-e', description: 'Nota Fiscal Eletrônica', icon: 'i-lucide-file-plus', to: '/fiscal/nfe' },
  { label: 'Novo Orçamento', description: 'Criar proposta comercial', icon: 'i-lucide-clipboard-plus', to: '/comercial/orcamentos' },
  { label: 'Novo Pedido', description: 'Registrar pedido de venda', icon: 'i-lucide-shopping-cart', to: '/comercial/pedidos' },
  { label: 'Cadastrar Pessoa', description: 'Cliente ou fornecedor', icon: 'i-lucide-user-plus', to: '/cadastros/pessoas' }
]

const actionColumns: TableColumn<QuickAction>[] = [
  {
    accessorKey: 'label',
    header: 'Ação',
    cell: ({ row }) => {
      return h('div', { class: 'flex items-center gap-3' }, [
        h('div', { class: 'p-2 rounded-lg bg-primary/10' }, [
          h(resolveComponent('UIcon'), { name: row.original.icon, class: 'size-5 text-primary' })
        ]),
        h('div', undefined, [
          h('p', { class: 'font-medium text-highlighted' }, row.original.label),
          h('p', { class: 'text-sm text-muted' }, row.original.description)
        ])
      ])
    }
  },
  {
    id: 'go',
    header: '',
    cell: ({ row }) => {
      return h(resolveComponent('UButton'), {
        label: 'Acessar',
        to: row.original.to,
        variant: 'outline',
        color: 'neutral',
        size: 'sm',
        trailingIcon: 'i-lucide-arrow-right'
      })
    }
  }
]
</script>

<template>
  <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
    <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
  </div>

  <template v-else>
    <UPageGrid class="lg:grid-cols-5 gap-4 sm:gap-6 lg:gap-px">
      <UPageCard
        v-for="(stat, index) in statCards"
        :key="index"
        :icon="stat.icon"
        :title="stat.title"
        :to="stat.to"
        variant="subtle"
        :ui="{
          container: 'gap-y-1.5',
          wrapper: 'items-start',
          leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
          title: 'font-normal text-muted text-xs uppercase'
        }"
        class="lg:rounded-none first:rounded-l-lg last:rounded-r-lg hover:z-1"
      >
        <div class="flex items-center gap-2">
          <span class="text-2xl font-semibold text-highlighted">
            {{ stat.value }}
          </span>
        </div>
      </UPageCard>
    </UPageGrid>

    <div v-if="alerts.length" class="space-y-2">
      <UAlert
        v-for="(alert, i) in alerts"
        :key="i"
        :title="alert.label"
        :icon="alert.icon"
        :color="alert.color"
        variant="subtle"
      />
    </div>

    <UCard>
      <template #header>
        <div class="flex items-center justify-between">
          <p class="font-medium text-highlighted">
            Ações Rápidas
          </p>
          <UBadge variant="subtle" color="neutral">
            {{ currentCompany?.fantasia || currentCompany?.razao_social }}
          </UBadge>
        </div>
      </template>

      <UTable
        :data="quickActions"
        :columns="actionColumns"
        :ui="{
          base: 'table-fixed border-separate border-spacing-0',
          thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
          tbody: '[&>tr]:last:[&>td]:border-b-0',
          th: 'first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
          td: 'border-b border-default'
        }"
      />
    </UCard>
  </template>
</template>

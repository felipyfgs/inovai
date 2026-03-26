<script setup lang="ts">
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

const cards = computed(() => [
  {
    label: 'Faturamento do Mês',
    value: formatCurrency(stats.value?.faturamento_mes ?? 0),
    icon: 'i-lucide-trending-up',
    color: 'text-success'
  },
  {
    label: 'Impostos do Mês',
    value: formatCurrency(stats.value?.impostos_mes ?? 0),
    icon: 'i-lucide-receipt',
    color: 'text-warning'
  },
  {
    label: 'NF-e do Mês',
    value: String(stats.value?.nfe_mes ?? 0),
    icon: 'i-lucide-file-text',
    color: 'text-primary'
  },
  {
    label: 'Orçamentos Pendentes',
    value: String(stats.value?.orcamentos_pendentes ?? 0),
    icon: 'i-lucide-clipboard-list',
    color: 'text-info'
  },
  {
    label: 'Pedidos Pendentes',
    value: String(stats.value?.pedidos_pendentes ?? 0),
    icon: 'i-lucide-shopping-cart',
    color: 'text-info'
  }
])

const alerts = computed(() => {
  const items = []
  if (stats.value?.produtos_estoque_baixo) {
    items.push({
      label: `${stats.value.produtos_estoque_baixo} produto(s) com estoque baixo`,
      icon: 'i-lucide-alert-triangle',
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
</script>

<template>
  <div class="space-y-6">
    <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
      <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
    </div>

    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <UCard v-for="card in cards" :key="card.label">
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

      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <UButton
          label="Nova NF-e"
          icon="i-lucide-file-plus"
          to="/fiscal/nfe"
          variant="outline"
          color="neutral"
          block
        />
        <UButton
          label="Novo Orçamento"
          icon="i-lucide-clipboard-plus"
          to="/comercial/orcamentos"
          variant="outline"
          color="neutral"
          block
        />
        <UButton
          label="Ver Pedidos"
          icon="i-lucide-shopping-cart"
          to="/comercial/pedidos"
          variant="outline"
          color="neutral"
          block
        />
        <UButton
          label="Cadastros"
          icon="i-lucide-database"
          to="/cadastros/pessoas"
          variant="outline"
          color="neutral"
          block
        />
      </div>
    </template>
  </div>
</template>

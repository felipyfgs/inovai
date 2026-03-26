<script setup lang="ts">
interface OfficeDashboard {
  total_empresas: number
  total_nfe: number
  nfe_autorizadas: number
  total_cte: number
  total_mdfe: number
  certificados_vencendo: number
  certificados_vencidos: number
}

const { data: stats, status } = useApi<OfficeDashboard>('/dashboard/office', { lazy: true })

const cards = computed(() => [
  {
    label: 'Empresas',
    value: stats.value?.total_empresas ?? 0,
    icon: 'i-lucide-building-2',
    color: 'text-primary'
  },
  {
    label: 'NF-e Emitidas',
    value: stats.value?.total_nfe ?? 0,
    icon: 'i-lucide-file-text',
    color: 'text-success'
  },
  {
    label: 'NF-e Autorizadas',
    value: stats.value?.nfe_autorizadas ?? 0,
    icon: 'i-lucide-check-circle',
    color: 'text-success'
  },
  {
    label: 'CT-e',
    value: stats.value?.total_cte ?? 0,
    icon: 'i-lucide-truck',
    color: 'text-info'
  },
  {
    label: 'MDF-e',
    value: stats.value?.total_mdfe ?? 0,
    icon: 'i-lucide-route',
    color: 'text-info'
  }
])

const alerts = computed(() => {
  const items = []
  if (stats.value?.certificados_vencidos) {
    items.push({
      label: `${stats.value.certificados_vencidos} certificado(s) vencido(s)`,
      icon: 'i-lucide-alert-circle',
      color: 'error' as const
    })
  }
  if (stats.value?.certificados_vencendo) {
    items.push({
      label: `${stats.value.certificados_vencendo} certificado(s) vencendo em 30 dias`,
      icon: 'i-lucide-alert-triangle',
      color: 'warning' as const
    })
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

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UButton
          label="Nova Empresa"
          icon="i-lucide-plus"
          to="/empresas"
          variant="outline"
          color="neutral"
          block
        />
        <UButton
          label="Gerenciar Empresas"
          icon="i-lucide-building-2"
          to="/empresas"
          variant="outline"
          color="neutral"
          block
        />
        <UButton
          label="Gerenciar Usuários"
          icon="i-lucide-users"
          to="/usuarios"
          variant="outline"
          color="neutral"
          block
        />
      </div>
    </template>
  </div>
</template>

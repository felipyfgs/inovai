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

const statCards = computed(() => [
  {
    title: 'Empresas',
    icon: 'i-lucide-building-2',
    value: stats.value?.total_empresas ?? 0,
    to: '/empresas'
  },
  {
    title: 'NF-e Emitidas',
    icon: 'i-lucide-file-text',
    value: stats.value?.total_nfe ?? 0,
    to: '/fiscal/nfe'
  },
  {
    title: 'NF-e Autorizadas',
    icon: 'i-lucide-check-circle',
    value: stats.value?.nfe_autorizadas ?? 0,
    to: '/fiscal/nfe'
  },
  {
    title: 'CT-e',
    icon: 'i-lucide-truck',
    value: stats.value?.total_cte ?? 0,
    to: '/fiscal/cte'
  },
  {
    title: 'MDF-e',
    icon: 'i-lucide-route',
    value: stats.value?.total_mdfe ?? 0,
    to: '/fiscal/mdfe'
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

    <UCard v-if="!stats?.total_empresas" :ui="{ body: 'text-center py-12' }">
      <UIcon name="i-lucide-building-2" class="size-12 text-muted mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-highlighted mb-2">Bem-vindo ao InovAI!</h3>
      <p class="text-muted mb-6">Comece cadastrando sua primeira empresa para emitir notas fiscais.</p>
      <UButton
        label="Cadastrar Empresa"
        icon="i-lucide-plus"
        to="/empresas"
        size="lg"
      />
    </UCard>

    <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <UButton
        label="Nova Empresa"
        icon="i-lucide-plus"
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
      <UButton
        label="Selecione uma empresa na sidebar para começar"
        icon="i-lucide-arrow-left"
        variant="outline"
        color="neutral"
        block
        disabled
      />
    </div>
  </template>
</template>

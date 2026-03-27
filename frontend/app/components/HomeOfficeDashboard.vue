<script setup lang="ts">
const { data: dashboard, status } = useApi<{
  total_empresas: number
  total_nfe: number
  nfe_autorizadas: number
  total_cte: number
  total_mdfe: number
  certificados_vencendo: number
  certificados_vencidos: number
}>('/dashboard/office', {
  lazy: true
})

const statsCards = computed(() => [
  {
    label: 'Empresas',
    value: dashboard.value?.total_empresas ?? 0,
    icon: 'i-lucide-building-2',
    to: '/empresas'
  },
  {
    label: 'NF-e Emitidas',
    value: dashboard.value?.total_nfe ?? 0,
    icon: 'i-lucide-file-text',
    to: '/fiscal/nfe'
  },
  {
    label: 'CT-e',
    value: dashboard.value?.total_cte ?? 0,
    icon: 'i-lucide-truck',
    to: '/fiscal/cte'
  },
  {
    label: 'MDF-e',
    value: dashboard.value?.total_mdfe ?? 0,
    icon: 'i-lucide-map',
    to: '/fiscal/mdfe'
  }
])

const hasAlerts = computed(() =>
  (dashboard.value?.certificados_vencidos ?? 0) > 0 || (dashboard.value?.certificados_vencendo ?? 0) > 0
)
</script>

<template>
  <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
    <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
  </div>

  <div v-else class="space-y-6">
    <div v-if="hasAlerts" class="p-4 rounded-lg border border-amber-500/30 bg-amber-500/5">
      <div class="flex items-start gap-3">
        <UIcon name="i-lucide-shield-alert" class="size-5 text-amber-500 shrink-0 mt-0.5" />
        <div>
          <p class="font-medium text-amber-700 dark:text-amber-400">
            Atenção aos certificados digitais
          </p>
          <p class="text-sm text-amber-600 dark:text-amber-500 mt-1">
            <template v-if="(dashboard?.certificados_vencidos ?? 0) > 0">
              {{ dashboard?.certificados_vencidos }} certificado(s) vencido(s).
            </template>
            <template v-if="(dashboard?.certificados_vencendo ?? 0) > 0">
              {{ dashboard?.certificados_vencendo }} certificado(s) vence(m) nos próximos 30 dias.
            </template>
          </p>
        </div>
      </div>
    </div>

    <UPageGrid class="lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-px">
      <UPageCard
        v-for="card in statsCards"
        :key="card.label"
        :icon="card.icon"
        :title="card.label"
        variant="subtle"
        :ui="{
          container: 'gap-y-1.5 cursor-pointer',
          wrapper: 'items-start',
          leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
          title: 'font-normal text-muted text-xs uppercase'
        }"
        class="lg:rounded-none first:rounded-l-lg last:rounded-r-lg hover:z-1"
        @click="navigateTo(card.to)"
      >
        <span class="text-2xl font-semibold text-highlighted">
          {{ card.value }}
        </span>
      </UPageCard>
    </UPageGrid>

    <UPageCard title="Ações Rápidas" variant="subtle">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <UButton
          label="Empresas"
          icon="i-lucide-building-2"
          to="/empresas"
          variant="outline"
          color="neutral"
          block
        />
        <UButton
          label="NF-e"
          icon="i-lucide-file-text"
          to="/fiscal/nfe"
          variant="outline"
          color="neutral"
          block
        />
        <UButton
          label="Orçamentos"
          icon="i-lucide-calculator"
          to="/comercial/orcamentos"
          variant="outline"
          color="neutral"
          block
        />
        <UButton
          label="Perfil"
          icon="i-lucide-building"
          to="/escritorio"
          variant="outline"
          color="neutral"
          block
        />
      </div>
    </UPageCard>
  </div>
</template>

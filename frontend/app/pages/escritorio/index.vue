<script setup lang="ts">
import type { Period, Range } from '~/types'

definePageMeta({ middleware: 'escritorio' })

const range = shallowRef<Range>({
  start: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
  end: new Date()
})
const period = ref<Period>('daily')

const queryParams = computed(() => ({
  start: range.value.start.toISOString().split('T')[0],
  end: range.value.end.toISOString().split('T')[0]
}))

const { data: dashboard, status } = useApi<{
  total_empresas: number
  total_nfe: number
  nfe_autorizadas: number
  total_cte: number
  total_mdfe: number
  certificados_vencendo: number
  certificados_vencidos: number
}>('/dashboard/office', {
  lazy: true,
  query: queryParams
})

const statCards = computed(() => [
  {
    label: 'NF-e Emitidas',
    value: dashboard.value?.total_nfe ?? 0,
    icon: 'i-lucide-file-text'
  },
  {
    label: 'NF-e Autorizadas',
    value: dashboard.value?.nfe_autorizadas ?? 0,
    icon: 'i-lucide-check-circle'
  },
  {
    label: 'CT-e Emitidos',
    value: dashboard.value?.total_cte ?? 0,
    icon: 'i-lucide-truck'
  },
  {
    label: 'MDF-e Emitidos',
    value: dashboard.value?.total_mdfe ?? 0,
    icon: 'i-lucide-route'
  },
  {
    label: 'Empresas',
    value: dashboard.value?.total_empresas ?? 0,
    icon: 'i-lucide-building-2'
  },
  {
    label: 'Certificados Vencendo',
    value: dashboard.value?.certificados_vencendo ?? 0,
    icon: 'i-lucide-shield-alert',
    color: (dashboard.value?.certificados_vencendo ?? 0) > 0 ? 'warning' as const : undefined
  }
])
</script>

<template>
  <UDashboardPanel id="escritorio">
    <template #header>
      <UDashboardNavbar title="Painel do Escritório">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <template #left>
          <AdminDashboardDateRangePicker v-model="range" class="-ms-1" />
          <AdminDashboardPeriodSelect v-model="period" :range="range" />
        </template>
      </UDashboardToolbar>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <div v-else class="space-y-6">
        <UPageGrid class="lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-px">
          <UPageCard
            v-for="card in statCards"
            :key="card.label"
            :icon="card.icon"
            :title="card.label"
            variant="subtle"
            :ui="{
              container: 'gap-y-1.5',
              wrapper: 'items-start',
              leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
              title: 'font-normal text-muted text-xs uppercase'
            }"
            class="lg:rounded-none first:rounded-l-lg last:rounded-r-lg hover:z-1"
          >
            <span class="text-2xl font-semibold text-highlighted">
              {{ card.value }}
            </span>
          </UPageCard>
        </UPageGrid>

        <EscritorioChart :period="period" :range="range" />

        <div v-if="(dashboard?.certificados_vencidos ?? 0) > 0" class="p-4 rounded-lg border border-error/30 bg-error/5">
          <div class="flex items-start gap-3">
            <UIcon name="i-lucide-shield-alert" class="size-5 text-error shrink-0 mt-0.5" />
            <div>
              <p class="font-medium text-error">
                {{ dashboard?.certificados_vencidos }} certificado(s) vencido(s)
              </p>
              <p class="text-sm text-muted mt-1">
                Acesse a página de Empresas para renovar.
              </p>
            </div>
          </div>
        </div>

        <div v-else-if="(dashboard?.certificados_vencendo ?? 0) > 0" class="p-4 rounded-lg border border-warning/30 bg-warning/5">
          <div class="flex items-start gap-3">
            <UIcon name="i-lucide-shield-alert" class="size-5 text-warning shrink-0 mt-0.5" />
            <div>
              <p class="font-medium text-warning">
                {{ dashboard?.certificados_vencendo }} certificado(s) vence(m) em até 30 dias
              </p>
              <p class="text-sm text-muted mt-1">
                Acesse a página de Empresas para renovar.
              </p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UButton
            label="Gerenciar Empresas"
            icon="i-lucide-building-2"
            to="/empresas"
            variant="outline"
            color="neutral"
            block
          />
          <UButton
            label="Gerenciar Equipe"
            icon="i-lucide-users"
            to="/escritorio/equipe"
            variant="outline"
            color="neutral"
            block
          />
          <UButton
            label="Ver Planos"
            icon="i-lucide-package"
            to="/escritorio/planos"
            variant="outline"
            color="neutral"
            block
          />
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>

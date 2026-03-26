<script setup lang="ts">
import type { AdminDashboard, Period, Range } from '~/types'

definePageMeta({ middleware: 'admin' })

const range = shallowRef<Range>({
  start: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
  end: new Date()
})
const period = ref<Period>('daily')

const queryParams = computed(() => ({
  start: range.value.start ? range.value.start.toISOString().split('T')[0] : undefined,
  end: range.value.end ? range.value.end.toISOString().split('T')[0] : undefined
}))

const { data: stats, status } = useApi<AdminDashboard>('/admin/invoices/dashboard', {
  lazy: true,
  query: queryParams
})

const statCards = computed(() => [
  {
    label: 'Receita no Período',
    value: stats.value?.revenue ?? 0,
    icon: 'i-lucide-trending-up',
    format: 'currency' as const,
    variation: stats.value?.revenue_variation ?? 0
  },
  {
    label: 'Receita Anterior',
    value: stats.value?.previous_revenue ?? 0,
    icon: 'i-lucide-calendar',
    format: 'currency' as const,
    variation: null as number | null
  },
  {
    label: 'A Receber',
    value: stats.value?.pending ?? 0,
    icon: 'i-lucide-clock',
    format: 'currency' as const,
    variation: null as number | null
  },
  {
    label: 'Vencido',
    value: stats.value?.overdue ?? 0,
    icon: 'i-lucide-alert-circle',
    format: 'currency' as const,
    variation: null as number | null
  },
  {
    label: 'Total de Clientes',
    value: stats.value?.total_offices ?? 0,
    icon: 'i-lucide-users',
    format: 'number' as const,
    variation: null as number | null
  },
  {
    label: 'Inadimplentes',
    value: stats.value?.inadimplentes ?? 0,
    icon: 'i-lucide-user-x',
    format: 'number' as const,
    variation: null as number | null
  }
])
</script>

<template>
  <UDashboardPanel id="admin-dashboard">
    <template #header>
      <UDashboardNavbar title="Painel Administrativo">
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
            <div class="flex items-center gap-2">
              <span class="text-2xl font-semibold text-highlighted">
                <template v-if="card.format === 'currency'">
                  {{ formatCurrency(card.value) }}
                </template>
                <template v-else>
                  {{ card.value }}
                </template>
              </span>

              <UBadge
                v-if="card.variation !== null"
                :color="card.variation > 0 ? 'success' : 'error'"
                variant="subtle"
                class="text-xs"
              >
                {{ card.variation > 0 ? '+' : '' }}{{ card.variation }}%
              </UBadge>
            </div>
          </UPageCard>
        </UPageGrid>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
          <div class="lg:col-span-3">
            <AdminDashboardChart :period="period" :range="range" />
          </div>
          <div class="lg:col-span-2">
            <AdminDashboardPlansChart />
          </div>
        </div>

        <AdminDashboardStatusChart :period="period" :range="range" />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <AdminDashboardGrowthChart :period="period" :range="range" />
          <AdminDashboardOverdueChart :period="period" :range="range" />
        </div>

        <UPageCard
          v-if="stats"
          title="Taxa de Inadimplência"
          variant="subtle"
        >
          <div class="flex items-center gap-4">
            <UProgress
              :value="stats.churn_rate"
              :max="100"
              color="error"
              class="flex-1"
            />
            <span class="text-sm font-medium w-12 text-right">{{ stats.churn_rate }}%</span>
          </div>
          <p class="text-sm text-muted mt-2">
            {{ stats.inadimplentes }} de {{ stats.total_offices }} clientes com faturas vencidas
          </p>
        </UPageCard>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UButton
            label="Gerir Contadores"
            icon="i-lucide-users"
            to="/admin/escritorios"
            variant="outline"
            color="neutral"
            block
          />
          <UButton
            label="Cobranças"
            icon="i-lucide-receipt"
            to="/admin/cobrancas"
            variant="outline"
            color="neutral"
            block
          />
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>

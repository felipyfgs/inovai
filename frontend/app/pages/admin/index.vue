<script setup lang="ts">
import type { AdminDashboard } from '~/types'

definePageMeta({ middleware: 'admin' })

const { data: stats, status } = useApi<AdminDashboard>('/admin/invoices/dashboard', { lazy: true })

const statCards = computed(() => [
  {
    label: 'Receita do Mês (MRR)',
    value: stats.value?.mrr ?? 0,
    icon: 'i-lucide-trending-up',
    color: 'text-success',
    format: 'currency'
  },
  {
    label: 'Mês Anterior',
    value: stats.value?.last_month_revenue ?? 0,
    icon: 'i-lucide-calendar',
    color: 'text-muted',
    format: 'currency'
  },
  {
    label: 'A Receber',
    value: stats.value?.pending ?? 0,
    icon: 'i-lucide-clock',
    color: 'text-warning',
    format: 'currency'
  },
  {
    label: 'Vencido',
    value: stats.value?.overdue ?? 0,
    icon: 'i-lucide-alert-circle',
    color: 'text-error',
    format: 'currency'
  },
  {
    label: 'Total de Clientes',
    value: stats.value?.total_offices ?? 0,
    icon: 'i-lucide-users',
    color: 'text-primary',
    format: 'number'
  },
  {
    label: 'Inadimplentes',
    value: stats.value?.inadimplentes ?? 0,
    icon: 'i-lucide-user-x',
    color: 'text-error',
    format: 'number'
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

        <template #right />
      </UDashboardNavbar>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <div v-else class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <UCard
            v-for="card in statCards"
            :key="card.label"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-muted">
                  {{ card.label }}
                </p>
                <p class="text-2xl font-bold mt-1">
                  <template v-if="card.format === 'currency'">
                    {{ formatCurrency(card.value) }}
                  </template>
                  <template v-else>
                    {{ card.value }}
                  </template>
                </p>
              </div>
              <UIcon :name="card.icon" :class="['size-8', card.color]" />
            </div>
          </UCard>
        </div>

        <UCard v-if="stats">
          <template #header>
            <p class="font-medium">
              Taxa de Inadimplência
            </p>
          </template>
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
        </UCard>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UButton
            label="Gerir Contadores"
            icon="i-lucide-users"
            to="/admin/contadores"
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
          <UButton
            label="Mapa de Empresas"
            icon="i-lucide-map"
            to="/admin/mapa"
            variant="outline"
            color="neutral"
            block
          />
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>

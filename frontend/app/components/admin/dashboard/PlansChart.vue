<script setup lang="ts">
type PlanRecord = { plan: string, subscribers: number, mrr: number }

const { data } = useApi<PlanRecord[]>('/admin/invoices/plans-chart', { lazy: true })

const chartData = computed<PlanRecord[]>(() => data.value ?? [])

const totalMrr = computed(() => chartData.value.reduce((acc: number, r) => acc + r.mrr, 0))
const totalSubscribers = computed(() => chartData.value.reduce((acc: number, r) => acc + r.subscribers, 0))
</script>

<template>
  <UCard>
    <template #header>
      <div>
        <p class="text-xs text-muted uppercase mb-1.5">
          Assinantes por Plano
        </p>
        <p class="text-3xl text-highlighted font-semibold">
          {{ totalSubscribers }}
        </p>
        <p class="text-sm text-muted">
          MRR: {{ formatCurrency(totalMrr) }}
        </p>
      </div>
    </template>

    <div v-if="!chartData.length" class="flex items-center justify-center py-8 text-muted text-sm">
      Nenhum dado disponível.
    </div>

    <div v-else class="divide-y divide-[var(--ui-border)]">
      <div
        v-for="record in chartData"
        :key="record.plan"
        class="flex items-center justify-between py-3 first:pt-0 last:pb-0"
      >
        <span class="text-sm text-muted">
          {{ record.plan }}
        </span>
        <div class="flex items-center gap-3">
          <span class="text-sm font-medium">
            {{ formatCurrency(record.mrr) }}/mês
          </span>
          <span class="text-sm font-semibold text-highlighted">
            {{ record.subscribers }}
          </span>
        </div>
      </div>
    </div>
  </UCard>
</template>

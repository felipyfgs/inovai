<script setup lang="ts">
import { VisSingleContainer, VisDonut, VisCrosshair, VisTooltip } from '@unovis/vue'

const cardRef = useTemplateRef<HTMLElement | null>('cardRef')

type PlanRecord = { plan: string, subscribers: number, mrr: number }

const { data } = useApi<PlanRecord[]>('/admin/invoices/plans-chart', { lazy: true })

const chartData = computed<PlanRecord[]>(() => data.value ?? [])

const totalMrr = computed(() => chartData.value.reduce((acc: number, r) => acc + r.mrr, 0))
const totalSubscribers = computed(() => chartData.value.reduce((acc: number, r) => acc + r.subscribers, 0))

const x = (_: PlanRecord, i: number) => i
const y = (d: PlanRecord) => d.subscribers

const colors = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4']

const template = (d: PlanRecord) => `${d.plan}: ${d.subscribers} assinante(s)\nR$ ${d.mrr.toFixed(2).replace('.', ',')}/mês`
</script>

<template>
  <UCard ref="cardRef" :ui="{ root: 'overflow-visible', body: '!px-0 !pt-0 !pb-3' }">
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

    <div v-if="!chartData.length" class="h-72 flex items-center justify-center text-muted text-sm">
      Nenhum dado disponível.
    </div>

    <VisSingleContainer
      v-else
      :data="chartData"
      :height="280"
      :width="200"
    >
      <VisDonut
        :x="x"
        :y="y"
        :value="(d: PlanRecord) => d.subscribers"
        :color="(_: PlanRecord, i: number) => colors[i % colors.length]"
        :arc-width="20"
      />

      <VisCrosshair
        color="var(--ui-primary)"
        :template="template"
      />

      <VisTooltip />
    </VisSingleContainer>

    <div class="px-4 flex flex-wrap gap-3 mt-2">
      <div v-for="(record, i) in chartData" :key="record.plan" class="flex items-center gap-1.5 text-xs">
        <span class="size-2.5 rounded-full shrink-0" :style="{ backgroundColor: colors[i % colors.length] }" />
        <span class="text-muted">{{ record.plan }}</span>
        <span class="font-medium">{{ record.subscribers }}</span>
      </div>
    </div>
  </UCard>
</template>

<style scoped>
.unovis-single-container {
  --vis-crosshair-line-stroke-color: var(--ui-primary);
  --vis-crosshair-circle-stroke-color: var(--ui-bg);
  --vis-tooltip-background-color: var(--ui-bg);
  --vis-tooltip-border-color: var(--ui-border);
  --vis-tooltip-text-color: var(--ui-text-highlighted);
}
</style>

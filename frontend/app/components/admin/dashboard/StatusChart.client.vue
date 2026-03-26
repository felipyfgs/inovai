<script setup lang="ts">
import { VisXYContainer, VisAxis, VisGroupedBar, VisCrosshair, VisTooltip } from '@unovis/vue'
import type { Period, Range } from '~/types'

const cardRef = useTemplateRef<HTMLElement | null>('cardRef')

const props = defineProps<{
  period: Period
  range: Range
}>()

type StatusRecord = { date: string, label: string, paid: number, pending: number, overdue: number, cancelled: number }

const { width } = useElementSize(cardRef)

const queryParams = computed(() => ({
  start: props.range.start.toISOString().split('T')[0],
  end: props.range.end.toISOString().split('T')[0],
  period: props.period
}))

const { data } = useApi<StatusRecord[]>('/admin/invoices/status-chart', {
  lazy: true,
  query: queryParams
})

const chartData = computed<StatusRecord[]>(() => data.value ?? [])

const x = (_: StatusRecord, i: number) => i

const xTicks = (i: number) => {
  if (!chartData.value[i]) return ''
  if (chartData.value.length <= 15) return chartData.value[i].label
  if (i === 0) return chartData.value[i].label
  if (i === chartData.value.length - 1) return chartData.value[i].label
  const step = Math.ceil(chartData.value.length / 6)
  return i % step === 0 ? chartData.value[i].label : ''
}

const template = (d: StatusRecord) => {
  return `${d.label}<br>Pago: ${formatCurrency(d.paid)}<br>Pendente: ${formatCurrency(d.pending)}<br>Vencido: ${formatCurrency(d.overdue)}`
}
</script>

<template>
  <UCard ref="cardRef" :ui="{ root: 'overflow-visible', body: '!px-0 !pt-0 !pb-3' }">
    <template #header>
      <div>
        <p class="text-xs text-muted uppercase mb-1.5">
          Status das Faturas
        </p>
        <div class="flex flex-wrap gap-3 text-xs">
          <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-emerald-500" /> Pago</span>
          <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-amber-500" /> Pendente</span>
          <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-red-500" /> Vencido</span>
        </div>
      </div>
    </template>

    <div v-if="!chartData.length" class="h-72 flex items-center justify-center text-muted text-sm">
      Nenhum dado disponível para o período selecionado.
    </div>

    <VisXYContainer
      v-else
      :data="chartData"
      :padding="{ top: 10 }"
      class="h-72"
      :width="width"
    >
      <VisGroupedBar
        :x="x"
        :y="(d: StatusRecord, accessor: string) => d[accessor as keyof StatusRecord] as number"
        :accessors="['paid', 'pending', 'overdue'] as any"
        :colors="['#10b981', '#f59e0b', '#ef4444']"
        rounded-corners="top"
      />

      <VisAxis
        type="x"
        :x="x"
        :tick-format="xTicks"
      />

      <VisCrosshair
        color="var(--ui-primary)"
        :template="template"
      />

      <VisTooltip />
    </VisXYContainer>
  </UCard>
</template>

<style scoped>
.unovis-xy-container {
  --vis-crosshair-line-stroke-color: var(--ui-border);
  --vis-crosshair-circle-stroke-color: var(--ui-bg);
  --vis-axis-grid-color: var(--ui-border);
  --vis-axis-tick-color: var(--ui-border);
  --vis-axis-tick-label-color: var(--ui-text-dimmed);
  --vis-tooltip-background-color: var(--ui-bg);
  --vis-tooltip-border-color: var(--ui-border);
  --vis-tooltip-text-color: var(--ui-text-highlighted);
}
</style>

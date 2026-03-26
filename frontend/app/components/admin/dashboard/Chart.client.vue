<script setup lang="ts">
import { VisXYContainer, VisLine, VisAxis, VisArea, VisCrosshair, VisTooltip } from '@unovis/vue'
import type { Period, Range } from '~/types'

const cardRef = useTemplateRef<HTMLElement | null>('cardRef')

const props = defineProps<{
  period: Period
  range: Range
}>()

type ChartRecord = {
  date: string
  label: string
  amount: number
}

const { width } = useElementSize(cardRef)

const queryParams = computed(() => ({
  start: props.range.start.toISOString().split('T')[0],
  end: props.range.end.toISOString().split('T')[0],
  period: props.period
}))

const { data } = useApi<ChartRecord[]>('/admin/invoices/chart', {
  lazy: true,
  query: queryParams
})

const chartData = computed<ChartRecord[]>(() => data.value ?? [])

const x = (_: ChartRecord, i: number) => i
const y = (d: ChartRecord) => d.amount

const total = computed(() => chartData.value.reduce((acc: number, { amount }) => acc + amount, 0))

const xTicks = (i: number) => {
  if (!chartData.value[i]) return ''
  if (chartData.value.length <= 15) return chartData.value[i].label
  if (i === 0) return chartData.value[i].label
  if (i === chartData.value.length - 1) return chartData.value[i].label
  const step = Math.ceil(chartData.value.length / 6)
  return i % step === 0 ? chartData.value[i].label : ''
}

const template = (d: ChartRecord) => `${d.label}: ${formatCurrency(d.amount)}`
</script>

<template>
  <UCard ref="cardRef" :ui="{ root: 'overflow-visible', body: '!px-0 !pt-0 !pb-3' }">
    <template #header>
      <div>
        <p class="text-xs text-muted uppercase mb-1.5">
          Evolução da Receita
        </p>
        <p class="text-3xl text-highlighted font-semibold">
          {{ formatCurrency(total) }}
        </p>
      </div>
    </template>

    <div v-if="!chartData.length" class="h-96 flex items-center justify-center text-muted text-sm">
      Nenhum dado disponível para o período selecionado.
    </div>

    <VisXYContainer
      v-else
      :data="chartData"
      :padding="{ top: 40 }"
      class="h-96"
      :width="width"
    >
      <VisLine
        :x="x"
        :y="y"
        color="var(--ui-primary)"
      />
      <VisArea
        :x="x"
        :y="y"
        color="var(--ui-primary)"
        :opacity="0.1"
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
  --vis-crosshair-line-stroke-color: var(--ui-primary);
  --vis-crosshair-circle-stroke-color: var(--ui-bg);

  --vis-axis-grid-color: var(--ui-border);
  --vis-axis-tick-color: var(--ui-border);
  --vis-axis-tick-label-color: var(--ui-text-dimmed);

  --vis-tooltip-background-color: var(--ui-bg);
  --vis-tooltip-border-color: var(--ui-border);
  --vis-tooltip-text-color: var(--ui-text-highlighted);
}
</style>

<script setup lang="ts">
import { VisXYContainer, VisLine, VisAxis, VisArea, VisCrosshair, VisTooltip } from '@unovis/vue'
import type { Period, Range } from '~/types'

const cardRef = useTemplateRef<HTMLElement | null>('cardRef')

const props = defineProps<{
  period: Period
  range: Range
}>()

type GrowthRecord = { date: string, label: string, count: number }

const { width } = useElementSize(cardRef)

const queryParams = computed(() => ({
  start: props.range.start.toISOString().split('T')[0],
  end: props.range.end.toISOString().split('T')[0],
  period: props.period
}))

const { data } = useApi<GrowthRecord[]>('/admin/offices/growth-chart', {
  lazy: true,
  query: queryParams
})

const chartData = computed<GrowthRecord[]>(() => data.value ?? [])

const total = computed(() => chartData.value.reduce((acc: number, { count }) => acc + count, 0))

const x = (_: GrowthRecord, i: number) => i
const y = (d: GrowthRecord) => d.count

const xTicks = (i: number) => {
  if (!chartData.value[i]) return ''
  if (chartData.value.length <= 15) return chartData.value[i].label
  if (i === 0) return chartData.value[i].label
  if (i === chartData.value.length - 1) return chartData.value[i].label
  const step = Math.ceil(chartData.value.length / 6)
  return i % step === 0 ? chartData.value[i].label : ''
}

const template = (d: GrowthRecord) => `${d.label}: ${d.count} novo(s)`
</script>

<template>
  <UCard ref="cardRef" :ui="{ root: 'overflow-visible', body: '!px-0 !pt-0 !pb-3' }">
    <template #header>
      <div>
        <p class="text-xs text-muted uppercase mb-1.5">
          Crescimento de Escritórios
        </p>
        <p class="text-3xl text-highlighted font-semibold">
          {{ total }}
        </p>
        <p class="text-sm text-muted">
          Novos escritórios no período
        </p>
      </div>
    </template>

    <div v-if="!chartData.length" class="h-72 flex items-center justify-center text-muted text-sm">
      Nenhum dado disponível para o período selecionado.
    </div>

    <VisXYContainer
      v-else
      :data="chartData"
      :padding="{ top: 40 }"
      class="h-72"
      :width="width"
    >
      <VisLine
        :x="x"
        :y="y"
        color="#3b82f6"
      />
      <VisArea
        :x="x"
        :y="y"
        color="#3b82f6"
        :opacity="0.1"
      />

      <VisAxis
        type="x"
        :x="x"
        :tick-format="xTicks"
      />

      <VisCrosshair
        color="#3b82f6"
        :template="template"
      />

      <VisTooltip />
    </VisXYContainer>
  </UCard>
</template>

<style scoped>
.unovis-xy-container {
  --vis-crosshair-line-stroke-color: #3b82f6;
  --vis-crosshair-circle-stroke-color: var(--ui-bg);
  --vis-axis-grid-color: var(--ui-border);
  --vis-axis-tick-color: var(--ui-border);
  --vis-axis-tick-label-color: var(--ui-text-dimmed);
  --vis-tooltip-background-color: var(--ui-bg);
  --vis-tooltip-border-color: var(--ui-border);
  --vis-tooltip-text-color: var(--ui-text-highlighted);
}
</style>

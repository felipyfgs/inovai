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
  nfe: number
  cte: number
  mdfe: number
}

const { width } = useElementSize(cardRef)

const queryParams = computed(() => ({
  start: props.range.start.toISOString().split('T')[0],
  end: props.range.end.toISOString().split('T')[0],
  period: props.period
}))

const { data } = useApi<ChartRecord[]>('/dashboard/office/chart', {
  lazy: true,
  query: queryParams
})

const chartData = computed<ChartRecord[]>(() => data.value ?? [])

const x = (_: ChartRecord, i: number) => i
const yNfe = (d: ChartRecord) => d.nfe
const yCte = (d: ChartRecord) => d.cte
const yMdfe = (d: ChartRecord) => d.mdfe

const totalNfe = computed(() => chartData.value.reduce((acc: number, d) => acc + d.nfe, 0))
const totalCte = computed(() => chartData.value.reduce((acc: number, d) => acc + d.cte, 0))
const totalMdfe = computed(() => chartData.value.reduce((acc: number, d) => acc + d.mdfe, 0))

const xTicks = (i: number) => {
  if (!chartData.value[i]) return ''
  if (chartData.value.length <= 15) return chartData.value[i].label
  if (i === 0) return chartData.value[i].label
  if (i === chartData.value.length - 1) return chartData.value[i].label
  const step = Math.ceil(chartData.value.length / 6)
  return i % step === 0 ? chartData.value[i].label : ''
}

const template = (d: ChartRecord) => `${d.label} — NF-e: ${d.nfe} | CT-e: ${d.cte} | MDF-e: ${d.mdfe}`
</script>

<template>
  <UCard ref="cardRef" :ui="{ root: 'overflow-visible', body: '!px-0 !pt-0 !pb-3' }">
    <template #header>
      <div>
        <p class="text-xs text-muted uppercase mb-1.5">
          Documentos Fiscais
        </p>
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-1.5">
            <div class="size-2 rounded-full bg-[var(--ui-primary)]" />
            <span class="text-sm text-muted">NF-e: <span class="text-highlighted font-semibold">{{ totalNfe }}</span></span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="size-2 rounded-full bg-blue-500" />
            <span class="text-sm text-muted">CT-e: <span class="text-highlighted font-semibold">{{ totalCte }}</span></span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="size-2 rounded-full bg-amber-500" />
            <span class="text-sm text-muted">MDF-e: <span class="text-highlighted font-semibold">{{ totalMdfe }}</span></span>
          </div>
        </div>
      </div>
    </template>

    <div v-if="!chartData.length" class="h-96 flex items-center justify-center text-muted text-sm">
      Nenhum documento emitido no período selecionado.
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
        :y="yNfe"
        color="var(--ui-primary)"
      />
      <VisArea
        :x="x"
        :y="yNfe"
        color="var(--ui-primary)"
        :opacity="0.1"
      />

      <VisLine
        :x="x"
        :y="yCte"
        color="#3b82f6"
      />
      <VisArea
        :x="x"
        :y="yCte"
        color="#3b82f6"
        :opacity="0.1"
      />

      <VisLine
        :x="x"
        :y="yMdfe"
        color="#f59e0b"
      />
      <VisArea
        :x="x"
        :y="yMdfe"
        color="#f59e0b"
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

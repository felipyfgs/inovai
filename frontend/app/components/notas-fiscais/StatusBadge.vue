<script setup lang="ts">
import { UBadge } from '#components'

const props = defineProps<{ status: string }>()

const statusColor: Record<string, string> = {
  rascunho: 'neutral',
  assinada: 'info',
  transmitida: 'info',
  autorizada: 'success',
  rejeitada: 'error',
  cancelada: 'warning',
  inutilizada: 'warning',
  denegada: 'error'
}

const color = computed(() => statusColor[props.status] || 'neutral')
const label = computed(() => props.status.charAt(0).toUpperCase() + props.status.slice(1))
</script>

<template>
  <UBadge :color="color" variant="subtle">
    {{ label }}
  </UBadge>
</template>

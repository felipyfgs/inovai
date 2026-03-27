<script setup lang="ts">
import { UBadge } from '#components'

const props = defineProps<{ status: string }>()

type BadgeColor = 'error' | 'primary' | 'secondary' | 'success' | 'info' | 'warning' | 'neutral'

const statusColor: Record<string, BadgeColor> = {
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

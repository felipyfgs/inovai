<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'

defineProps<{
  collapsed?: boolean
}>()

const items = computed<DropdownMenuItem[][]>(() => [
  [{
    label: 'InovAI',
    avatar: { text: 'IA', size: 'xs' as const },
    active: true
  }],
  [{
    label: 'Configurações',
    icon: 'i-lucide-settings',
    to: '/settings'
  }]
])
</script>

<template>
  <UDropdownMenu
    :items="items"
    :content="{ align: 'center', collisionPadding: 12 }"
    :ui="{ content: collapsed ? 'w-40' : 'w-(--reka-dropdown-menu-trigger-width)' }"
  >
    <UButton
      v-bind="{
        label: collapsed ? undefined : 'InovAI',
        trailingIcon: collapsed ? undefined : 'i-lucide-chevrons-up-down'
      }"
      color="neutral"
      variant="ghost"
      block
      :square="collapsed"
      class="data-[state=open]:bg-elevated"
      :class="[!collapsed && 'py-2']"
      :ui="{
        trailingIcon: 'text-dimmed'
      }"
    >
      <template #leading>
        <UAvatar text="IA" size="xs" />
      </template>
    </UButton>
  </UDropdownMenu>
</template>

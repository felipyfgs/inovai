<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'

defineProps<{
  collapsed?: boolean
}>()

const { currentOffice, setOffice } = useCurrentOffice()
const { isPlatformAdmin } = useAccessContext()

const selected = computed(() => {
  if (!currentOffice.value) {
    return {
      label: 'Admin',
      avatar: { text: 'AD', size: 'xs' as const }
    }
  }
  return {
    label: currentOffice.value.name,
    avatar: {
      text: currentOffice.value.name
        .split(' ')
        .slice(0, 2)
        .map(w => w[0])
        .join('')
        .toUpperCase(),
      size: 'xs' as const
    }
  }
})

async function exitOfficeContext() {
  setOffice(null)
  const { setCompany } = useCurrentCompany()
  setCompany(null)
  await navigateTo('/admin')
}

const items = computed<DropdownMenuItem[][]>(() => {
  const groups: DropdownMenuItem[][] = []

  if (currentOffice.value) {
    groups.push([{
      label: 'Painel Admin',
      icon: 'i-lucide-shield',
      onSelect: exitOfficeContext
    }])
  }

  groups.push([{
    label: 'Configurações',
    icon: 'i-lucide-settings',
    to: '/settings'
  }])

  return groups
})
</script>

<template>
  <UDropdownMenu
    v-if="isPlatformAdmin"
    :items="items"
    :content="{ align: 'center', collisionPadding: 12 }"
    :ui="{ content: collapsed ? 'w-40' : 'w-(--reka-dropdown-menu-trigger-width)' }"
  >
    <UButton
      v-bind="{
        ...selected,
        label: collapsed ? undefined : selected?.label,
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
    />
  </UDropdownMenu>
</template>

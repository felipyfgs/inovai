<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'
import type { Office, PaginatedResponse } from '~/types'

defineProps<{
  collapsed?: boolean
}>()

const { currentOffice, setOffice, initializeFromOffices } = useCurrentOffice()
const { isPlatformAdmin, isOfficeAdmin } = useAccessContext()

const { data: officesData } = useApi<PaginatedResponse<Office>>('/admin/offices', {
  lazy: true,
  query: { per_page: 200 }
})

const offices = computed<Office[]>(() => officesData.value?.data ?? [])

watchEffect(() => {
  const list = offices.value
  if (list.length > 0 && isPlatformAdmin.value) {
    initializeFromOffices(list)
  }
})

function getInitials(name: string) {
  return name
    .split(' ')
    .slice(0, 2)
    .map(w => w[0])
    .join('')
    .toUpperCase()
}

async function selectOffice(office: Office) {
  if (currentOffice.value?.id === office.id) return
  setOffice(office)
  const { setCompany } = useCurrentCompany()
  setCompany(null)
  await navigateTo('/empresas')
}

async function exitOfficeContext() {
  setOffice(null)
  const { setCompany } = useCurrentCompany()
  setCompany(null)
  await navigateTo('/admin')
}

const selectedLabel = computed(() => {
  if (!currentOffice.value) return 'Admin'
  return currentOffice.value.name
})

const selectedInitials = computed(() => {
  if (!currentOffice.value) return 'AD'
  return getInitials(currentOffice.value.name)
})

const items = computed<DropdownMenuItem[][]>(() => {
  const groups: DropdownMenuItem[][] = []

  if (isOfficeAdmin.value) {
    groups.push([{
      label: 'Painel Admin',
      icon: 'i-lucide-shield',
      onSelect() {
        exitOfficeContext()
      }
    }])
  }

  const officeItems: DropdownMenuItem[] = offices.value.map(office => ({
    label: office.name,
    suffix: office.type === 'contador' ? 'Contador' : 'Cliente',
    avatar: { text: getInitials(office.name), size: 'xs' as const },
    active: currentOffice.value?.id === office.id,
    onSelect() {
      selectOffice(office)
    }
  }))

  if (officeItems.length > 0) {
    groups.push(officeItems)
  }

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
        label: collapsed ? undefined : selectedLabel,
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
        <UAvatar :text="selectedInitials" size="xs" />
      </template>
    </UButton>
  </UDropdownMenu>
</template>

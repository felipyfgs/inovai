<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'
import type { AuthUser, Company, PaginatedResponse } from '~/types'

defineProps<{
  collapsed?: boolean
}>()

const router = useRouter()
const { user } = useSanctumAuth<AuthUser>()
const { currentCompany, setCompany, initializeFromCompanies } = useCurrentCompany()

const { data: companiesData } = useApi<PaginatedResponse<Company>>('/companies', {
  lazy: true,
  query: { per_page: 200 }
})

const companies = computed<Company[]>(() => companiesData.value?.data ?? [])

watch(companies, (list) => {
  if (list.length > 0) {
    initializeFromCompanies(list)
  }
}, { immediate: true })

const maxCompanies = computed(() => user.value?.office?.subscription?.plan?.max_companies ?? null)

const usageLabel = computed(() => {
  if (maxCompanies.value === null) return null
  return `${companies.value.length} / ${maxCompanies.value} empresas`
})

function getInitials(name: string) {
  return name
    .split(' ')
    .slice(0, 2)
    .map(w => w[0])
    .join('')
    .toUpperCase()
}

function selectCompany(company: Company) {
  setCompany(company)
  router.push('/')
}

const selectedLabel = computed(() => {
  if (!currentCompany.value) return 'Selecionar empresa'
  return currentCompany.value.fantasia || currentCompany.value.razao_social
})

const selectedInitials = computed(() => {
  if (!currentCompany.value) return '?'
  return getInitials(currentCompany.value.fantasia || currentCompany.value.razao_social)
})

const items = computed<DropdownMenuItem[][]>(() => {
  const companyItems: DropdownMenuItem[] = companies.value.map(company => ({
    label: company.fantasia || company.razao_social,
    suffix: company.cnpj,
    avatar: { text: getInitials(company.fantasia || company.razao_social), size: 'xs' },
    active: currentCompany.value?.id === company.id,
    onSelect() {
      selectCompany(company)
    }
  }))

  const actionItems: DropdownMenuItem[] = [{
    label: 'Nova Empresa',
    icon: 'i-lucide-plus-circle',
    to: '/empresas'
  }, {
    label: 'Gerenciar Empresas',
    icon: 'i-lucide-building-2',
    to: '/empresas'
  }]

  return companies.value.length > 0 ? [companyItems, actionItems] : [actionItems]
})
</script>

<template>
  <UDropdownMenu
    :items="items"
    :content="{ align: 'center', collisionPadding: 12 }"
    :ui="{ content: collapsed ? 'w-56' : 'w-(--reka-dropdown-menu-trigger-width)' }"
  >
    <UButton
      color="neutral"
      variant="ghost"
      block
      :square="collapsed"
      class="data-[state=open]:bg-elevated"
      :class="[!collapsed && 'py-2']"
    >
      <UAvatar :text="selectedInitials" size="xs" />
      <span v-if="!collapsed" class="truncate flex-1 text-left">
        {{ selectedLabel }}
      </span>
      <span v-if="!collapsed && usageLabel" class="text-xs text-dimmed shrink-0">
        {{ usageLabel }}
      </span>
      <UIcon v-if="!collapsed" name="i-lucide-chevrons-up-down" class="shrink-0 size-4 text-dimmed ms-auto" />
    </UButton>
  </UDropdownMenu>
</template>

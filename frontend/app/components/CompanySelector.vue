<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'
import type { AuthUser, Company, PaginatedResponse } from '~/types'

const { user } = useSanctumAuth<AuthUser>()
const { currentCompany, setCompany, initializeFromCompanies } = useCurrentCompany()
const { currentOffice } = useCurrentOffice()
const { isPlatformAdmin, isInCompanyContext } = useAccessContext()

const { data: companiesData } = useApi<PaginatedResponse<Company>>('/companies', {
  lazy: true,
  query: { per_page: 200 }
})

const companies = computed<Company[]>(() => companiesData.value?.data ?? [])

watch([companies, () => currentOffice.value?.id], ([list]) => {
  if (list.length > 0) {
    initializeFromCompanies(list, false)
  }
}, { immediate: true })

const maxCompanies = computed(() => user.value?.office?.subscription?.plan?.max_companies ?? null)

const usageLabel = computed(() => {
  if (maxCompanies.value === null) return null
  return `${companies.value.length} / ${maxCompanies.value}`
})

function getInitials(name: string) {
  return name
    .split(' ')
    .slice(0, 2)
    .map(w => w[0])
    .join('')
    .toUpperCase()
}

async function selectCompany(company: Company) {
  if (currentCompany.value?.id === company.id) return
  setCompany(company)
  await clearNuxtData()
  await navigateTo('/')
}

async function exitCompanyContext() {
  setCompany(null)
  await clearNuxtData()
  await navigateTo('/admin')
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
  const groups: DropdownMenuItem[][] = []

  if (isInCompanyContext.value) {
    groups.push([{
      label: 'Painel Admin',
      icon: 'i-lucide-shield',
      onSelect() {
        exitCompanyContext()
      }
    }])
  }

  const companyItems: DropdownMenuItem[] = companies.value.map(company => ({
    label: company.fantasia || company.razao_social,
    suffix: company.cnpj,
    avatar: { text: getInitials(company.fantasia || company.razao_social), size: 'xs' as const },
    active: currentCompany.value?.id === company.id,
    onSelect() {
      selectCompany(company)
    }
  }))

  if (companyItems.length > 0) {
    groups.push(companyItems)
  }

  const actionItems: DropdownMenuItem[] = [{
    label: 'Nova Empresa',
    icon: 'i-lucide-plus-circle',
    to: '/empresas'
  }, {
    label: 'Gerenciar Empresas',
    icon: 'i-lucide-building-2',
    to: '/empresas'
  }]

  groups.push(actionItems)

  return groups
})
</script>

<template>
  <!-- Escritório (não platform admin): mostra para selecionar empresa -->
  <!-- Platform admin: não mostra -->
  <UDropdownMenu
    v-if="!isPlatformAdmin"
    :items="items"
    :content="{ align: 'end', collisionPadding: 12 }"
    :ui="{ content: 'w-72' }"
  >
    <UButton
      color="neutral"
      variant="ghost"
      class="data-[state=open]:bg-elevated gap-2"
    >
      <UAvatar :text="selectedInitials" size="2xs" />
      <span class="truncate max-w-48 hidden sm:inline">
        {{ selectedLabel }}
      </span>
      <UBadge
        v-if="isInCompanyContext"
        color="success"
        variant="subtle"
        size="sm"
      >
        Contexto Empresa
      </UBadge>
      <UBadge v-else-if="usageLabel" variant="subtle" size="sm">
        {{ usageLabel }}
      </UBadge>
      <UIcon name="i-lucide-chevrons-up-down" class="shrink-0 size-4 text-dimmed" />
    </UButton>
  </UDropdownMenu>
</template>

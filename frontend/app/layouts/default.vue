<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'
import type { Company, PaginatedResponse } from '~/types'

const toast = useToast()
const { canSee, isPlatformAdmin } = useAccessContext()
const { initializeFromCompanies } = useCurrentCompany()
const { activeModules } = useCompanyModules()

const { data: companiesData } = useApi<PaginatedResponse<Company>>('/companies', {
  lazy: true,
  query: { per_page: 200 }
})

watch(() => companiesData.value?.data, (list) => {
  if (list && list.length > 0) {
    initializeFromCompanies(list)
  }
}, { immediate: true })

const open = ref(false)

function getFiscalChildren(): NavigationMenuItem[] {
  const items: NavigationMenuItem[] = []
  const closeSidebar = () => {
    open.value = false
  }
  if (activeModules.value.includes('nfe')) {
    items.push({ label: 'NF-e', to: '/fiscal/nfe', onSelect: closeSidebar })
  }
  if (activeModules.value.includes('nfce')) {
    items.push({ label: 'NFC-e', to: '/fiscal/nfce', onSelect: closeSidebar })
  }
  if (activeModules.value.includes('cte')) {
    items.push({ label: 'CT-e', to: '/fiscal/cte', onSelect: closeSidebar })
  }
  if (activeModules.value.includes('mdfe')) {
    items.push({ label: 'MDF-e', to: '/fiscal/mdfe', onSelect: closeSidebar })
  }
  if (activeModules.value.includes('nfse')) {
    items.push({ label: 'NFS-e', to: '/fiscal/nfse', onSelect: closeSidebar })
  }
  return items
}

const companyLinks = computed(() => [{
  label: 'Início',
  icon: 'i-lucide-house',
  to: '/',
  module: 'inicio',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Cadastros',
  icon: 'i-lucide-database',
  type: 'trigger' as const,
  module: 'cadastros',
  children: [{
    label: 'Pessoas',
    to: '/cadastros/pessoas',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Fornecedores',
    to: '/cadastros/fornecedores',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Produtos',
    to: '/cadastros/produtos',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Transportadoras',
    to: '/cadastros/transportadoras',
    onSelect: () => {
      open.value = false
    }
  }]
}, {
  label: 'Comercial',
  icon: 'i-lucide-shopping-cart',
  type: 'trigger' as const,
  module: 'comercial',
  children: [{
    label: 'Orçamentos',
    to: '/comercial/orcamentos',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Pedidos',
    to: '/comercial/pedidos',
    onSelect: () => {
      open.value = false
    }
  }]
}, {
  label: 'Fiscal',
  icon: 'i-lucide-file-text',
  type: 'trigger' as const,
  module: 'fiscal',
  children: getFiscalChildren()
}, {
  label: 'Financeiro',
  icon: 'i-lucide-wallet',
  type: 'trigger' as const,
  module: 'financeiro',
  children: [{
    label: 'Contas a Pagar',
    to: '/financeiro/contas-pagar',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Contas a Receber',
    to: '/financeiro/contas-receber',
    onSelect: () => {
      open.value = false
    }
  }]
}, {
  label: 'Restaurante',
  icon: 'i-lucide-utensils',
  to: '/restaurante',
  module: 'restaurante',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Estoque',
  icon: 'i-lucide-warehouse',
  to: '/estoque',
  module: 'estoque',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Início',
  icon: 'i-lucide-building',
  to: '/escritorio',
  module: 'dashboard-office',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Planos',
  icon: 'i-lucide-package',
  to: '/escritorio/planos',
  module: 'dashboard-office',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Equipe',
  icon: 'i-lucide-users',
  to: '/escritorio/equipe',
  module: 'dashboard-office',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Assinaturas',
  icon: 'i-lucide-credit-card',
  to: '/escritorio/assinaturas',
  module: 'dashboard-office',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Cobranças',
  icon: 'i-lucide-receipt',
  to: '/escritorio/cobrancas',
  module: 'dashboard-office',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Empresas',
  icon: 'i-lucide-building-2',
  to: '/empresas',
  module: 'empresas',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Configurações',
  icon: 'i-lucide-settings',
  to: '/settings',
  module: 'config',
  type: 'trigger' as const,
  defaultOpen: true,
  children: [{
    label: 'Geral',
    to: '/settings',
    exact: true,
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Emitente',
    to: '/configuracoes/emitente',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Segurança',
    to: '/settings/security',
    onSelect: () => {
      open.value = false
    }
  }]
}])

const adminLinks = [{
  label: 'Dashboard',
  icon: 'i-lucide-layout-dashboard',
  to: '/admin',
  exact: true,
  module: 'admin-dashboard',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Escritórios',
  icon: 'i-lucide-building-2',
  to: '/admin/escritorios',
  module: 'admin-escritorios',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Planos',
  icon: 'i-lucide-package',
  to: '/admin/planos',
  module: 'admin-planos',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Cobranças',
  icon: 'i-lucide-receipt',
  to: '/admin/cobrancas',
  module: 'admin-cobrancas',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Configurações',
  icon: 'i-lucide-settings',
  module: 'config',
  type: 'trigger' as const,
  children: [{
    label: 'Geral',
    to: '/settings',
    exact: true,
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Segurança',
    to: '/settings/security',
    onSelect: () => {
      open.value = false
    }
  }]
}]

const bottomLinks = [{
  label: 'Ajuda',
  icon: 'i-lucide-info',
  to: '/settings',
  module: 'config',
  onSelect: () => {
    open.value = false
  }
}]

const allLinks = computed<(NavigationMenuItem & { module?: string })[]>(() => {
  if (isPlatformAdmin.value) {
    return [...adminLinks]
  }
  return [...companyLinks.value]
})

const links = computed<NavigationMenuItem[][]>(() => [
  allLinks.value.filter(item => !item.module || canSee(item.module as 'config')),
  bottomLinks
])

const groups = computed(() => [{
  id: 'links',
  label: 'Ir para',
  items: links.value.flat()
}] as unknown as import('@nuxt/ui').CommandPaletteGroup[])

onMounted(async () => {
  const cookie = useCookie('cookie-consent')
  if (cookie.value === 'accepted') {
    return
  }

  toast.add({
    title: 'Utilizamos cookies para melhorar sua experiência.',
    duration: 0,
    close: false,
    actions: [{
      label: 'Aceitar',
      color: 'neutral',
      variant: 'outline',
      onClick: () => {
        cookie.value = 'accepted'
      }
    }, {
      label: 'Recusar',
      color: 'neutral',
      variant: 'ghost'
    }]
  })
})
</script>

<template>
  <UDashboardGroup unit="rem">
    <UDashboardSidebar
      id="default"
      v-model:open="open"
      collapsible
      resizable
      class="bg-elevated/25"
      :ui="{ footer: 'lg:border-t lg:border-default' }"
    >
      <template #header="{ collapsed }">
        <NuxtLink v-if="isPlatformAdmin" to="/admin" class="flex items-center gap-2 px-2.5 py-2">
          <UIcon name="i-lucide-shield-check" class="size-6 text-primary shrink-0" />
          <span v-if="!collapsed" class="font-semibold text-sm truncate">InovAI Admin</span>
        </NuxtLink>
        <TeamsMenu v-else :collapsed="collapsed" />
      </template>

      <template #default="{ collapsed }">
        <CompanyContextIndicator v-if="!collapsed" />
        <UDashboardSearchButton :collapsed="collapsed" class="bg-transparent ring-default" />

        <UNavigationMenu
          :collapsed="collapsed"
          :items="links[0]"
          orientation="vertical"
          tooltip
          popover
        />

        <UNavigationMenu
          :collapsed="collapsed"
          :items="links[1]"
          orientation="vertical"
          tooltip
          class="mt-auto"
        />
      </template>

      <template #footer="{ collapsed }">
        <UserMenu :collapsed="collapsed" />
      </template>
    </UDashboardSidebar>

    <UDashboardSearch :groups="groups" />

    <slot />
  </UDashboardGroup>
</template>

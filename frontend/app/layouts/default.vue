<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

const toast = useToast()
const { canSee, isPlatformAdmin } = useAccessContext()

const open = ref(false)

const companyLinks = [{
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
  children: [{
    label: 'NF-e',
    to: '/fiscal/nfe',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'NFC-e',
    to: '/fiscal/nfce',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'CT-e',
    to: '/fiscal/cte',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'MDF-e',
    to: '/fiscal/mdfe',
    onSelect: () => {
      open.value = false
    }
  }]
}, {
  label: 'Estoque',
  icon: 'i-lucide-warehouse',
  to: '/estoque',
  module: 'estoque',
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
  label: 'Usuários',
  icon: 'i-lucide-users',
  to: '/usuarios',
  module: 'usuarios',
  onSelect: () => {
    open.value = false
  }
}]

const configLink = {
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
  } as NavigationMenuItem, {
    label: 'Integrações',
    to: '/settings/integrations',
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
}

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
    return [...adminLinks, configLink]
  }
  return [...companyLinks, configLink]
})

const links = computed<NavigationMenuItem[][]>(() => [
  allLinks.value.filter(item => !item.module || canSee(item.module as 'config')),
  bottomLinks
])

const groups = computed(() => [{
  id: 'links',
  label: 'Ir para',
  items: links.value.flat() as NavigationMenuItem[]
}])

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
        <OfficeSelector v-if="isPlatformAdmin" :collapsed="collapsed" />
        <TeamsMenu v-else :collapsed="collapsed" />
      </template>

      <template #default="{ collapsed }">
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

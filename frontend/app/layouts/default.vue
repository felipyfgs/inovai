<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'
import type { AuthUser } from '~/types'

const route = useRoute()
const toast = useToast()
const { user } = useSanctumAuth<AuthUser>()

const isAdmin = computed(() => user.value?.roles?.some(r => r.name === 'admin') ?? false)

const open = ref(false)

const baseLinks = [{
  label: 'Início',
  icon: 'i-lucide-house',
  to: '/',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Empresas',
  icon: 'i-lucide-building-2',
  to: '/empresas',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Usuários',
  icon: 'i-lucide-users',
  to: '/usuarios',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Cadastros',
  icon: 'i-lucide-database',
  type: 'trigger',
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
  type: 'trigger',
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
  type: 'trigger',
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
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Configurações',
  to: '/settings',
  icon: 'i-lucide-settings',
  defaultOpen: true,
  type: 'trigger',
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

const adminLinks = [{
  label: 'Admin',
  icon: 'i-lucide-shield',
  type: 'trigger' as const,
  children: [{
    label: 'Dashboard Admin',
    to: '/admin',
    exact: true,
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Contadores & Clientes',
    to: '/admin/contadores',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Cobranças',
    to: '/admin/cobrancas',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Mapa de Empresas',
    to: '/admin/mapa',
    onSelect: () => {
      open.value = false
    }
  }]
}]

const bottomLinks = [{
  label: 'Ajuda',
  icon: 'i-lucide-info',
  to: '/settings',
  onSelect: () => {
    open.value = false
  }
}]

const links = computed<NavigationMenuItem[][]>(() => [
  [...baseLinks, ...(isAdmin.value ? adminLinks : [])],
  bottomLinks
])

const groups = computed(() => [{
  id: 'links',
  label: 'Ir para',
  items: links.value.flat()
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
        <TeamsMenu :collapsed="collapsed" />
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

    <NotificationsSlideover />
  </UDashboardGroup>
</template>

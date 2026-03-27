<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

const links = [[{
  label: 'Contas a Pagar',
  icon: 'i-lucide-arrow-up-right',
  to: '/financeiro/contas-pagar',
  exact: true
}, {
  label: 'Contas a Receber',
  icon: 'i-lucide-arrow-down-left',
  to: '/financeiro/contas-receber'
}]] satisfies NavigationMenuItem[][]
</script>

<template>
  <UDashboardPanel id="financeiro" :ui="{ body: 'lg:py-12' }">
    <template #header>
      <UDashboardNavbar title="Financeiro">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right />
      </UDashboardNavbar>

      <UDashboardToolbar>
        <UNavigationMenu :items="links" highlight class="-mx-1 flex-1" />
      </UDashboardToolbar>
    </template>

    <template #body>
      <NuxtPage />
    </template>
  </UDashboardPanel>
</template>

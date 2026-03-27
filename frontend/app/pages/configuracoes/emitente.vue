<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

const links = [[{
  label: 'Dados',
  icon: 'i-lucide-building-2',
  to: '/configuracoes/emitente',
  exact: true
}, {
  label: 'Certificado',
  icon: 'i-lucide-shield',
  to: '/configuracoes/emitente/certificado'
}, {
  label: 'Numeração',
  icon: 'i-lucide-hash',
  to: '/configuracoes/emitente/numeracao'
}, {
  label: 'NFC-e / CSC',
  icon: 'i-lucide-smartphone',
  to: '/configuracoes/emitente/csc'
}, {
  label: 'Ambiente',
  icon: 'i-lucide-server',
  to: '/configuracoes/emitente/ambiente'
}]] satisfies NavigationMenuItem[][]
</script>

<template>
  <UDashboardPanel id="emitente" :ui="{ body: 'lg:py-12' }">
    <template #header>
      <UDashboardNavbar title="Configurações do Emitente">
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
      <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-2xl mx-auto">
        <NuxtPage />
      </div>
    </template>
  </UDashboardPanel>
</template>

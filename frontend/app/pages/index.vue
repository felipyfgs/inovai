<script setup lang="ts">
import type { AuthUser } from '~/types'

const { currentCompany } = useCurrentCompany()
const { user } = useSanctumAuth<AuthUser>()
const isAdmin = computed(() => user.value?.roles?.some(r => r.name === 'admin') ?? false)
</script>

<template>
  <UDashboardPanel id="home">
    <template #header>
      <UDashboardNavbar
        :title="currentCompany ? (currentCompany.fantasia || currentCompany.razao_social) : 'Início'"
      >
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <HomeCompanyDashboard v-if="currentCompany" />
      <HomeOfficeDashboard v-else />
    </template>
  </UDashboardPanel>
</template>

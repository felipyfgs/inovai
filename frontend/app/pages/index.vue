<script setup lang="ts">
const { currentCompany } = useCurrentCompany()
const { currentOffice } = useCurrentOffice()
const { isPlatformAdmin } = useAccessContext()

onMounted(() => {
  if (isPlatformAdmin.value) {
    navigateTo('/admin')
  } else if (!currentOffice.value && !currentCompany.value) {
    navigateTo('/empresas')
  }
})
</script>

<template>
  <UDashboardPanel id="home">
    <template #header>
      <UDashboardNavbar title="Início">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <BackToAdmin />
          <CompanySelector />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <HomeCompanyDashboard v-if="currentCompany" />
      <HomeOfficeDashboard v-else-if="currentOffice" />
      <div v-else class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>
    </template>
  </UDashboardPanel>
</template>

<script setup lang="ts">
</script>

<template>
  <UDashboardPanel id="estoque">
    <template #header>
      <UDashboardNavbar title="Estoque">
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
      <div class="flex flex-1 items-center justify-center">
        <div class="text-center">
          <UIcon name="i-lucide-warehouse" class="size-16 text-dimmed mx-auto" />
          <p class="mt-4 text-lg font-medium text-highlighted">
            Estoque
          </p>
          <p class="text-muted">
            Módulo de estoque em desenvolvimento.
          </p>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>

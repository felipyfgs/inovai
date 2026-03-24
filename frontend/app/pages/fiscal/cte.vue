<script setup lang="ts">
</script>

<template>
  <UDashboardPanel id="cte">
    <template #header>
      <UDashboardNavbar title="CT-e (Conhecimento de Transporte)">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UButton
            label="Novo CT-e"
            icon="i-lucide-plus"
            size="md"
            class="rounded-full"
          />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex flex-1 items-center justify-center">
        <div class="text-center">
          <UIcon name="i-lucide-truck" class="size-16 text-dimmed mx-auto" />
          <p class="mt-4 text-lg font-medium text-highlighted">
            CT-e
          </p>
          <p class="text-muted">
            Módulo de CT-e em desenvolvimento.
          </p>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>

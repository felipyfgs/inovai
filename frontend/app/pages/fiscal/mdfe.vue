<script setup lang="ts">
</script>

<template>
  <UDashboardPanel id="mdfe">
    <template #header>
      <UDashboardNavbar title="MDF-e (Manifesto de Documentos)">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UButton
            label="Novo MDF-e"
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
          <UIcon name="i-lucide-clipboard-list" class="size-16 text-dimmed mx-auto" />
          <p class="mt-4 text-lg font-medium text-highlighted">
            MDF-e
          </p>
          <p class="text-muted">
            Módulo de MDF-e em desenvolvimento.
          </p>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>

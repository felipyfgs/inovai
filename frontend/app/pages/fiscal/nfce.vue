<script setup lang="ts">
</script>

<template>
  <UDashboardPanel id="nfce">
    <template #header>
      <UDashboardNavbar title="NFC-e (Nota Fiscal ao Consumidor)">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <BackToAdmin />
          <CompanySelector />
          <UButton
            label="Nova NFC-e"
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
          <UIcon name="i-lucide-receipt" class="size-16 text-dimmed mx-auto" />
          <p class="mt-4 text-lg font-medium text-highlighted">
            NFC-e
          </p>
          <p class="text-muted">
            Módulo de NFC-e em desenvolvimento.
          </p>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>

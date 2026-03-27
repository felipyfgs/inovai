<script setup lang="ts">
const { currentCompany, setCompany } = useCurrentCompany()
const { isInCompanyContext } = useAccessContext()

async function exitCompanyContext() {
  setCompany(null)
  await clearNuxtData()
  await navigateTo('/')
}
</script>

<template>
  <div
    v-if="isInCompanyContext && currentCompany"
    class="mx-2 mb-1 rounded-md bg-primary/10 border border-primary/20 flex items-center gap-2 px-2.5 py-1.5"
  >
    <UIcon name="i-lucide-building-2" class="size-3.5 text-primary shrink-0" />
    <span class="text-xs text-primary truncate flex-1">
      {{ currentCompany.fantasia || currentCompany.razao_social }}
    </span>
    <UButton
      icon="i-lucide-x"
      color="neutral"
      variant="ghost"
      size="xs"
      class="shrink-0 -my-0.5 -mr-0.5"
      @click="exitCompanyContext"
    />
  </div>
</template>

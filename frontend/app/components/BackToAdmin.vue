<script setup lang="ts">
const { isOfficeAdmin, isInCompanyContext } = useAccessContext()
const { setCompany } = useCurrentCompany()
const { setOffice } = useCurrentOffice()

async function exitToOffice() {
  setCompany(null)
  await navigateTo('/')
}

async function exitToPlatformAdmin() {
  setCompany(null)
  setOffice(null)
  await navigateTo('/admin')
}
</script>

<template>
  <!-- In company context: show "Voltar ao Escritório" -->
  <UButton
    v-if="isInCompanyContext"
    color="warning"
    variant="subtle"
    size="sm"
    icon="i-lucide-building-2"
    label="Voltar ao Escritório"
    @click="exitToOffice"
  />
  <!-- In office context (escritório): show "Voltar ao Admin Plataforma" -->
  <UButton
    v-else-if="isOfficeAdmin"
    color="warning"
    variant="subtle"
    size="sm"
    icon="i-lucide-shield"
    label="Admin Plataforma"
    @click="exitToPlatformAdmin"
  />
</template>

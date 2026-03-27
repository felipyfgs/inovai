<script setup lang="ts">
import type { AuthUser, Company } from '~/types'

const colorMode = useColorMode()
const { user } = useSanctumAuth<AuthUser>()
const { getStoredCompanyId, setCompany } = useCurrentCompany()
const { setOffice } = useCurrentOffice()
const { $sanctumClient } = useNuxtApp()

watch(() => user.value?.office, (office) => {
  if (office) {
    setOffice(office)
  }
}, { immediate: true })

onMounted(async () => {
  const storedId = getStoredCompanyId()
  if (!storedId || !user.value) return

  try {
    const company = await $sanctumClient<Company>(`/api/companies/${storedId}`)
    setCompany(company)
  } catch {
    setCompany(null)
  }
})

const color = computed(() => colorMode.value === 'dark' ? '#1b1718' : 'white')

useHead({
  meta: [
    { charset: 'utf-8' },
    { name: 'viewport', content: 'width=device-width, initial-scale=1' },
    { key: 'theme-color', name: 'theme-color', content: color }
  ],
  link: [
    { rel: 'icon', href: '/favicon.ico' }
  ],
  htmlAttrs: {
    lang: 'en'
  }
})

const title = 'Nuxt Dashboard Template'
const description = 'A professional dashboard template built with Nuxt UI, featuring multiple pages, data visualization, and comprehensive management capabilities for creating powerful admin interfaces.'

useSeoMeta({
  title,
  description,
  ogTitle: title,
  ogDescription: description,
  ogImage: 'https://ui.nuxt.com/assets/templates/nuxt/dashboard-light.png',
  twitterImage: 'https://ui.nuxt.com/assets/templates/nuxt/dashboard-light.png',
  twitterCard: 'summary_large_image'
})
</script>

<template>
  <UApp>
    <NuxtLoadingIndicator />

    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </UApp>
</template>

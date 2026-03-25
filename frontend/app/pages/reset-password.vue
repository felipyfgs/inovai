<script setup lang="ts">
definePageMeta({
  layout: 'auth',
  sanctum: { guestOnly: true }
})

const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()
const toast = useToast()
const { extractMessage } = useApiError()

const form = reactive({
  email: (route.query.email as string) || '',
  password: '',
  password_confirmation: '',
  token: (route.query.token as string) || ''
})
const loading = ref(false)

async function handleSubmit() {
  loading.value = true
  try {
    await $fetch('/api/reset-password', {
      baseURL: config.public.apiBase as string,
      method: 'POST',
      body: form
    })
    toast.add({ title: 'Senha redefinida!', description: 'Faça login com sua nova senha.', color: 'success' })
    router.push('/login')
  } catch (e: unknown) {
    const message = extractMessage(e) || 'Erro ao redefinir senha.'
    toast.add({ title: 'Erro', description: message, color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-highlighted">InovAI</h1>
      <p class="text-muted mt-2">Redefinir senha</p>
    </div>

    <UCard>
      <template #header>
        <h2 class="text-lg font-semibold">Nova senha</h2>
      </template>

      <form class="flex flex-col gap-4" @submit.prevent="handleSubmit">
        <UFormField label="E-mail">
          <UInput
            v-model="form.email"
            type="email"
            placeholder="seu@email.com"
            icon="i-lucide-mail"
            required
            class="w-full"
          />
        </UFormField>

        <UFormField label="Nova Senha">
          <UInput
            v-model="form.password"
            type="password"
            placeholder="Mínimo 6 caracteres"
            icon="i-lucide-lock"
            required
            class="w-full"
          />
        </UFormField>

        <UFormField label="Confirmar Senha">
          <UInput
            v-model="form.password_confirmation"
            type="password"
            placeholder="Repita a senha"
            icon="i-lucide-lock"
            required
            class="w-full"
          />
        </UFormField>

        <UButton
          type="submit"
          label="Redefinir senha"
          block
          :loading="loading"
          size="lg"
        />
      </form>

      <template #footer>
        <p class="text-center text-sm text-muted">
          <NuxtLink to="/login" class="text-primary font-medium">Voltar ao login</NuxtLink>
        </p>
      </template>
    </UCard>
  </div>
</template>

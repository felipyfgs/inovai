<script setup lang="ts">
definePageMeta({
  layout: 'auth',
  sanctum: { guestOnly: true }
})

const config = useRuntimeConfig()
const toast = useToast()
const { extractMessage } = useApiError()

const email = ref('')
const loading = ref(false)
const sent = ref(false)

async function handleSubmit() {
  loading.value = true
  try {
    await $fetch('/api/forgot-password', {
      baseURL: config.public.apiBase as string,
      method: 'POST',
      body: { email: email.value }
    })
    sent.value = true
    toast.add({ title: 'E-mail enviado!', description: 'Verifique sua caixa de entrada.', color: 'success' })
  } catch (e: unknown) {
    const message = extractMessage(e) || 'Erro ao enviar e-mail.'
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
      <p class="text-muted mt-2">Recuperação de senha</p>
    </div>

    <UCard>
      <template #header>
        <h2 class="text-lg font-semibold">Esqueceu a senha?</h2>
      </template>

      <div v-if="sent" class="text-center py-4">
        <UIcon name="i-lucide-mail-check" class="size-12 text-success mx-auto mb-4" />
        <p class="text-highlighted font-medium mb-2">E-mail enviado!</p>
        <p class="text-sm text-muted">Verifique sua caixa de entrada para redefinir sua senha.</p>
      </div>

      <form v-else class="flex flex-col gap-4" @submit.prevent="handleSubmit">
        <p class="text-sm text-muted">
          Informe seu e-mail e enviaremos um link para redefinir sua senha.
        </p>

        <UFormField label="E-mail">
          <UInput
            v-model="email"
            type="email"
            placeholder="seu@email.com"
            icon="i-lucide-mail"
            required
            class="w-full"
          />
        </UFormField>

        <UButton
          type="submit"
          label="Enviar link"
          block
          :loading="loading"
          size="lg"
        />
      </form>

      <template #footer>
        <p class="text-center text-sm text-muted">
          Lembrou a senha?
          <NuxtLink to="/login" class="text-primary font-medium">Entrar</NuxtLink>
        </p>
      </template>
    </UCard>
  </div>
</template>

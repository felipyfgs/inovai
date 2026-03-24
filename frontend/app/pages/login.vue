<script setup lang="ts">
definePageMeta({
  layout: 'auth'
})

const { login } = useSanctumAuth()
const router = useRouter()
const toast = useToast()

const form = reactive({
  email: '',
  password: ''
})
const loading = ref(false)

async function handleLogin() {
  loading.value = true
  try {
    await login({
      email: form.email,
      password: form.password
    })
    router.push('/')
  } catch (e: any) {
    toast.add({
      title: 'Erro ao entrar',
      description: e?.response?._data?.message || 'Credenciais inválidas.',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-highlighted">
        InovAI
      </h1>
      <p class="text-muted mt-2">
        Sistema de Emissão de Notas Fiscais
      </p>
    </div>

    <UCard>
      <template #header>
        <h2 class="text-lg font-semibold">
          Entrar
        </h2>
      </template>

      <form class="flex flex-col gap-4" @submit.prevent="handleLogin">
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

        <UFormField label="Senha">
          <UInput
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            icon="i-lucide-lock"
            required
            class="w-full"
          />
        </UFormField>

        <UButton
          type="submit"
          label="Entrar"
          block
          :loading="loading"
          size="lg"
        />
      </form>

      <template #footer>
        <p class="text-center text-sm text-muted">
          Não tem conta?
          <NuxtLink to="/register" class="text-primary font-medium">
            Cadastre-se
          </NuxtLink>
        </p>
      </template>
    </UCard>
  </div>
</template>

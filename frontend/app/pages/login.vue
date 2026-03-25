<script setup lang="ts">
import type { AuthUser } from '~/types'

definePageMeta({
  layout: 'auth',
  sanctum: { guestOnly: true }
})

const { login, user } = useSanctumAuth<AuthUser>()
const router = useRouter()
const toast = useToast()
const { handleError } = useApiError()

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
    if (user.value?.must_change_password) {
      toast.add({ title: 'Troque sua senha', description: 'Por segurança, defina uma nova senha.', color: 'warning' })
      router.push('/settings/security')
    } else {
      router.push('/')
    }
  } catch (e: unknown) {
    handleError(e, 'Credenciais inválidas')
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

        <div class="flex justify-end">
          <NuxtLink to="/forgot-password" class="text-sm text-primary font-medium">
            Esqueceu a senha?
          </NuxtLink>
        </div>

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

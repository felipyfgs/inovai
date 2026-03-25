<script setup lang="ts">
definePageMeta({
  layout: 'auth',
  sanctum: { guestOnly: true }
})

const config = useRuntimeConfig()
const router = useRouter()
const toast = useToast()
const { extractMessage } = useApiError()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  office_name: '',
  office_cnpj: ''
})
const loading = ref(false)

async function handleRegister() {
  loading.value = true
  try {
    await $fetch('/api/register', {
      baseURL: config.public.apiBase as string,
      method: 'POST',
      body: form
    })
    toast.add({
      title: 'Conta criada com sucesso!',
      description: 'Faça login para continuar.'
    })
    router.push('/login')
  } catch (e: unknown) {
    const message = extractMessage(e) || 'Erro ao cadastrar.'
    toast.add({
      title: 'Erro ao cadastrar',
      description: message,
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
        Crie sua conta de escritório contábil
      </p>
    </div>

    <UCard>
      <template #header>
        <h2 class="text-lg font-semibold">
          Cadastro
        </h2>
      </template>

      <form class="flex flex-col gap-4" @submit.prevent="handleRegister">
        <UFormField label="Nome do Escritório" required>
          <UInput
            v-model="form.office_name"
            placeholder="Escritório Contábil XYZ"
            icon="i-lucide-building-2"
            required
            class="w-full"
          />
        </UFormField>

        <UFormField label="CNPJ do Escritório">
          <UInput
            v-model="form.office_cnpj"
            placeholder="00.000.000/0001-00"
            icon="i-lucide-file-text"
            class="w-full"
          />
        </UFormField>

        <UFormField label="Seu Nome" required>
          <UInput
            v-model="form.name"
            placeholder="João da Silva"
            icon="i-lucide-user"
            required
            class="w-full"
          />
        </UFormField>

        <UFormField label="E-mail" required>
          <UInput
            v-model="form.email"
            type="email"
            placeholder="seu@email.com"
            icon="i-lucide-mail"
            required
            class="w-full"
          />
        </UFormField>

        <UFormField label="Telefone">
          <UInput
            v-model="form.phone"
            placeholder="(11) 99999-9999"
            icon="i-lucide-phone"
            class="w-full"
          />
        </UFormField>

        <UFormField label="Senha" required>
          <UInput
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            icon="i-lucide-lock"
            required
            class="w-full"
          />
        </UFormField>

        <UFormField label="Confirmar Senha" required>
          <UInput
            v-model="form.password_confirmation"
            type="password"
            placeholder="••••••••"
            icon="i-lucide-lock"
            required
            class="w-full"
          />
        </UFormField>

        <UButton
          type="submit"
          label="Cadastrar"
          block
          :loading="loading"
          size="lg"
        />
      </form>

      <template #footer>
        <p class="text-center text-sm text-muted">
          Já tem conta?
          <NuxtLink to="/login" class="text-primary font-medium">
            Entrar
          </NuxtLink>
        </p>
      </template>
    </UCard>
  </div>
</template>

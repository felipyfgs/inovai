<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { AuthUser } from '~/types'

definePageMeta({
  layout: 'default'
})

const toast = useToast()
const { user, refreshIdentity } = useSanctumAuth<AuthUser>()

const profileSchema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  email: z.string().email('E-mail inválido'),
  phone: z.string().optional()
})

type ProfileSchema = z.output<typeof profileSchema>

const state = reactive<Partial<ProfileSchema>>({})
const loading = ref(false)

watch(user, (u) => {
  if (u) {
    state.name = u.name
    state.email = u.email
    state.phone = u.phone ?? ''
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<ProfileSchema>) {
  loading.value = true
  try {
    const { put } = useApiMutation()
    await put('/me', event.data)
    await refreshIdentity()
    toast.add({ title: 'Perfil atualizado', color: 'success' })
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao atualizar perfil.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UPageHeader
    title="Meu Perfil"
    description="Gerencie suas informações pessoais."
  />

  <UForm
    :schema="profileSchema"
    :state="state"
    class="mt-6"
    @submit="onSubmit"
  >
    <UPageCard variant="subtle">
      <UFormField
        name="name"
        label="Nome"
        description="Seu nome completo."
        required
        class="flex max-sm:flex-col justify-between items-start gap-4"
      >
        <UInput
          v-model="state.name"
          autocomplete="off"
          class="w-full max-w-md"
        />
      </UFormField>

      <USeparator />

      <UFormField
        name="email"
        label="E-mail"
        description="Used for sign in and notifications."
        required
        class="flex max-sm:flex-col justify-between items-start gap-4"
      >
        <UInput
          v-model="state.email"
          type="email"
          autocomplete="off"
          class="w-full max-w-md"
        />
      </UFormField>

      <USeparator />

      <UFormField
        name="phone"
        label="Telefone"
        description="Telefone de contato."
        class="flex max-sm:flex-col justify-between items-start gap-4"
      >
        <UInput
          v-model="state.phone"
          type="tel"
          autocomplete="off"
          class="w-full max-w-md"
        />
      </UFormField>

      <USeparator />

      <div class="flex justify-end pt-2">
        <UButton
          type="submit"
          label="Salvar alterações"
          :loading="loading"
        />
      </div>
    </UPageCard>
  </UForm>
</template>

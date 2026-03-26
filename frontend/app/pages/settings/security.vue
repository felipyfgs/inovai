<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

definePageMeta({
  layout: 'default'
})

const toast = useToast()
const loading = ref(false)
const deleteOpen = ref(false)
const deleteLoading = ref(false)

const passwordSchema = z.object({
  current_password: z.string().min(6, 'Mínimo 6 caracteres'),
  password: z.string().min(6, 'Mínimo 6 caracteres'),
  password_confirmation: z.string()
}).refine(data => data.password === data.password_confirmation, {
  message: 'Senhas não conferem',
  path: ['password_confirmation']
})

type PasswordSchema = z.output<typeof passwordSchema>

const state = reactive<Partial<PasswordSchema>>({
  current_password: '',
  password: '',
  password_confirmation: ''
})

async function onSubmit(event: FormSubmitEvent<PasswordSchema>) {
  loading.value = true
  try {
    const { put } = useApiMutation()
    await put('/me/password', event.data)
    toast.add({ title: 'Senha alterada com sucesso', color: 'success' })
    resetForm()
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao alterar senha.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function resetForm() {
  state.current_password = ''
  state.password = ''
  state.password_confirmation = ''
}

async function deleteAccount() {
  deleteLoading.value = true
  try {
    const { del } = useApiMutation()
    await del('/me')
    toast.add({ title: 'Conta excluída com sucesso', color: 'success' })
    await navigateTo('/login')
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao excluir conta.', color: 'error' })
  } finally {
    deleteLoading.value = false
  }
}
</script>

<template>
  <UPageHeader
    title="Segurança"
    description="Gerencie suas configurações de segurança."
  />

  <div class="mt-6 space-y-6">
    <UPageCard
      title="Alterar Senha"
      description="Confirme sua senha atual antes de definir uma nova."
      variant="subtle"
    >
      <UForm
        :schema="passwordSchema"
        :state="state"
        class="flex flex-col gap-4 max-w-sm mt-4"
        @submit="onSubmit"
      >
        <UFormField name="current_password" label="Senha atual" required>
          <UInput
            v-model="state.current_password"
            type="password"
            class="w-full"
          />
        </UFormField>

        <UFormField name="password" label="Nova senha" required>
          <UInput
            v-model="state.password"
            type="password"
            class="w-full"
          />
        </UFormField>

        <UFormField name="password_confirmation" label="Confirmar nova senha" required>
          <UInput
            v-model="state.password_confirmation"
            type="password"
            class="w-full"
          />
        </UFormField>

        <UButton
          type="submit"
          label="Alterar senha"
          :loading="loading"
          class="w-fit"
        />
      </UForm>
    </UPageCard>

    <UPageCard
      title="Conta"
      description="Não quer mais usar nosso serviço? Você pode excluir sua conta aqui. Esta ação não é reversível. Todas as informações relacionadas a esta conta serão excluídas permanentemente."
      class="bg-gradient-to-tl from-error/10 from-5% to-default"
    >
      <template #footer>
        <UButton label="Excluir conta" color="error" @click="deleteOpen = true" />
      </template>
    </UPageCard>

    <UModal
      v-model:open="deleteOpen"
      title="Excluir conta"
      description="Tem certeza? Esta ação não pode ser desfeita. Todas as informações serão excluídas permanentemente."
      :ui="{ footer: 'justify-end' }"
    >
      <template #footer>
        <UButton
          label="Cancelar"
          color="neutral"
          variant="outline"
          @click="deleteOpen = false"
        />
        <UButton
          label="Excluir permanentemente"
          color="error"
          :loading="deleteLoading"
          @click="deleteAccount"
        />
      </template>
    </UModal>
  </div>
</template>

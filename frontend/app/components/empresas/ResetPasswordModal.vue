<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { AppUser } from '~/types'

import { UButton } from '#components'

const props = defineProps<{
  user: AppUser
  companyId: number
}>()

const emit = defineEmits<{
  updated: []
}>()

const toast = useToast()
const { put } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const open = ref(true)
const loading = ref(false)

const schema = z.object({
  password: z.string().min(8, 'Mínimo 8 caracteres'),
  password_confirmation: z.string().min(8, 'Confirme a senha')
}).refine(data => data.password === data.password_confirmation, {
  message: 'As senhas não conferem',
  path: ['password_confirmation']
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  password: '',
  password_confirmation: ''
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await put(`/companies/${props.companyId}/users/${props.user.id}/reset-password`, event.data)
    toast.add({ title: 'Senha redefinida', description: `Senha de ${props.user.name} alterada.`, color: 'success' })
    open.value = false
    emit('updated')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao redefinir senha.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Redefinir Senha" :description="`${user.name} (${user.email})`">
    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        @submit="onSubmit"
      >
        <div class="space-y-4">
          <UFormField label="Nova Senha" name="password" required>
            <UInput
              v-model="state.password"
              type="password"
              placeholder="Mínimo 8 caracteres"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Confirmar Senha" name="password_confirmation" required>
            <UInput
              v-model="state.password_confirmation"
              type="password"
              placeholder="Repita a senha"
              class="w-full"
            />
          </UFormField>

          <p class="text-xs text-muted">
            O usuário será obrigado a alterar a senha no próximo acesso.
          </p>

          <div class="flex justify-end gap-3">
            <UButton
              label="Cancelar"
              color="neutral"
              variant="outline"
              @click="open = false"
            />
            <UButton
              label="Redefinir Senha"
              color="primary"
              :loading="loading"
              type="submit"
            />
          </div>
        </div>
      </UForm>
    </template>
  </UModal>
</template>

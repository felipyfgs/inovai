<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { AppUser } from '~/types'

const props = defineProps<{
  user: AppUser
}>()

const emit = defineEmits<{ updated: [] }>()

const open = ref(true)
const loading = ref(false)
const toast = useToast()
const { put } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  email: z.string().email('E-mail inválido'),
  phone: z.string().optional()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone ?? ''
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await put(`/users/${props.user.id}`, event.data)
    toast.add({ title: 'Usuário atualizado', description: props.user.name, color: 'success' })
    open.value = false
    emit('updated')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao atualizar.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Editar Usuário" :description="user.email">
    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-4"
        @submit="onSubmit"
      >
        <UFormField label="Nome" name="name" required>
          <UInput v-model="state.name" placeholder="Nome completo" class="w-full" />
        </UFormField>

        <UFormField label="E-mail" name="email" required>
          <UInput
            v-model="state.email"
            type="email"
            placeholder="email@exemplo.com"
            class="w-full"
          />
        </UFormField>

        <UFormField label="Telefone" name="phone">
          <UInput v-model="state.phone" placeholder="(00) 00000-0000" class="w-full" />
        </UFormField>

        <div class="flex justify-end gap-3">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="outline"
            @click="open = false"
          />
          <UButton
            label="Salvar"
            color="primary"
            :loading="loading"
            type="submit"
          />
        </div>
      </UForm>
    </template>
  </UModal>
</template>

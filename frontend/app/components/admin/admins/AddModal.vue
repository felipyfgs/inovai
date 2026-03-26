<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const emit = defineEmits<{ created: [] }>()

const open = ref(false)
const loading = ref(false)
const { post } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  email: z.string().email('E-mail inválido'),
  phone: z.string().optional(),
  password: z.string().min(6, 'Mínimo 6 caracteres'),
  password_confirmation: z.string()
}).refine(data => data.password === data.password_confirmation, {
  message: 'Senhas não conferem',
  path: ['password_confirmation']
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await post('/admin/admins', event.data)
    useAppToast().success('Administrador criado com sucesso')
    open.value = false
    Object.assign(state, { name: '', email: '', phone: '', password: '', password_confirmation: '' })
    emit('created')
  } catch (error) {
    useAppToast().error(extractMessage(error))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open">
    <UButton label="Novo Admin" icon="i-lucide-plus" size="sm" />

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-4"
        @submit="onSubmit"
      >
        <UFormField label="Nome" name="name" required>
          <UInput v-model="state.name" class="w-full" autocomplete="off" />
        </UFormField>

        <UFormField label="E-mail" name="email" required>
          <UInput
            v-model="state.email"
            type="email"
            class="w-full"
            autocomplete="off"
          />
        </UFormField>

        <UFormField label="Telefone" name="phone">
          <UInput
            v-model="state.phone"
            type="tel"
            class="w-full"
            autocomplete="off"
          />
        </UFormField>

        <USeparator />

        <UFormField label="Senha" name="password" required>
          <UInput
            v-model="state.password"
            type="password"
            class="w-full"
            autocomplete="new-password"
          />
        </UFormField>

        <UFormField label="Confirmar Senha" name="password_confirmation" required>
          <UInput
            v-model="state.password_confirmation"
            type="password"
            class="w-full"
            autocomplete="new-password"
          />
        </UFormField>

        <div class="flex justify-end gap-2 pt-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="outline"
            @click="open = false"
          />
          <UButton type="submit" label="Criar Administrador" :loading="loading" />
        </div>
      </UForm>
    </template>
  </UModal>
</template>

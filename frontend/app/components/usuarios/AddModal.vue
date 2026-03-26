<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const props = defineProps<{ officeId?: number }>()
const emit = defineEmits<{ created: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const formRef = useTemplateRef('formRef')

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  email: z.string().email('E-mail inválido'),
  password: z.string().min(6, 'Mínimo 6 caracteres'),
  password_confirmation: z.string(),
  phone: z.string().optional()
}).refine(data => data.password === data.password_confirmation, {
  message: 'Senhas não conferem',
  path: ['password_confirmation']
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: ''
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  if (!props.officeId) return
  loading.value = true
  try {
    await post('/users', {
      ...event.data,
      role: 'accountant',
      office_id: props.officeId,
      is_active: true
    })
    toast.add({ title: 'Usuário criado com sucesso', color: 'success' })
    open.value = false
    emit('created')
    Object.assign(state, { name: '', email: '', password: '', password_confirmation: '', phone: '' })
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao criar usuário.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Novo Usuário"
    description="Adicione um contador ao escritório."
    :ui="{ content: 'w-full sm:max-w-lg', body: 'max-h-[75vh] overflow-y-auto', footer: 'justify-end shrink-0' }"
  >
    <UButton
      label="Novo Usuário"
      icon="i-lucide-plus"
      color="neutral"
    />

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-6 p-1"
        @submit="onSubmit"
      >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField
            name="name"
            label="Nome"
            required
            class="sm:col-span-2"
          >
            <UInput
              v-model="state.name"
              placeholder="Nome completo"
              icon="i-lucide-user"
              class="w-full"
            />
          </UFormField>

          <UFormField
            name="email"
            label="E-mail"
            required
            class="sm:col-span-2"
          >
            <UInput
              v-model="state.email"
              type="email"
              placeholder="email@exemplo.com"
              icon="i-lucide-mail"
              class="w-full"
            />
          </UFormField>

          <UFormField name="password" label="Senha" required>
            <UInput
              v-model="state.password"
              type="password"
              placeholder="Mínimo 6 caracteres"
              icon="i-lucide-lock"
              class="w-full"
            />
          </UFormField>

          <UFormField name="password_confirmation" label="Confirmar Senha" required>
            <UInput
              v-model="state.password_confirmation"
              type="password"
              placeholder="Repita a senha"
              icon="i-lucide-lock"
              class="w-full"
            />
          </UFormField>

          <UFormField name="phone" label="Telefone" class="sm:col-span-2">
            <UInput
              v-model="state.phone"
              placeholder="(00) 00000-0000"
              icon="i-lucide-phone"
              class="w-full"
            />
          </UFormField>
        </div>
      </UForm>
    </template>

    <template #footer="{ close }">
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="close"
      />
      <UButton
        label="Criar Usuário"
        color="primary"
        :loading="loading"
        @click="formRef?.submit()"
      />
    </template>
  </UModal>
</template>

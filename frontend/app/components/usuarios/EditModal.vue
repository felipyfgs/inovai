<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { AppUser } from '~/types'

const props = defineProps<{ user: AppUser | null }>()
const emit = defineEmits<{ updated: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { put } = useApiMutation()
const formRef = useTemplateRef('formRef')

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  email: z.string().email('E-mail inválido'),
  phone: z.string().optional(),
  is_active: z.boolean()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({})

watch(() => props.user, (u) => {
  if (u) {
    Object.assign(state, {
      name: u.name,
      email: u.email,
      phone: u.phone ?? '',
      is_active: u.is_active
    })
    open.value = true
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  if (!props.user) return

  loading.value = true
  try {
    await put(`/users/${props.user.id}`, event.data)
    toast.add({ title: 'Usuário atualizado', color: 'success' })
    open.value = false
    emit('updated')
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao atualizar.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Editar Usuário"
    description="Atualize os dados do usuário."
    :ui="{ content: 'w-full sm:max-w-lg', body: 'max-h-[75vh] overflow-y-auto', footer: 'justify-end shrink-0' }"
  >
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

          <UFormField name="phone" label="Telefone" class="sm:col-span-2">
            <UInput
              v-model="state.phone"
              placeholder="(00) 00000-0000"
              icon="i-lucide-phone"
              class="w-full"
            />
          </UFormField>
        </div>

        <UFormField name="is_active">
          <UCheckbox v-model="state.is_active" label="Usuário ativo" />
        </UFormField>
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
        label="Salvar"
        color="primary"
        :loading="loading"
        @click="formRef?.submit()"
      />
    </template>
  </UModal>
</template>

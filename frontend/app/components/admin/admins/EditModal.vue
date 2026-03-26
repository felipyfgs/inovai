<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { AdminUser } from '~/types'

const props = defineProps<{ admin: AdminUser }>()
const emit = defineEmits<{ updated: [] }>()

const open = ref(false)
const loading = ref(false)
const { put } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  email: z.string().email('E-mail inválido'),
  phone: z.string().optional(),
  is_active: z.boolean()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({})

watch(() => props.admin, (admin) => {
  if (admin) {
    Object.assign(state, {
      name: admin.name,
      email: admin.email,
      phone: admin.phone ?? '',
      is_active: admin.is_active
    })
    open.value = true
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await put(`/admin/admins/${props.admin.id}`, event.data)
    useAppToast().success('Administrador atualizado com sucesso')
    open.value = false
    emit('updated')
  } catch (error) {
    useAppToast().error(extractMessage(error))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Editar Administrador" description="Atualize os dados do administrador.">
    <slot />

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

        <UFormField label="Status" name="is_active">
          <USwitch v-model="state.is_active" label="Ativo" />
        </UFormField>

        <div class="flex justify-end gap-2 pt-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="outline"
            @click="open = false"
          />
          <UButton type="submit" label="Salvar" :loading="loading" />
        </div>
      </UForm>
    </template>
  </UModal>
</template>

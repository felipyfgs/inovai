<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const emit = defineEmits<{ created: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  cnpj: z.string().min(11, 'CPF ou CNPJ inválido'),
  email: z.string().email('E-mail inválido').optional().or(z.literal('')),
  phone: z.string().optional(),
  type: z.enum(['contador', 'direct']),
  notes: z.string().optional()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  cnpj: '',
  email: '',
  phone: '',
  type: 'contador',
  notes: ''
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await post('/admin/offices', event.data)
    toast.add({ title: 'Cadastrado com sucesso', color: 'success' })
    open.value = false
    emit('created')
    Object.assign(state, { name: '', cnpj: '', email: '', phone: '', type: 'contador', notes: '' })
  } catch (e: unknown) {
    const err = e as { response?: { _data?: { message?: string } } }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || 'Erro ao cadastrar.', color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Novo Contador / Cliente" description="Preencha os dados do escritório contábil ou cliente direto.">
    <UButton label="Novo Cadastro" icon="i-lucide-plus" />

    <template #body>
      <UForm
        :schema="schema"
        :state="state"
        class="space-y-4"
        @submit="onSubmit"
      >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField
            label="Nome / Razão Social"
            name="name"
            required
            class="sm:col-span-2"
          >
            <UInput v-model="state.name" placeholder="Nome do escritório ou empresa" class="w-full" />
          </UFormField>

          <UFormField label="CPF/CNPJ" name="cnpj" required>
            <UInput v-model="state.cnpj" placeholder="000.000.000-00 ou 00.000.000/0001-00" class="w-full" />
          </UFormField>

          <UFormField label="Tipo" name="type" required>
            <USelect
              v-model="state.type"
              :items="[
                { label: 'Contador (escritório contábil)', value: 'contador' },
                { label: 'Cliente Direto', value: 'direct' }
              ]"
              class="w-full"
            />
          </UFormField>

          <UFormField label="E-mail" name="email">
            <UInput
              v-model="state.email"
              type="email"
              placeholder="contato@empresa.com.br"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Telefone" name="phone">
            <UInput v-model="state.phone" placeholder="(00) 00000-0000" class="w-full" />
          </UFormField>

          <UFormField label="Observações" name="notes" class="sm:col-span-2">
            <UTextarea v-model="state.notes" placeholder="Observações internas..." class="w-full" />
          </UFormField>
        </div>

        <div class="flex justify-end gap-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="subtle"
            @click="open = false"
          />
          <UButton
            label="Cadastrar"
            color="primary"
            variant="solid"
            type="submit"
            :loading="loading"
          />
        </div>
      </UForm>
    </template>
  </UModal>
</template>

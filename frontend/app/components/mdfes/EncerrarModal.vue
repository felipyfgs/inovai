<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Mdfe } from '~/types'

const props = defineProps<{ mdfe: Mdfe }>()
const emit = defineEmits<{ encerrado: [] }>()

const ufOptions = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
  'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
  'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
].map(uf => ({ label: uf, value: uf }))

const schema = z.object({
  uf: z.string().min(1, 'Selecione a UF'),
  municipio: z.string().min(1, 'Obrigatório'),
  municipio_ibge: z.string().min(1, 'Obrigatório')
})

type Schema = z.output<typeof schema>

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const formRef = useTemplateRef('formRef')
const { encerrarMdfe, extractMessage } = useMdfe()

const state = reactive<Partial<Schema>>({
  uf: '',
  municipio: '',
  municipio_ibge: ''
})

watch(open, (val) => {
  if (val) {
    state.uf = ''
    state.municipio = ''
    state.municipio_ibge = ''
  }
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await encerrarMdfe(props.mdfe.id, {
      uf: event.data.uf,
      municipio: event.data.municipio,
      municipio_ibge: event.data.municipio_ibge
    })
    toast.add({ title: 'MDF-e encerrado', description: `MDF-e nº ${props.mdfe.numero} encerrado com sucesso`, color: 'success' })
    open.value = false
    emit('encerrado')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao encerrar MDF-e.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function handleSubmit() {
  formRef.value?.submit()
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Encerrar MDF-e"
    :description="`Encerrar MDF-e nº ${mdfe.numero}`"
    :ui="{ footer: 'justify-end' }"
  >
    <slot />

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-4"
        @submit="onSubmit"
      >
        <UFormField label="UF" name="uf" required>
          <USelect
            v-model="state.uf"
            :items="ufOptions"
            class="w-full"
            placeholder="Selecione a UF"
          />
        </UFormField>
        <UFormField label="Município" name="municipio" required>
          <UInput
            v-model="state.municipio"
            class="w-full"
            placeholder="Nome do município"
          />
        </UFormField>
        <UFormField label="Código IBGE do Município" name="municipio_ibge" required>
          <UInput
            v-model="state.municipio_ibge"
            class="w-full"
            placeholder="Código IBGE"
          />
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
        label="Confirmar Encerramento"
        color="primary"
        :loading="loading"
        @click="handleSubmit"
      />
    </template>
  </UModal>
</template>

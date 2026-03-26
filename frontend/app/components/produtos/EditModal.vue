<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Produto } from '~/types'

const props = defineProps<{ produto: Produto }>()
const emit = defineEmits<{ updated: [] }>()

const schema = z.object({
  codigo: z.string().optional(),
  codigo_barras: z.string().optional(),
  descricao: z.string().min(2, 'Mínimo 2 caracteres'),
  ncm: z.string().optional(),
  cest: z.string().optional(),
  cfop: z.string().optional(),
  unidade: z.string().default('UN'),
  preco_venda: z.coerce.number().min(0).default(0),
  preco_custo: z.coerce.number().min(0).default(0),
  origem: z.coerce.number().min(0).max(8).default(0),
  observacoes: z.string().optional()
})

type Schema = z.output<typeof schema>

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { put } = useApiMutation()
const { extractMessage } = useApiError()
const formRef = useTemplateRef('formRef')

const state = reactive<Partial<Schema>>({})

watch(open, (val) => {
  if (val) {
    Object.assign(state, {
      codigo: props.produto.codigo || '',
      codigo_barras: props.produto.codigo_barras || '',
      descricao: props.produto.descricao,
      ncm: props.produto.ncm || '',
      cest: props.produto.cest || '',
      cfop: props.produto.cfop || '',
      unidade: props.produto.unidade,
      preco_venda: Number(props.produto.preco_venda),
      preco_custo: Number(props.produto.preco_custo),
      origem: props.produto.origem,
      observacoes: ''
    })
  }
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await put(`/produtos/${props.produto.id}`, event.data)
    toast.add({ title: 'Produto atualizado', description: event.data.descricao, color: 'success' })
    open.value = false
    emit('updated')
  } catch (error) {
    toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao atualizar.', color: 'error' })
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
    title="Editar Produto"
    description="Alterar dados do produto"
    :ui="{ content: 'w-full sm:max-w-4xl', footer: 'justify-end' }"
  >
    <slot />

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-6"
        @submit="onSubmit"
      >
        <!-- Seção: Informações Básicas -->
        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-package text-muted" />
            Informações Básicas
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <UFormField
              label="Descrição"
              name="descricao"
              required
              class="sm:col-span-2 lg:col-span-4"
            >
              <UInput v-model="state.descricao" class="w-full" />
            </UFormField>
            <UFormField label="Código Interno" name="codigo">
              <UInput v-model="state.codigo" class="w-full" />
            </UFormField>
            <UFormField label="Código de Barras" name="codigo_barras">
              <UInput v-model="state.codigo_barras" class="w-full" />
            </UFormField>
            <UFormField label="Unidade" name="unidade">
              <UInput v-model="state.unidade" class="w-full" />
            </UFormField>
            <UFormField label="Origem" name="origem">
              <USelect
                v-model="state.origem"
                :items="[
                  { label: '0 - Nacional', value: 0 },
                  { label: '1 - Estrangeira (importação)', value: 1 },
                  { label: '2 - Estrangeira (mercado interno)', value: 2 }
                ]"
                class="w-full"
              />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <!-- Seção: Classificação Fiscal -->
        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-barcode text-muted" />
            Classificação Fiscal
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <UFormField label="NCM" name="ncm">
              <UInput v-model="state.ncm" class="w-full" maxlength="8" />
            </UFormField>
            <UFormField label="CEST" name="cest">
              <UInput v-model="state.cest" class="w-full" maxlength="7" />
            </UFormField>
            <UFormField label="CFOP" name="cfop">
              <UInput v-model="state.cfop" class="w-full" maxlength="4" />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <!-- Seção: Preços -->
        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-dollar-sign text-muted" />
            Preços
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Preço de Venda (R$)" name="preco_venda">
              <UInput
                v-model="state.preco_venda"
                type="number"
                step="0.01"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Preço de Custo (R$)" name="preco_custo">
              <UInput
                v-model="state.preco_custo"
                type="number"
                step="0.01"
                class="w-full"
              />
            </UFormField>
          </div>
        </div>

        <!-- Seção: Observações -->
        <UFormField label="Observações" name="observacoes">
          <UTextarea v-model="state.observacoes" class="w-full" :rows="3" />
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
        label="Salvar Alterações"
        color="primary"
        :loading="loading"
        @click="handleSubmit"
      />
    </template>
  </UModal>
</template>

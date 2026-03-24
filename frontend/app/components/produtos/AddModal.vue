<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const emit = defineEmits<{ created: [] }>()

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
  cst_icms: z.string().optional(),
  csosn: z.string().optional(),
  aliq_icms: z.coerce.number().min(0).optional(),
  aliq_ipi: z.coerce.number().min(0).optional(),
  cst_pis: z.string().optional(),
  aliq_pis: z.coerce.number().min(0).optional(),
  cst_cofins: z.string().optional(),
  aliq_cofins: z.coerce.number().min(0).optional(),
  observacoes: z.string().optional()
})

type Schema = z.output<typeof schema>

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { post } = useApiMutation()
const formRef = useTemplateRef('formRef')

const state = reactive<Partial<Schema>>({
  codigo: '', codigo_barras: '', descricao: '', ncm: '', cest: '', cfop: '',
  unidade: 'UN', preco_venda: 0, preco_custo: 0, origem: 0,
  cst_icms: '', csosn: '', aliq_icms: 0, aliq_ipi: 0,
  cst_pis: '', aliq_pis: 0, cst_cofins: '', aliq_cofins: 0, observacoes: ''
})

function resetForm() {
  Object.assign(state, {
    codigo: '', codigo_barras: '', descricao: '', ncm: '', cest: '', cfop: '',
    unidade: 'UN', preco_venda: 0, preco_custo: 0, origem: 0,
    cst_icms: '', csosn: '', aliq_icms: 0, aliq_ipi: 0,
    cst_pis: '', aliq_pis: 0, cst_cofins: '', aliq_cofins: 0, observacoes: ''
  })
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await post('/produtos', event.data)
    toast.add({ title: 'Produto criado', description: event.data.descricao, color: 'success' })
    open.value = false
    resetForm()
    emit('created')
  } catch (error) {
    toast.add({ title: 'Erro', description: error?.response?._data?.message || 'Erro ao criar.', color: 'error' })
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
    title="Novo Produto"
    description="Adicionar produto ao catálogo"
    :ui="{ content: 'w-full sm:max-w-4xl', footer: 'justify-end' }"
  >
    <UButton label="Novo Produto" icon="i-lucide-plus" />

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
              <UInput v-model="state.descricao" class="w-full" placeholder="Descrição do produto" />
            </UFormField>
            <UFormField label="Código Interno" name="codigo">
              <UInput v-model="state.codigo" class="w-full" placeholder="Código de controle" />
            </UFormField>
            <UFormField label="Código de Barras" name="codigo_barras">
              <UInput v-model="state.codigo_barras" class="w-full" placeholder="EAN/GTIN" />
            </UFormField>
            <UFormField label="Unidade" name="unidade">
              <UInput v-model="state.unidade" class="w-full" placeholder="UN" />
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
                placeholder="Selecione"
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
            <UFormField label="NCM" name="ncm" description="Nomenclatura Comum do Mercosul">
              <UInput
                v-model="state.ncm"
                class="w-full"
                placeholder="00000000"
                maxlength="8"
              />
            </UFormField>
            <UFormField label="CEST" name="cest" description="Código Especificador da Substituição Tributária">
              <UInput
                v-model="state.cest"
                class="w-full"
                placeholder="0000000"
                maxlength="7"
              />
            </UFormField>
            <UFormField label="CFOP" name="cfop" description="Código de Operação Fiscal">
              <UInput
                v-model="state.cfop"
                class="w-full"
                placeholder="5102"
                maxlength="4"
              />
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
                placeholder="0,00"
              />
            </UFormField>
            <UFormField label="Preço de Custo (R$)" name="preco_custo">
              <UInput
                v-model="state.preco_custo"
                type="number"
                step="0.01"
                class="w-full"
                placeholder="0,00"
              />
            </UFormField>
          </div>
        </div>

        <USeparator />

        <!-- Seção: Tributação -->
        <div>
          <h3 class="text-sm font-semibold text-highlighted mb-3 flex items-center gap-2">
            <span class="i-lucide-receipt text-muted" />
            Tributação
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <UFormField label="CST ICMS" name="cst_icms">
              <UInput v-model="state.cst_icms" class="w-full" placeholder="CST" />
            </UFormField>
            <UFormField label="CSOSN" name="csosn">
              <UInput v-model="state.csosn" class="w-full" placeholder="CSOSN" />
            </UFormField>
            <UFormField label="Alíquota ICMS (%)" name="aliq_icms">
              <UInput
                v-model="state.aliq_icms"
                type="number"
                step="0.01"
                class="w-full"
                placeholder="0,00"
              />
            </UFormField>
            <UFormField label="Alíquota IPI (%)" name="aliq_ipi">
              <UInput
                v-model="state.aliq_ipi"
                type="number"
                step="0.01"
                class="w-full"
                placeholder="0,00"
              />
            </UFormField>
            <UFormField label="CST PIS" name="cst_pis">
              <UInput v-model="state.cst_pis" class="w-full" placeholder="CST" />
            </UFormField>
            <UFormField label="Alíquota PIS (%)" name="aliq_pis">
              <UInput
                v-model="state.aliq_pis"
                type="number"
                step="0.01"
                class="w-full"
                placeholder="0,00"
              />
            </UFormField>
            <UFormField label="CST COFINS" name="cst_cofins">
              <UInput v-model="state.cst_cofins" class="w-full" placeholder="CST" />
            </UFormField>
            <UFormField label="Alíquota COFINS (%)" name="aliq_cofins">
              <UInput
                v-model="state.aliq_cofins"
                type="number"
                step="0.01"
                class="w-full"
                placeholder="0,00"
              />
            </UFormField>
          </div>
        </div>

        <!-- Seção: Observações -->
        <UFormField label="Observações" name="observacoes">
          <UTextarea
            v-model="state.observacoes"
            class="w-full"
            placeholder="Informações adicionais..."
            :rows="3"
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
        label="Criar Produto"
        color="primary"
        :loading="loading"
        @click="handleSubmit"
      />
    </template>
  </UModal>
</template>

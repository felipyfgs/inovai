<script setup lang="ts">
import type { OfficePlan } from '~/types'
import type { FormSubmitEvent } from '@nuxt/ui'
import * as z from 'zod'

const emit = defineEmits<{ created: [], updated: [] }>()

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  description: z.string().optional(),
  price: z.coerce.number().min(0, 'Valor inválido'),
  max_nfs_month: z.coerce.number().positive().nullable().optional(),
  modules: z.array(z.string()).min(1, 'Selecione ao menos 1 módulo'),
  is_default: z.boolean().default(false)
})

type Schema = z.output<typeof schema>

const open = ref(false)
const loading = ref(false)
const editingPlan = ref<OfficePlan | null>(null)
const toast = useToast()
const { post, put } = useApiMutation()
const formRef = useTemplateRef('formRef')

const moduleOptions = [
  { label: 'NF-e', value: 'nfe' },
  { label: 'NFC-e', value: 'nfce' },
  { label: 'CT-e', value: 'cte' },
  { label: 'MDF-e', value: 'mdfe' },
  { label: 'NFS-e', value: 'nfse' },
  { label: 'Orçamentos', value: 'orcamento' },
  { label: 'Estoque', value: 'estoque' },
  { label: 'Financeiro', value: 'financeiro' },
  { label: 'Restaurante', value: 'restaurante' },
  { label: 'Relatórios', value: 'relatorios' }
]

const state = reactive<Partial<Schema>>({
  name: '',
  description: '',
  price: 0,
  max_nfs_month: null,
  modules: ['nfe', 'nfce'],
  is_default: false
})

function resetForm() {
  Object.assign(state, {
    name: '',
    description: '',
    price: 0,
    max_nfs_month: null,
    modules: ['nfe', 'nfce'],
    is_default: false
  })
}

function openFor(plan: OfficePlan) {
  editingPlan.value = plan
  Object.assign(state, {
    name: plan.name,
    description: plan.description || '',
    price: plan.price,
    max_nfs_month: plan.max_nfs_month ?? null,
    modules: plan.modules,
    is_default: plan.is_default
  })
  open.value = true
}

function close() {
  open.value = false
  editingPlan.value = null
  resetForm()
}

defineExpose({ openFor, close })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    if (editingPlan.value) {
      await put(`/office-plans/${editingPlan.value.id}`, event.data)
      toast.add({ title: 'Plano atualizado', description: event.data.name, color: 'success' })
    } else {
      await post('/office-plans', event.data)
      toast.add({ title: 'Plano criado', description: event.data.name, color: 'success' })
    }
    close()
    if (editingPlan.value) {
      emit('updated')
    } else {
      emit('created')
    }
  } catch (error: unknown) {
    const err = error as { response?: { _data?: { message?: string } }, message?: string }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || err?.message || 'Erro ao salvar plano.', color: 'error' })
  } finally {
    loading.value = false
  }
}

function toggleModule(modValue: string) {
  const current = state.modules || []
  if (current.includes(modValue)) {
    state.modules = current.filter(m => m !== modValue)
  } else {
    state.modules = [...current, modValue]
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    :title="editingPlan ? 'Editar Plano' : 'Novo Plano'"
    :ui="{ content: 'sm:max-w-lg', body: 'max-h-[75vh] overflow-y-auto', footer: 'justify-end shrink-0' }"
  >
    <slot :open="() => { resetForm(); open = true }">
      <UButton
        label="Novo Plano"
        icon="i-lucide-plus"
        size="xs"
      />
    </slot>

    <template #body>
      <UForm
        ref="formRef"
        :schema="schema"
        :state="state"
        class="space-y-4 p-1"
        @submit="onSubmit"
      >
        <UFormField label="Nome do Plano" name="name" required>
          <UInput v-model="state.name" placeholder="Ex: Plano Básico" class="w-full" />
        </UFormField>

        <UFormField label="Descrição" name="description">
          <UTextarea
            v-model="state.description"
            placeholder="Descrição do plano para os clientes"
            class="w-full"
            :rows="2"
          />
        </UFormField>

        <div class="grid grid-cols-2 gap-4">
          <UFormField label="Valor Mensal (R$)" name="price" required>
            <UInput
              v-model="state.price"
              type="number"
              step="0.01"
              placeholder="0.00"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Limite NFs/mês" name="max_nfs_month">
            <UInput
              :model-value="state.max_nfs_month ?? undefined"
              type="number"
              placeholder="Vazio = ilimitado"
              class="w-full"
              @update:model-value="(v: string | number) => { state.max_nfs_month = v ? Number(v) : null }"
            />
          </UFormField>
        </div>

        <UFormField label="Módulos Inclusos" name="modules" required>
          <div class="grid grid-cols-2 gap-2">
            <label
              v-for="mod in moduleOptions"
              :key="mod.value"
              class="flex items-center gap-2 p-2 rounded-md border border-default cursor-pointer hover:bg-elevated/50 transition-colors"
              :class="{ 'border-primary bg-primary/5 ring ring-primary/20': state.modules?.includes(mod.value) }"
              @click="toggleModule(mod.value)"
            >
              <UCheckbox
                :model-value="state.modules?.includes(mod.value)"
                @update:model-value="() => {}"
              />
              <span class="text-sm">{{ mod.label }}</span>
            </label>
          </div>
        </UFormField>

        <UFormField label="Plano Padrão" name="is_default">
          <UCheckbox
            v-model="state.is_default"
            description="Este será o plano selecionado por padrão ao criar uma empresa"
          />
        </UFormField>
      </UForm>
    </template>

    <template #footer="{ close: closeModal }">
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="closeModal"
      />
      <UButton
        :label="editingPlan ? 'Salvar' : 'Criar Plano'"
        color="primary"
        :loading="loading"
        @click="formRef?.submit()"
      />
    </template>
  </UModal>
</template>

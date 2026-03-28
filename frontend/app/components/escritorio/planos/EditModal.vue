<script setup lang="ts">
import type { FormSubmitEvent } from '@nuxt/ui'
import type { OfficePlan } from '~/types'
import * as z from 'zod'

const props = defineProps<{ plan: OfficePlan }>()
const emit = defineEmits<{ updated: [] }>()

const schema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  description: z.string().optional(),
  price: z.coerce.number().min(0, 'Valor inválido'),
  max_nfs_month: z.coerce.number().positive().nullable().optional(),
  modules: z.array(z.string()).min(1, 'Selecione ao menos 1 módulo'),
  is_default: z.boolean().default(false)
})

type Schema = z.output<typeof schema>

const open = ref(true)
const loading = ref(false)
const { put } = useApiMutation()
const { extractMessage } = useApiError()

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
  name: props.plan.name,
  description: props.plan.description || '',
  price: props.plan.price,
  max_nfs_month: props.plan.max_nfs_month ?? null,
  modules: props.plan.modules,
  is_default: props.plan.is_default
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    await put(`/office-plans/${props.plan.id}`, event.data)
    useAppToast().success('Plano atualizado com sucesso')
    open.value = false
    emit('updated')
  } catch (error: unknown) {
    useAppToast().error(extractMessage(error))
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
  <UModal v-model:open="open" title="Editar Plano" description="Atualize os dados do plano.">
    <template #body>
      <UForm
        :schema="schema"
        :state="state"
        class="space-y-4"
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

        <div class="flex justify-end gap-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="subtle"
            @click="open = false"
          />
          <UButton
            label="Salvar"
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

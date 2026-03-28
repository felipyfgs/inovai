<script setup lang="ts">
import type { AvailableModule, Company, OfficePlan } from '~/types'

const props = defineProps<{
  company: Company
}>()

const emit = defineEmits<{ updated: [] }>()

const open = ref(false)
const loading = ref(false)
const toast = useToast()
const { put } = useApiMutation()

const { data: modulesData, status, refresh } = useApi<AvailableModule[]>(`/companies/${props.company.id}/modules`, {
  lazy: true
})

const planData = ref<OfficePlan | null>(null)

watchEffect(async () => {
  if (!open.value || !props.company.office_plan_id) {
    planData.value = null
    return
  }
  try {
    const { $sanctumClient } = useNuxtApp()
    planData.value = await $sanctumClient<OfficePlan>(`/api/office-plans/${props.company.office_plan_id}`)
  } catch {
    planData.value = null
  }
})

const selectedModules = computed({
  get: () => (modulesData.value || []).filter(m => m.is_active && m.allowed_by_plan).map(m => m.id),
  set: () => {}
})

function toggleModule(moduleId: string) {
  const current = selectedModules.value
  if (current.includes(moduleId)) {
    selectedModules.value = current.filter(m => m !== moduleId)
  } else {
    selectedModules.value = [...current, moduleId]
  }
}

function isModuleEnabled(moduleId: string): boolean {
  return selectedModules.value.includes(moduleId)
}

function restorePlan() {
  if (planData.value) {
    selectedModules.value = [...planData.value.modules]
  }
}

async function saveModules() {
  loading.value = true
  try {
    await put(`/companies/${props.company.id}/modules`, { modules: selectedModules.value })
    toast.add({ title: 'Módulos atualizados', color: 'success' })
    open.value = false
    emit('updated')
  } catch (error: unknown) {
    const err = error as { response?: { _data?: { message?: string } }, message?: string }
    toast.add({ title: 'Erro', description: err?.response?._data?.message || err?.message || 'Erro ao atualizar módulos.', color: 'error' })
  } finally {
    loading.value = false
  }
}

watch(open, (val) => {
  if (val) refresh()
})
</script>

<template>
  <slot :open="() => open = true" />

  <UModal
    v-model:open="open"
    title="Gerenciar Módulos"
    :description="`Módulos de ${company.fantasia || company.razao_social}`"
    :ui="{ content: 'sm:max-w-lg' }"
  >
    <template #body>
      <div class="space-y-3">
        <p class="text-sm text-muted mb-4">
          Módulos disponíveis para {{ company.fantasia || company.razao_social }}
        </p>

        <div v-if="status === 'pending'" class="flex items-center justify-center py-8">
          <UIcon name="i-lucide-loader-2" class="animate-spin size-6 text-muted" />
        </div>

        <div v-else class="space-y-2">
          <label
            v-for="mod in modulesData"
            :key="mod.id"
            class="flex items-center gap-3 p-3 rounded-lg border border-default hover:bg-elevated/50 transition-colors cursor-pointer"
            :class="{ 'opacity-50 cursor-not-allowed': !mod.allowed_by_plan }"
          >
            <UCheckbox
              :model-value="isModuleEnabled(mod.id)"
              :disabled="!mod.allowed_by_plan"
              @update:model-value="toggleModule(mod.id)"
            />
            <span class="flex-1 text-sm font-medium">{{ mod.label }}</span>
            <UBadge
              v-if="!mod.allowed_by_plan"
              variant="subtle"
              size="xs"
              color="warning"
            >
              Indisponível no plano
            </UBadge>
          </label>
        </div>
      </div>
    </template>

    <template #footer="{ close }">
      <UButton
        v-if="planData"
        label="Restaurar Plano"
        color="neutral"
        variant="outline"
        icon="i-lucide-rotate-ccw"
        @click="restorePlan"
      />
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
        @click="saveModules"
      />
    </template>
  </UModal>
</template>

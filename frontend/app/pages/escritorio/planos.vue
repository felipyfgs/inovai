<script setup lang="ts">
import type { OfficePlan } from '~/types'

definePageMeta({ middleware: 'escritorio' })

const { data: plans, status, refresh } = useApi<OfficePlan[]>('/office-plans', { lazy: true })

const addModal = ref<{ openFor: (plan: OfficePlan) => void } | null>(null)
const deletingPlan = ref<OfficePlan | null>(null)
const deleting = ref(false)
const showDeleteModal = computed({
  get: () => deletingPlan.value !== null,
  set: (val: boolean) => { if (!val) deletingPlan.value = null }
})
const toast = useToast()
const { del, put } = useApiMutation()

const allModules: Record<string, string> = {
  nfe: 'NF-e',
  nfce: 'NFC-e',
  cte: 'CT-e',
  mdfe: 'MDF-e',
  nfse: 'NFS-e',
  orcamento: 'Orçamentos',
  estoque: 'Estoque',
  financeiro: 'Financeiro',
  restaurante: 'Restaurante',
  relatorios: 'Relatórios'
}

const editingPlan = ref<OfficePlan | null>(null)

function openEdit(plan: OfficePlan) {
  editingPlan.value = plan
  nextTick(() => addModal.value?.openFor(plan))
}

async function toggleActive(plan: OfficePlan) {
  const newValue = !plan.is_active
  try {
    await put(`/office-plans/${plan.id}`, { is_active: newValue })
    plan.is_active = newValue
    toast.add({
      title: newValue ? 'Plano ativado' : 'Plano desativado',
      description: plan.name,
      color: 'success'
    })
  } catch {
    toast.add({ title: 'Erro', color: 'error' })
    refresh()
  }
}

async function confirmDelete(plan: OfficePlan) {
  deletingPlan.value = plan
}

async function executeDelete() {
  if (!deletingPlan.value) return
  deleting.value = true
  try {
    await del(`/office-plans/${deletingPlan.value.id}`)
    toast.add({ title: 'Plano removido', description: deletingPlan.value.name, color: 'success' })
    deletingPlan.value = null
    refresh()
  } catch {
    toast.add({ title: 'Erro', color: 'error' })
    deletingPlan.value = null
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <UDashboardPanel>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold">
            Planos de Serviço
          </h2>
          <p class="text-sm text-muted">
            Configure planos para oferecer às suas empresas clientes
          </p>
        </div>
        <EscritorioPlanosAddModal ref="addModal" @created="refresh" @updated="refresh" />
      </div>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center py-12">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-6 text-muted" />
      </div>

      <div v-else-if="plans && plans.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <UCard
          v-for="plan in plans"
          :key="plan.id"
          class="relative"
          :class="{ 'opacity-60': !plan.is_active }"
        >
          <div class="flex items-start justify-between p-4">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-semibold text-base">
                  {{ plan.name }}
                </h3>
                <UBadge
                  v-if="plan.is_default"
                  variant="subtle"
                  size="xs"
                  color="primary"
                >
                  Padrão
                </UBadge>
              </div>
              <p v-if="plan.description" class="text-sm text-muted mt-1">
                {{ plan.description }}
              </p>
            </div>
            <UDropdownMenu
              :items="[
                { label: 'Editar', icon: 'i-lucide-pencil', onSelect: () => openEdit(plan) },
                { type: 'separator' as const },
                { label: plan.is_active ? 'Desativar' : 'Ativar', icon: plan.is_active ? 'i-lucide-eye-off' : 'i-lucide-eye', onSelect: () => toggleActive(plan) },
                { type: 'separator' as const },
                { label: 'Excluir', icon: 'i-lucide-trash-2', onSelect: () => confirmDelete(plan) }
              ]"
            >
              <UButton icon="i-lucide-ellipsis" variant="ghost" size="sm" />
            </UDropdownMenu>
          </div>

          <div class="px-4 pb-4 border-t border-default">
            <div class="pt-3 space-y-2">
              <div class="flex items-baseline justify-between">
                <span class="text-2xl font-bold text-primary">
                  R$ {{ plan.price.toFixed(2).replace('.', ',') }}
                </span>
                <span class="text-xs text-muted">/mês</span>
              </div>
              <p v-if="plan.max_nfs_month" class="text-xs text-muted">
                Até {{ plan.max_nfs_month }} NFs/mês
              </p>
              <p v-else class="text-xs text-muted">
                NFs ilimitados
              </p>
            </div>
          </div>

          <div class="px-4 pb-4 border-t border-default">
            <p class="text-xs font-medium text-muted uppercase tracking-wider mb-2">
              Módulos inclusos
            </p>
            <div class="flex flex-wrap gap-1.5">
              <UBadge
                v-for="mod in plan.modules"
                :key="mod"
                variant="subtle"
                size="xs"
                color="primary"
              >
                {{ allModules[mod] || mod }}
              </UBadge>
            </div>
          </div>
        </UCard>
      </div>

      <div v-else class="flex flex-col items-center justify-center py-12 text-center">
        <UIcon name="i-lucide-package" class="size-12 text-dimmed mb-3" />
        <p class="text-sm text-muted">
          Nenhum plano cadastrado
        </p>
        <p class="text-xs text-dimmed">
          Crie planos para oferecer às suas empresas
        </p>
      </div>
    </template>
  </UDashboardPanel>

  <UModal
    v-model:open="showDeleteModal"
    title="Excluir Plano"
    :ui="{ content: 'sm:max-w-md' }"
  >
    <template #body>
      <p class="text-sm text-muted">
        Tem certeza que deseja excluir o plano
        <span class="font-medium text-default">"{{ deletingPlan?.name }}"</span>?
        Esta ação não pode ser desfeita.
      </p>
    </template>

    <template #footer>
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="deletingPlan = null"
      />
      <UButton
        label="Excluir Plano"
        color="error"
        :loading="deleting"
        @click="executeDelete"
      />
    </template>
  </UModal>
</template>

<script setup lang="ts">
import type { Plan } from '~/types'

const UButton = resolveComponent('UButton')
const UDropdownMenu = resolveComponent('UDropdownMenu')

definePageMeta({ middleware: 'admin' })

const { data, refresh } = useApi<Plan[]>('/admin/plans', { lazy: true })

const plans = computed(() => data.value || [])

const deletingPlan = ref<Plan | null>(null)
const editingPlan = ref<Plan | null>(null)
const editOpen = ref(false)
</script>

<template>
  <UDashboardPanel id="admin-planos">
    <template #header>
      <UDashboardNavbar title="Planos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <AdminPlanosAddModal @created="refresh()" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div v-if="!data" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <div v-else-if="plans.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
        <UIcon name="i-lucide-package" class="size-12 text-muted mb-4" />
        <p class="text-muted">
          Nenhum plano cadastrado.
        </p>
        <p class="text-sm text-muted">
          Crie um plano para começar.
        </p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <UCard v-for="plan in plans" :key="plan.id">
          <template #header>
            <div class="flex items-center justify-between">
              <div>
                <p class="font-medium">
                  {{ plan.name }}
                </p>
                <p class="text-sm text-muted">
                  {{ plan.description || '—' }}
                </p>
              </div>
              <UBadge :color="plan.is_active ? 'success' : 'error'" variant="subtle">
                {{ plan.is_active ? 'Ativo' : 'Inativo' }}
              </UBadge>
            </div>
          </template>

          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-muted">Preço</span>
              <span class="font-medium">R$ {{ Number(plan.price).toFixed(2).replace('.', ',') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted">Empresas</span>
              <span class="font-medium">{{ plan.max_companies === 999 ? 'Ilimitado' : plan.max_companies }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted">NFes/Mês</span>
              <span class="font-medium">{{ plan.max_nfs_month === 0 ? 'Ilimitado' : plan.max_nfs_month }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted">Carência</span>
              <span class="font-medium">{{ plan.grace_period_days }} dias</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted">Máx. Atraso</span>
              <span class="font-medium">{{ plan.max_overdue_days }} dias</span>
            </div>
          </div>

          <template #footer>
            <UDropdownMenu
              :items="[
                { type: 'label' as const, label: 'Ações' },
                {
                  label: 'Editar',
                  icon: 'i-lucide-pencil',
                  onSelect: () => { editingPlan = plan; editOpen = true }
                },
                {
                  label: 'Excluir',
                  icon: 'i-lucide-trash',
                  color: 'error',
                  onSelect: () => { deletingPlan = plan }
                }
              ]"
              :content="{ align: 'end' }"
            >
              <UButton
                label="Ações"
                icon="i-lucide-ellipsis-vertical"
                color="neutral"
                variant="outline"
                block
                class="flex-1"
              />
            </UDropdownMenu>
          </template>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>

  <AdminPlanosEditModal
    v-if="editingPlan"
    v-model:open="editOpen"
    :plan="editingPlan"
    @updated="() => { editingPlan = null; refresh() }"
  />

  <AdminPlanosDeleteModal
    v-if="deletingPlan"
    :plan="deletingPlan"
    @deleted="() => { deletingPlan = null; refresh() }"
  />
</template>

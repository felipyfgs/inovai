<script setup lang="ts">
import type { Plan } from '~/types'

definePageMeta({ middleware: 'admin' })

const { data, refresh } = useApi<Plan[]>('/admin/plans', { lazy: true })

const plans = computed(() => data.value || [])

const deletingPlan = ref<Plan | null>(null)
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
          </div>

          <template #footer>
            <div class="flex gap-2">
              <AdminPlanosEditModal :plan="plan" @updated="refresh()">
                <UButton
                  variant="outline"
                  size="sm"
                  icon="i-lucide-pencil"
                  class="flex-1"
                />
              </AdminPlanosEditModal>
              <UButton
                variant="outline"
                color="error"
                size="sm"
                icon="i-lucide-trash"
                @click="deletingPlan = plan"
              />
            </div>
          </template>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>

  <AdminPlanosDeleteModal
    v-if="deletingPlan"
    :plan="deletingPlan"
    @deleted="() => { deletingPlan = null; refresh() }"
  />
</template>

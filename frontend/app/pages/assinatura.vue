<script setup lang="ts">
import type { Office } from '~/types'

definePageMeta({ middleware: 'escritorio' })

const { data: office, status } = useApi<Office>('/office/profile', { lazy: true })

const subscription = computed(() => office.value?.subscription)
const plan = computed(() => subscription.value?.plan)

const statusColor = computed(() => {
  const s = subscription.value?.status
  const map: Record<string, 'success' | 'warning' | 'error' | 'neutral'> = {
    active: 'success',
    trial: 'warning',
    cancelled: 'error',
    expired: 'error'
  }
  return map[s || ''] || 'neutral'
})

const statusLabel = computed(() => {
  const s = subscription.value?.status
  const map: Record<string, string> = {
    active: 'Ativo',
    trial: 'Trial',
    cancelled: 'Cancelado',
    expired: 'Expirado'
  }
  return map[s || ''] || 'Sem assinatura'
})

const formatDate = (date: string | null) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}
</script>

<template>
  <UDashboardPanel id="assinatura">
    <template #header>
      <UDashboardNavbar title="Assinatura">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <div v-else-if="office" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 space-y-6">
            <UPageCard title="Plano Atual" variant="subtle">
              <div v-if="plan" class="space-y-4">
                <div class="flex items-start justify-between">
                  <div>
                    <h3 class="text-xl font-semibold">
                      {{ plan.name }}
                    </h3>
                    <p v-if="plan.description" class="text-sm text-muted mt-1">
                      {{ plan.description }}
                    </p>
                  </div>
                  <UBadge :color="statusColor" variant="subtle" size="lg">
                    {{ statusLabel }}
                  </UBadge>
                </div>

                <div class="flex items-baseline gap-1">
                  <span class="text-3xl font-bold text-primary">
                    {{ formatCurrency(plan.price) }}
                  </span>
                  <span class="text-sm text-muted">/mês</span>
                </div>

                <USeparator />

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                  <div>
                    <span class="text-xs text-muted uppercase tracking-wider">Empresas</span>
                    <p class="text-lg font-semibold">
                      {{ office.companies_count ?? 0 }} / {{ plan.max_companies }}
                    </p>
                  </div>
                  <div>
                    <span class="text-xs text-muted uppercase tracking-wider">NFs/mês</span>
                    <p class="text-lg font-semibold">
                      {{ plan.max_nfs_month }}
                    </p>
                  </div>
                  <div>
                    <span class="text-xs text-muted uppercase tracking-wider">Período de carência</span>
                    <p class="text-lg font-semibold">
                      {{ plan.grace_period_days }} dias
                    </p>
                  </div>
                </div>

                <USeparator />

                <div>
                  <span class="text-xs text-muted uppercase tracking-wider">Recursos do Plano</span>
                  <div class="flex flex-wrap gap-1.5 mt-2">
                    <UBadge
                      v-for="feature in plan.features"
                      :key="feature"
                      variant="subtle"
                      size="sm"
                      color="primary"
                    >
                      {{ feature }}
                    </UBadge>
                    <p v-if="!plan.features?.length" class="text-sm text-muted">
                      Nenhum recurso listado
                    </p>
                  </div>
                </div>
              </div>

              <div v-else class="text-center py-8">
                <UIcon name="i-lucide-package" class="size-12 text-dimmed mb-3" />
                <p class="text-muted">
                  Nenhum plano associado ao escritório.
                </p>
                <p class="text-sm text-dimmed">
                  Entre em contato com o suporte.
                </p>
              </div>
            </UPageCard>
          </div>

          <div class="space-y-6">
            <UPageCard title="Detalhes da Assinatura" variant="subtle">
              <div v-if="subscription" class="space-y-3 text-sm">
                <div class="flex justify-between">
                  <span class="text-muted">Status</span>
                  <UBadge :color="statusColor" variant="subtle">
                    {{ statusLabel }}
                  </UBadge>
                </div>
                <USeparator />
                <div class="flex justify-between">
                  <span class="text-muted">Início</span>
                  <span class="font-medium">{{ formatDate(subscription.starts_at) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted">Término</span>
                  <span class="font-medium">{{ formatDate(subscription.ends_at) }}</span>
                </div>
              </div>

              <div v-else class="text-sm text-muted text-center py-4">
                Sem assinatura ativa
              </div>
            </UPageCard>

            <UPageCard title="Escritório" variant="subtle">
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-muted">Nome</span>
                  <span class="font-medium">{{ office.name }}</span>
                </div>
                <div v-if="office.cnpj" class="flex justify-between">
                  <span class="text-muted">CNPJ</span>
                  <span class="font-medium">{{ office.cnpj }}</span>
                </div>
                <div v-if="office.type" class="flex justify-between">
                  <span class="text-muted">Tipo</span>
                  <span class="font-medium capitalize">{{ office.type }}</span>
                </div>
              </div>
            </UPageCard>
          </div>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>

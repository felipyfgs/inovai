<script setup lang="ts">
import type { Office } from '~/types'

definePageMeta({ middleware: 'escritorio' })

const { data: office, status, refresh } = useApi<Office>('/office/profile', {
  lazy: true
})

const { data: dashboard } = useApi<{
  total_empresas: number
  total_nfe: number
  nfe_autorizadas: number
  total_cte: number
  total_mdfe: number
  certificados_vencendo: number
  certificados_vencidos: number
}>('/dashboard/office', {
  lazy: true
})

const subscriptionStatus = computed(() => {
  const s = office.value?.subscription?.status
  const colors: Record<string, 'success' | 'warning' | 'error'> = {
    active: 'success',
    trial: 'warning',
    cancelled: 'error',
    expired: 'error'
  }
  const labels: Record<string, string> = {
    active: 'Ativo',
    trial: 'Trial',
    cancelled: 'Cancelado',
    expired: 'Expirado'
  }
  return {
    color: (colors[s || ''] || 'neutral') as 'success' | 'warning' | 'error' | 'neutral',
    label: labels[s || ''] || 'Sem plano'
  }
})

const planName = computed(() => office.value?.subscription?.plan?.name ?? 'Sem plano')
const planPrice = computed(() => {
  const price = office.value?.subscription?.plan?.price
  return price != null ? formatCurrency(price) : '-'
})

const activeUsers = computed(() => (office.value?.users || []).filter(u => u.is_active !== false))

const statsCards = computed(() => [
  {
    label: 'Empresas',
    value: dashboard.value?.total_empresas ?? 0,
    icon: 'i-lucide-building-2',
    to: '/empresas'
  },
  {
    label: 'NF-e Emitidas',
    value: dashboard.value?.total_nfe ?? 0,
    icon: 'i-lucide-file-text',
    to: '/fiscal/nfe'
  },
  {
    label: 'Certificados OK',
    value: (dashboard.value?.total_empresas ?? 0) - (dashboard.value?.certificados_vencendo ?? 0) - (dashboard.value?.certificados_vencidos ?? 0),
    icon: 'i-lucide-shield-check',
    to: '/empresas'
  },
  {
    label: 'Certificados Vencendo',
    value: dashboard.value?.certificados_vencendo ?? 0,
    icon: 'i-lucide-shield-alert',
    color: (dashboard.value?.certificados_vencendo ?? 0) > 0 ? 'warning' as const : undefined,
    to: '/empresas'
  }
])

const addressParts = computed(() => {
  const o = office.value
  if (!o) return ''
  const parts = [o.logradouro, o.numero, o.complemento, o.bairro, o.municipio, o.uf, o.cep].filter(Boolean)
  return parts.join(', ')
})
</script>

<template>
  <UDashboardPanel id="escritorio">
    <template #header>
      <UDashboardNavbar title="Escritório">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <BackToAdmin />
          <CompanySelector />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <div v-else-if="office" class="space-y-6">
        <UPageGrid class="lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-px">
          <UPageCard
            v-for="card in statsCards"
            :key="card.label"
            :icon="card.icon"
            :title="card.label"
            variant="subtle"
            :ui="{
              container: 'gap-y-1.5 cursor-pointer',
              wrapper: 'items-start',
              leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
              title: 'font-normal text-muted text-xs uppercase'
            }"
            class="lg:rounded-none first:rounded-l-lg last:rounded-r-lg hover:z-1"
            @click="navigateTo(card.to)"
          >
            <span class="text-2xl font-semibold text-highlighted">
              {{ card.value }}
            </span>
          </UPageCard>
        </UPageGrid>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 space-y-6">
            <UPageCard title="Dados do Escritório" variant="subtle">
              <div class="space-y-4">
                <div class="flex items-start justify-between">
                  <div>
                    <h3 class="text-lg font-semibold">
                      {{ office.name }}
                    </h3>
                    <p v-if="office.cnpj" class="text-sm text-muted">
                      CNPJ: {{ office.cnpj }}
                    </p>
                  </div>
                  <EscritorioEditProfileModal :office="office" @updated="refresh" />
                </div>

                <USeparator />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                  <div v-if="office.email">
                    <span class="text-muted">E-mail</span>
                    <p>{{ office.email }}</p>
                  </div>
                  <div v-if="office.phone">
                    <span class="text-muted">Telefone</span>
                    <p>{{ office.phone }}</p>
                  </div>
                  <div v-if="office.ie">
                    <span class="text-muted">Inscrição Estadual</span>
                    <p>{{ office.ie }}</p>
                  </div>
                </div>

                <div v-if="addressParts" class="text-sm">
                  <span class="text-muted">Endereço</span>
                  <p>{{ addressParts }}</p>
                </div>

                <div v-if="office.notes" class="text-sm">
                  <span class="text-muted">Observações</span>
                  <p>{{ office.notes }}</p>
                </div>
              </div>
            </UPageCard>
          </div>

          <div class="space-y-6">
            <UPageCard title="Plano" variant="subtle">
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-2xl font-semibold">
                    {{ planName }}
                  </span>
                  <UBadge :color="subscriptionStatus.color" variant="subtle">
                    {{ subscriptionStatus.label }}
                  </UBadge>
                </div>
                <p class="text-sm text-muted">
                  {{ planPrice }}/mês
                </p>

                <template v-if="office.subscription?.plan">
                  <USeparator />
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-muted">Empresas</span>
                      <span class="font-medium">
                        {{ office.companies_count ?? 0 }} / {{ office.subscription.plan.max_companies }}
                      </span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-muted">NFs/mês</span>
                      <span class="font-medium">
                        {{ dashboard?.total_nfe ?? 0 }} / {{ office.subscription.plan.max_nfs_month }}
                      </span>
                    </div>
                  </div>
                </template>
              </div>
            </UPageCard>

            <UPageCard title="Equipe" variant="subtle">
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-2xl font-semibold">
                    {{ activeUsers.length }}
                  </span>
                  <UButton
                    label="Gerenciar"
                    variant="ghost"
                    size="xs"
                    icon="i-lucide-arrow-right"
                    to="/escritorio/equipe"
                  />
                </div>
                <p class="text-sm text-muted">
                  usuários ativos
                </p>
                <div class="flex -space-x-2">
                  <UAvatar
                    v-for="user in activeUsers.slice(0, 5)"
                    :key="user.id"
                    :name="user.name"
                    size="sm"
                    class="ring-2 ring-[var(--ui-bg)]"
                  />
                  <UAvatar
                    v-if="activeUsers.length > 5"
                    :label="`+${activeUsers.length - 5}`"
                    size="sm"
                    class="ring-2 ring-[var(--ui-bg)]"
                  />
                </div>
              </div>
            </UPageCard>
          </div>
        </div>

        <div v-if="(dashboard?.certificados_vencidos ?? 0) > 0 || (dashboard?.certificados_vencendo ?? 0) > 0" class="p-4 rounded-lg border border-amber-500/30 bg-amber-500/5">
          <div class="flex items-start gap-3">
            <UIcon name="i-lucide-shield-alert" class="size-5 text-amber-500 shrink-0 mt-0.5" />
            <div>
              <p class="font-medium text-amber-700 dark:text-amber-400">
                Atenção aos certificados digitais
              </p>
              <p class="text-sm text-amber-600 dark:text-amber-500 mt-1">
                <template v-if="(dashboard?.certificados_vencidos ?? 0) > 0">
                  {{ dashboard?.certificados_vencidos }} certificado(s) vencido(s).
                </template>
                <template v-if="(dashboard?.certificados_vencendo ?? 0) > 0">
                  {{ dashboard?.certificados_vencendo }} certificado(s) vence(m) nos próximos 30 dias.
                </template>
                Acesse a página de Empresas para renovar.
              </p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>

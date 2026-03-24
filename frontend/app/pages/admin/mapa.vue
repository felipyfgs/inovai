<script setup lang="ts">
import type { AdminMap } from '~/types'

definePageMeta({ middleware: 'admin' })

const { data, status } = useApi<AdminMap>('/admin/offices/map', { lazy: true })

const search = ref('')

const filteredContadores = computed(() => {
  if (!data.value?.contadores) return []
  if (!search.value) return data.value.contadores
  return data.value.contadores.filter(o =>
    o.name.toLowerCase().includes(search.value.toLowerCase())
  )
})

const filteredDiretas = computed(() => {
  if (!data.value?.diretas) return []
  if (!search.value) return data.value.diretas
  return data.value.diretas.filter(o =>
    o.name.toLowerCase().includes(search.value.toLowerCase())
  )
})
</script>

<template>
  <UDashboardPanel id="admin-mapa">
    <template #header>
      <UDashboardNavbar title="Mapa: Contadores × Empresas">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <div v-else class="space-y-6">
        <div class="grid grid-cols-3 gap-4">
          <UCard>
            <p class="text-sm text-muted">
              Contadores
            </p>
            <p class="text-2xl font-bold mt-1">
              {{ data?.totals.contadores ?? 0 }}
            </p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">
              Clientes Diretos
            </p>
            <p class="text-2xl font-bold mt-1">
              {{ data?.totals.direct ?? 0 }}
            </p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">
              Total de Empresas
            </p>
            <p class="text-2xl font-bold mt-1">
              {{ data?.totals.companies ?? 0 }}
            </p>
          </UCard>
        </div>

        <UInput
          v-model="search"
          icon="i-lucide-search"
          placeholder="Filtrar por nome..."
          class="max-w-sm"
        />

        <div class="space-y-4">
          <h3 class="font-semibold text-lg flex items-center gap-2">
            <UIcon name="i-lucide-users" class="size-5" />
            Contadores ({{ filteredContadores.length }})
          </h3>

          <div v-if="filteredContadores.length === 0" class="text-muted text-sm">
            Nenhum contador encontrado.
          </div>

          <UCard
            v-for="contador in filteredContadores"
            :key="contador.id"
          >
            <div class="flex items-center justify-between mb-3">
              <div>
                <div class="flex items-center gap-2">
                  <p class="font-semibold">
                    {{ contador.name }}
                  </p>
                  <UBadge
                    v-if="contador.is_reseller"
                    label="Revendedor"
                    variant="subtle"
                    color="secondary"
                    size="xs"
                  />
                </div>
                <p class="text-sm text-muted">
                  {{ contador.cnpj || '—' }}
                </p>
              </div>
              <div class="text-right">
                <p class="text-sm text-muted">
                  Plano
                </p>
                <p class="font-medium text-sm">
                  {{ contador.subscription?.plan?.name || '—' }}
                </p>
              </div>
            </div>

            <div v-if="contador.companies && contador.companies.length > 0">
              <USeparator class="mb-3" />
              <p class="text-xs text-muted mb-2">
                {{ contador.companies.length }} empresa(s)
              </p>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                <div
                  v-for="company in contador.companies"
                  :key="company.id"
                  class="flex items-center gap-2 p-2 rounded-lg bg-elevated/50 text-sm"
                >
                  <UIcon name="i-lucide-building-2" class="size-4 text-muted shrink-0" />
                  <div class="min-w-0">
                    <p class="truncate font-medium">
                      {{ company.fantasia || company.razao_social }}
                    </p>
                    <p class="text-xs text-muted">
                      {{ company.cnpj }}
                    </p>
                  </div>
                  <UBadge
                    :label="company.ambiente === 'producao' ? 'Prod' : 'Homol'"
                    :color="company.ambiente === 'producao' ? 'success' : 'warning'"
                    variant="subtle"
                    size="xs"
                    class="ml-auto shrink-0"
                  />
                </div>
              </div>
            </div>
            <div v-else class="text-sm text-muted">
              Sem empresas cadastradas.
            </div>
          </UCard>
        </div>

        <div class="space-y-4">
          <h3 class="font-semibold text-lg flex items-center gap-2">
            <UIcon name="i-lucide-building" class="size-5" />
            Clientes Diretos ({{ filteredDiretas.length }})
          </h3>

          <div v-if="filteredDiretas.length === 0" class="text-muted text-sm">
            Nenhum cliente direto encontrado.
          </div>

          <UCard
            v-for="direto in filteredDiretas"
            :key="direto.id"
          >
            <div class="flex items-center justify-between mb-3">
              <div>
                <p class="font-semibold">
                  {{ direto.name }}
                </p>
                <p class="text-sm text-muted">
                  {{ direto.cnpj || '—' }}
                </p>
              </div>
              <div class="text-right">
                <p class="text-sm text-muted">
                  Plano
                </p>
                <p class="font-medium text-sm">
                  {{ direto.subscription?.plan?.name || '—' }}
                </p>
              </div>
            </div>

            <div v-if="direto.companies && direto.companies.length > 0">
              <USeparator class="mb-3" />
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                <div
                  v-for="company in direto.companies"
                  :key="company.id"
                  class="flex items-center gap-2 p-2 rounded-lg bg-elevated/50 text-sm"
                >
                  <UIcon name="i-lucide-building-2" class="size-4 text-muted shrink-0" />
                  <p class="truncate font-medium">
                    {{ company.fantasia || company.razao_social }}
                  </p>
                </div>
              </div>
            </div>
          </UCard>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>

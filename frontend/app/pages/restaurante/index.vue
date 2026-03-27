<script setup lang="ts">
import type { RestauranteMesa, RestauranteCardapioGrupo, RestauranteComanda, RestauranteResumo } from '~/types'
import { ref } from 'vue'
import { UBadge, UButton, UModal } from '#components'

const tab = ref('mesas')

const { data: resumo, refresh: refreshResumo } = useApi<RestauranteResumo>('/restaurante/resumo', { lazy: true })
const { data: mesas, refresh: refreshMesas } = useApi<RestauranteMesa[]>('/restaurante/mesas', { lazy: true })
const { data: cardapio } = useApi<RestauranteCardapioGrupo[]>('/restaurante/cardapio', { lazy: true })
const { data: comandas, refresh: refreshComandas } = useApi<RestauranteComanda[]>('/restaurante/comandas', { lazy: true })

const novaMesaOpen = ref(false)
const novaMesaNome = ref('')
const novaMesaCapacidade = ref(4)

const { post } = useApiMutation()

async function criarMesa() {
  if (!novaMesaNome.value) return
  try {
    await post('/restaurante/mesas', { nome: novaMesaNome.value, capacidade: novaMesaCapacidade.value })
    useAppToast().success('Mesa criada com sucesso.')
    novaMesaOpen.value = false
    novaMesaNome.value = ''
    novaMesaCapacidade.value = 4
    await refreshMesas()
  } catch {
    useAppToast().error('Erro ao criar mesa.')
  }
}

async function abrirComanda(mesaId?: number) {
  try {
    const comanda = await post<RestauranteComanda>('/restaurante/comandas', { mesa_id: mesaId, pessoas: 1 })
    useAppToast().success(`Comanda ${comanda.codigo} aberta.`)
    await refreshComandas()
    await refreshMesas()
    await refreshResumo()
  } catch {
    useAppToast().error('Erro ao abrir comanda.')
  }
}

const mesaStatusColors: Record<string, 'success' | 'warning' | 'error' | 'neutral'> = {
  livre: 'success',
  ocupada: 'warning',
  reservada: 'neutral',
  inativa: 'error'
}

const mesaStatusLabels: Record<string, string> = {
  livre: 'Livre',
  ocupada: 'Ocupada',
  reservada: 'Reservada',
  inativa: 'Inativa'
}
</script>

<template>
  <UDashboardPanel id="restaurante">
    <template #header>
      <UDashboardNavbar title="Restaurante">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right />
      </UDashboardNavbar>

      <UDashboardToolbar>
        <div class="flex gap-2 flex-1">
          <UButton :variant="tab === 'mesas' ? 'solid' : 'outline'" label="Mesas" @click="tab = 'mesas'" />
          <UButton :variant="tab === 'comandas' ? 'solid' : 'outline'" label="Comandas" @click="tab = 'comandas'" />
          <UButton :variant="tab === 'cardapio' ? 'solid' : 'outline'" label="Cardápio" @click="tab = 'cardapio'" />
        </div>
      </UDashboardToolbar>
    </template>

    <template #body>
      <UPageGrid v-if="tab === 'mesas'" class="lg:grid-cols-4 gap-4 mb-6">
        <UPageCard
          icon="i-lucide-circle-check"
          title="Mesas Livres"
          variant="subtle"
          color="success"
        >
          <span class="text-2xl font-semibold">{{ resumo?.mesas_livres ?? 0 }}</span>
        </UPageCard>
        <UPageCard
          icon="i-lucide-circle-x"
          title="Mesas Ocupadas"
          variant="subtle"
          color="warning"
        >
          <span class="text-2xl font-semibold">{{ resumo?.mesas_ocupadas ?? 0 }}</span>
        </UPageCard>
        <UPageCard icon="i-lucide-file-text" title="Comandas Abertas" variant="subtle">
          <span class="text-2xl font-semibold">{{ resumo?.comandas_abertas ?? 0 }}</span>
        </UPageCard>
        <UPageCard icon="i-lucide-dollar-sign" title="Receita Hoje" variant="subtle">
          <span class="text-2xl font-semibold">{{ formatCurrency(resumo?.receita_hoje ?? 0) }}</span>
        </UPageCard>
      </UPageGrid>

      <div v-if="tab === 'mesas'" class="space-y-4">
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-semibold">
            Mesas
          </h2>
          <UButton icon="i-lucide-plus" label="Nova Mesa" @click="novaMesaOpen = true" />
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <div
            v-for="mesa in mesas"
            :key="mesa.id"
            class="p-4 rounded-lg border-2 text-center cursor-pointer hover:border-primary/50 transition-colors"
            :class="mesa.status === 'livre' ? 'border-green-500/30 bg-green-500/5' : mesa.status === 'ocupada' ? 'border-amber-500/30 bg-amber-500/5' : 'border-default'"
            @click="mesa.status === 'livre' && abrirComanda(mesa.id)"
          >
            <p class="font-semibold">
              {{ mesa.nome }}
            </p>
            <p class="text-sm text-muted">
              {{ mesa.capacidade }} lugares
            </p>
            <UBadge :color="mesaStatusColors[mesa.status]" variant="subtle" class="mt-2">
              {{ mesaStatusLabels[mesa.status] }}
            </UBadge>
          </div>
        </div>
      </div>

      <div v-else-if="tab === 'comandas'" class="space-y-4">
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-semibold">
            Comandas Abertas
          </h2>
          <UButton icon="i-lucide-plus" label="Abrir Comanda" @click="abrirComanda()" />
        </div>

        <div v-if="comandas?.length === 0" class="text-center py-12 text-muted">
          Nenhuma comanda aberta
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <UPageCard
            v-for="comanda in comandas"
            :key="comanda.id"
            :title="`Comanda ${comanda.codigo}`"
            variant="subtle"
          >
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-muted">Mesa:</span>
                <span>{{ comanda.mesa?.nome ?? 'Sem mesa' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted">Abertura:</span>
                <span>{{ new Date(comanda.opened_at).toLocaleTimeString('pt-BR') }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted">Pessoas:</span>
                <span>{{ comanda.pessoas }}</span>
              </div>
              <USeparator />
              <div class="flex justify-between font-semibold">
                <span>Total:</span>
                <span>{{ formatCurrency(Number(comanda.valor_total)) }}</span>
              </div>
            </div>
          </UPageCard>
        </div>
      </div>

      <div v-else-if="tab === 'cardapio'" class="space-y-4">
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-semibold">
            Cardápio
          </h2>
        </div>

        <div v-if="cardapio?.length === 0" class="text-center py-12 text-muted">
          Nenhum grupo no cardápio
        </div>

        <div v-else class="space-y-6">
          <div v-for="grupo in cardapio" :key="grupo.id">
            <h3 class="font-semibold text-lg mb-3">
              {{ grupo.nome }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <UPageCard
                v-for="item in grupo.itens"
                :key="item.id"
                variant="subtle"
                :ui="{ body: 'space-y-2' }"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-medium">
                      {{ item.nome }}
                    </p>
                    <p v-if="item.descricao" class="text-sm text-muted">
                      {{ item.descricao }}
                    </p>
                  </div>
                  <UBadge v-if="!item.disponivel" color="error" variant="subtle">
                    Indisponível
                  </UBadge>
                </div>
                <p class="text-lg font-semibold text-primary">
                  {{ formatCurrency(Number(item.preco)) }}
                </p>
              </UPageCard>
            </div>
          </div>
        </div>
      </div>

      <UModal v-model:open="novaMesaOpen">
        <template #header>
          <h3 class="font-semibold">
            Nova Mesa
          </h3>
        </template>

        <template #body>
          <div class="space-y-4">
            <UFormField label="Nome">
              <UInput v-model="novaMesaNome" placeholder="Ex: Mesa 1" />
            </UFormField>
            <UFormField label="Capacidade">
              <UInput v-model="novaMesaCapacidade" type="number" min="1" />
            </UFormField>
          </div>
        </template>

        <template #footer>
          <div class="flex justify-end gap-3">
            <UButton variant="ghost" label="Cancelar" @click="novaMesaOpen = false" />
            <UButton label="Criar" @click="criarMesa" />
          </div>
        </template>
      </UModal>
    </template>
  </UDashboardPanel>
</template>

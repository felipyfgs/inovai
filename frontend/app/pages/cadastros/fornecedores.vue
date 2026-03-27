<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { Fornecedor, PaginatedResponse } from '~/types'
import { h, ref } from 'vue'
import { UButton, UDropdownMenu } from '#components'

const { data, status, refresh } = useApi<PaginatedResponse<Fornecedor>>('/fornecedores', {
  lazy: true
})

const editingFornecedor = ref<Fornecedor | null>(null)

const fornecedores = computed(() => data.value?.data || [])

const columns: TableColumn<Fornecedor>[] = [
  {
    accessorKey: 'razao_social',
    header: 'Razão Social'
  },
  {
    accessorKey: 'fantasia',
    header: 'Fantasia',
    cell: ({ row }) => row.original.fantasia ?? '-'
  },
  {
    accessorKey: 'cpf_cnpj',
    header: 'CNPJ/CPF',
    cell: ({ row }) => row.original.cpf_cnpj ?? '-'
  },
  {
    accessorKey: 'telefone',
    header: 'Telefone',
    cell: ({ row }) => row.original.telefone ?? '-'
  },
  {
    accessorKey: 'email',
    header: 'E-mail',
    cell: ({ row }) => row.original.email ?? '-'
  },
  {
    accessorKey: 'condicao_pagamento',
    header: 'Condição Pagamento',
    cell: ({ row }) => row.original.condicao_pagamento ?? '-'
  },
  {
    accessorKey: 'prazo_entrega',
    header: 'Prazo Entrega',
    cell: ({ row }) => row.original.prazo_entrega ? `${row.original.prazo_entrega} dias` : '-'
  },
  {
    accessorKey: 'avaliacao',
    header: 'Avaliação',
    cell: ({ row }) => {
      const avaliacao = row.original.avaliacao
      if (!avaliacao) return '-'
      return h('span', { class: 'text-amber-500' }, '★'.repeat(avaliacao) + '☆'.repeat(5 - avaliacao))
    }
  },
  {
    id: 'actions',
    header: '',
    cell: ({ row }) => {
      const fornecedor = row.original
      const items = [
        { type: 'label' as const, label: 'Ações' },
        {
          label: 'Editar dados de fornecedor',
          icon: 'i-lucide-pencil',
          onSelect: () => {
            editingFornecedor.value = fornecedor
          }
        },
        { type: 'separator' as const },
        {
          label: 'Ver no cadastro de pessoas',
          icon: 'i-lucide-external-link',
          onSelect: () => {
            navigateTo('/cadastros/pessoas')
          }
        }
      ]

      return h(UDropdownMenu, { items }, {
        default: () => h(UButton, { icon: 'i-lucide-ellipsis', variant: 'ghost', size: 'xs' })
      })
    }
  }
]
</script>

<template>
  <UDashboardPanel id="fornecedores">
    <template #header>
      <UDashboardNavbar title="Fornecedores">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right />
      </UDashboardNavbar>

      <UDashboardToolbar>
        <div class="flex-1" />

        <UButton
          icon="i-lucide-plus"
          label="Novo Fornecedor"
          to="/cadastros/pessoas"
        />
      </UDashboardToolbar>
    </template>

    <template #body>
      <div v-if="status === 'pending'" class="flex items-center justify-center h-48">
        <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
      </div>

      <UTable
        v-else
        :columns="columns"
        :data="fornecedores"
      />

      <FornecedoresEditModal
        :fornecedor="editingFornecedor"
        @updated="editingFornecedor = null; refresh()"
        @close="editingFornecedor = null"
      />
    </template>
  </UDashboardPanel>
</template>

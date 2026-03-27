<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { upperFirst } from 'scule'
import { getPaginationRowModel } from '@tanstack/table-core'
import type { Row } from '@tanstack/table-core'
import type { Mdfe } from '~/types'
import { formatCurrency } from '~/utils'

import { UButton, UBadge, UDropdownMenu, UCheckbox } from '#components'

const toast = useToast()
const table = useTemplateRef('table')
const { signMdfe, transmitMdfe, extractMessage } = useMdfe()

const columnFilters = ref([])
const columnVisibility = ref()
const rowSelection = ref({})

const addModal = useTemplateRef('addModal')
const editModal = useTemplateRef('editModal')
const deleteModal = useTemplateRef('deleteModal')
const cancelModal = useTemplateRef('cancelModal')
const encerrarModal = useTemplateRef('encerrarModal')

const selectedMdfe = ref<Mdfe | null>(null)

const { data, status, refresh } = useMdfe().listMdfes()

const mdfes = computed(() => data.value?.data || [])

function getRowItems(row: Row<Mdfe>) {
  const mdfe = row.original
  return [
    {
      type: 'label' as const,
      label: 'Ações'
    },
    {
      label: 'Copiar chave',
      icon: 'i-lucide-copy',
      onSelect() {
        navigator.clipboard.writeText(mdfe.chave || '')
        toast.add({ title: 'Copiado', description: 'Chave copiada' })
      }
    },
    {
      label: 'Ver XML',
      icon: 'i-lucide-code'
    },
    ...(mdfe.status === 'rascunho'
      ? [
          { type: 'separator' as const },
          {
            label: 'Assinar',
            icon: 'i-lucide-pen-line',
            async onSelect() {
              try {
                await signMdfe(mdfe.id)
                toast.add({ title: 'Assinado', description: 'MDF-e assinado com sucesso', color: 'success' })
                refresh()
              } catch (error) {
                toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao assinar MDF-e.', color: 'error' })
              }
            }
          }
        ]
      : []),
    ...(mdfe.status === 'assinada'
      ? [
          { type: 'separator' as const },
          {
            label: 'Transmitir',
            icon: 'i-lucide-send',
            async onSelect() {
              try {
                await transmitMdfe(mdfe.id)
                toast.add({ title: 'Transmitido', description: 'MDF-e transmitido com sucesso', color: 'success' })
                refresh()
              } catch (error) {
                toast.add({ title: 'Erro', description: extractMessage(error) || 'Erro ao transmitir MDF-e.', color: 'error' })
              }
            }
          }
        ]
      : []),
    ...(mdfe.status === 'autorizada'
      ? [
          { type: 'separator' as const },
          {
            label: 'Cancelar',
            icon: 'i-lucide-x-circle',
            color: 'error' as const,
            onSelect() {
              selectedMdfe.value = mdfe
              cancelModal.value?.open?.()
            }
          },
          {
            label: 'Encerrar',
            icon: 'i-lucide-lock',
            onSelect() {
              selectedMdfe.value = mdfe
              encerrarModal.value?.open?.()
            }
          }
        ]
      : []),
    { type: 'separator' as const },
    {
      label: 'Excluir',
      icon: 'i-lucide-trash-2',
      color: 'error' as const,
      onSelect() {
        selectedMdfe.value = mdfe
        deleteModal.value?.open?.()
      }
    }
  ]
}

const columns: TableColumn<Mdfe>[] = [
  {
    id: 'select',
    header: ({ table }) =>
      h(UCheckbox, {
        'modelValue': table.getIsSomePageRowsSelected()
          ? 'indeterminate'
          : table.getIsAllPageRowsSelected(),
        'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
          table.toggleAllPageRowsSelected(!!value),
        'ariaLabel': 'Select all'
      }),
    cell: ({ row }) =>
      h(UCheckbox, {
        'modelValue': row.getIsSelected(),
        'onUpdate:modelValue': (value: boolean | 'indeterminate') => row.toggleSelected(!!value),
        'ariaLabel': 'Select row'
      })
  },
  {
    accessorKey: 'numero',
    header: 'Nº'
  },
  {
    accessorKey: 'serie',
    header: 'Série'
  },
  {
    accessorKey: 'data_emissao',
    header: 'Emissão',
    cell: ({ row }) => row.original.data_emissao
      ? new Date(row.original.data_emissao).toLocaleDateString('pt-BR')
      : '—'
  },
  {
    accessorKey: 'uf_carregamento',
    header: 'UF Carreg.'
  },
  {
    accessorKey: 'uf_descarregamento',
    header: 'UF Descarreg.'
  },
  {
    accessorKey: 'veiculo_placa',
    header: 'Placa',
    cell: ({ row }) => row.original.veiculo_placa || '—'
  },
  {
    accessorKey: 'status',
    header: 'Status',
    filterFn: 'equals',
    cell: ({ row }) => {
      const color = {
        rascunho: 'neutral' as const,
        assinada: 'info' as const,
        transmitida: 'info' as const,
        autorizada: 'success' as const,
        rejeitada: 'error' as const,
        cancelada: 'error' as const,
        encerrada: 'neutral' as const
      }[row.original.status] || 'neutral' as const

      return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () =>
        row.original.status
      )
    }
  },
  {
    accessorKey: 'valor_carga',
    header: 'Valor Carga',
    cell: ({ row }) => formatCurrency(Number(row.original.valor_carga))
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'text-right' },
        h(
          UDropdownMenu,
          { content: { align: 'end' }, items: getRowItems(row) },
          () => h(UButton, { icon: 'i-lucide-ellipsis-vertical', color: 'neutral', variant: 'ghost', class: 'ml-auto' })
        )
      )
    }
  }
]

const statusFilter = ref('all')

watch(() => statusFilter.value, (newVal) => {
  if (!table?.value?.tableApi) return
  const col = table.value.tableApi.getColumn('status')
  if (!col) return
  col.setFilterValue(newVal === 'all' ? undefined : newVal)
})

const pagination = ref({ pageIndex: 0, pageSize: 10 })

function onCreated() {
  refresh()
}

function onUpdated() {
  refresh()
}

function onDeleted() {
  refresh()
}

function onCancelled() {
  refresh()
}

function onEncerrado() {
  refresh()
}
</script>

<template>
  <UDashboardPanel id="mdfe">
    <template #header>
      <UDashboardNavbar title="MDF-e (Manifesto de Documentos Fiscais)">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <BackToAdmin />
          <CompanySelector />
          <MdfeAddModal ref="addModal" @created="onCreated" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <div />

        <div class="flex flex-wrap items-center gap-1.5">
          <USelect
            v-model="statusFilter"
            :items="[
              { label: 'Todos', value: 'all' },
              { label: 'Rascunho', value: 'rascunho' },
              { label: 'Assinada', value: 'assinada' },
              { label: 'Autorizada', value: 'autorizada' },
              { label: 'Rejeitada', value: 'rejeitada' },
              { label: 'Cancelada', value: 'cancelada' },
              { label: 'Encerrada', value: 'encerrada' }
            ]"
            :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
            placeholder="Filtrar status"
            class="min-w-28"
          />
          <UDropdownMenu
            :items="
              table?.tableApi
                ?.getAllColumns()
                .filter((column: any) => column.getCanHide())
                .map((column: any) => ({
                  label: upperFirst(column.id),
                  type: 'checkbox' as const,
                  checked: column.getIsVisible(),
                  onUpdateChecked(checked: boolean) {
                    table?.tableApi?.getColumn(column.id)?.toggleVisibility(!!checked)
                  },
                  onSelect(e?: Event) {
                    e?.preventDefault()
                  }
                }))
            "
            :content="{ align: 'end' }"
          >
            <UButton
              label="Exibir"
              color="neutral"
              variant="outline"
              trailing-icon="i-lucide-settings-2"
            />
          </UDropdownMenu>
        </div>
      </div>

      <UTable
        ref="table"
        v-model:column-filters="columnFilters"
        v-model:column-visibility="columnVisibility"
        v-model:row-selection="rowSelection"
        v-model:pagination="pagination"
        :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
        class="shrink-0"
        :data="mdfes"
        :columns="columns"
        :loading="status === 'pending'"
        :ui="{
          base: 'table-fixed border-separate border-spacing-0',
          thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
          tbody: '[&>tr]:last:[&>td]:border-b-0',
          th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
          td: 'border-b border-default',
          separator: 'h-0'
        }"
      />

      <div class="flex items-center justify-between gap-3 border-t border-default pt-4 mt-auto">
        <div class="text-sm text-muted">
          {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} de
          {{ table?.tableApi?.getFilteredRowModel().rows.length || 0 }} registro(s) selecionado(s).
        </div>
        <div class="flex items-center gap-1.5">
          <UPagination
            :default-page="(table?.tableApi?.getState().pagination.pageIndex || 0) + 1"
            :items-per-page="table?.tableApi?.getState().pagination.pageSize"
            :total="table?.tableApi?.getFilteredRowModel().rows.length"
            @update:page="(p: number) => table?.tableApi?.setPageIndex(p - 1)"
          />
        </div>
      </div>

      <MdfeEditModal
        v-if="selectedMdfe"
        ref="editModal"
        :mdfe="selectedMdfe"
        @updated="onUpdated"
      />
      <MdfeDeleteModal
        v-if="selectedMdfe"
        ref="deleteModal"
        :mdfe="selectedMdfe"
        @deleted="onDeleted"
      />
      <MdfeCancelModal
        v-if="selectedMdfe"
        ref="cancelModal"
        :mdfe="selectedMdfe"
        @cancelled="onCancelled"
      />
      <MdfeEncerrarModal
        v-if="selectedMdfe"
        ref="encerrarModal"
        :mdfe="selectedMdfe"
        @encerrado="onEncerrado"
      />
    </template>
  </UDashboardPanel>
</template>

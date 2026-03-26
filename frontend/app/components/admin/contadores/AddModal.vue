<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import type { Plan } from '~/types'

const emit = defineEmits<{ created: [] }>()

const open = ref(false)
const loading = ref(false)
const { post } = useApiMutation()
const { handleError } = useApiError()
const formRef = useTemplateRef('formRef')

const { data: plansData } = useApi<Plan[]>('/admin/plans')

const plans = computed(() => (plansData.value ?? []).filter((p: Plan) => p.is_active))

const officeSchema = z.object({
  name: z.string().min(2, 'Mínimo 2 caracteres'),
  cnpj: z.string().min(11, 'CPF/CNPJ inválido'),
  email: z.string().email('E-mail inválido').optional().or(z.literal('')),
  phone: z.string().optional(),
  ie: z.string().optional(),
  logradouro: z.string().optional(),
  numero: z.string().optional(),
  complemento: z.string().optional(),
  bairro: z.string().optional(),
  municipio: z.string().optional(),
  uf: z.string().optional(),
  cep: z.string().optional(),
  notes: z.string().optional(),
  plan_id: z.number().min(1, 'Selecione um plano').optional().or(z.undefined())
})

const userSchema = z.object({
  user_name: z.string().min(2, 'Mínimo 2 caracteres'),
  user_email: z.string().email('E-mail inválido'),
  user_password: z.string().min(6, 'Mínimo 6 caracteres'),
  user_password_confirmation: z.string()
}).refine(data => data.user_password === data.user_password_confirmation, {
  message: 'Senhas não conferem',
  path: ['user_password_confirmation']
})

const schema = officeSchema.merge(userSchema)

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  cnpj: '',
  email: '',
  phone: '',
  ie: '',
  logradouro: '',
  numero: '',
  complemento: '',
  bairro: '',
  municipio: '',
  uf: '',
  cep: '',
  notes: '',
  plan_id: undefined,
  user_name: '',
  user_email: '',
  user_password: '',
  user_password_confirmation: ''
})

const ufOptions = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
  'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
  'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
].map(uf => ({ label: uf, value: uf }))

function resetForm() {
  Object.assign(state, {
    name: '', cnpj: '', email: '', phone: '', ie: '',
    logradouro: '', numero: '', complemento: '', bairro: '',
    municipio: '', uf: '', cep: '', notes: '', plan_id: undefined,
    user_name: '', user_email: '', user_password: '', user_password_confirmation: ''
  })
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    const { user_name, user_email, user_password, user_password_confirmation, ...officeData } = event.data
    const office = await post<{ id: number }>('/admin/offices', officeData)

    await post('/users', {
      name: user_name,
      email: user_email,
      password: user_password,
      password_confirmation: user_password_confirmation,
      role: 'accountant',
      office_id: office.id,
      is_active: true
    })

    useAppToast().success('Escritório e usuário criados com sucesso')
    open.value = false
    emit('created')
    resetForm()
  } catch (e: unknown) {
    handleError(e, 'Erro ao cadastrar escritório')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Novo Escritório"
    description="Preencha os dados do escritório contábil e crie o acesso do contador."
    :ui="{ content: 'w-full sm:max-w-lg', body: 'max-h-[80vh] overflow-y-auto', footer: 'justify-end shrink-0' }"
  >
    <UButton label="Novo Escritório" icon="i-lucide-plus" />

    <template #body>
      <UForm
        :schema="schema"
        :state="state"
        class="space-y-4"
        @submit="onSubmit"
      >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField
            label="Nome / Razão Social"
            name="name"
            required
            class="sm:col-span-2"
          >
            <UInput v-model="state.name" placeholder="Nome do contador ou escritório" class="w-full" />
          </UFormField>

          <UFormField label="CPF/CNPJ" name="cnpj" required>
            <UInput v-model="state.cnpj" placeholder="000.000.000-00 ou 00.000.000/0001-00" class="w-full" />
          </UFormField>

          <UFormField label="Inscrição Estadual" name="ie">
            <UInput v-model="state.ie" placeholder="Inscrição estadual" class="w-full" />
          </UFormField>

          <UFormField label="E-mail" name="email">
            <UInput
              v-model="state.email"
              type="email"
              placeholder="contato@escritorio.com.br"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Telefone" name="phone">
            <UInput v-model="state.phone" placeholder="(00) 00000-0000" class="w-full" />
          </UFormField>
        </div>

        <USeparator label="Endereço" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Logradouro" name="logradouro" class="sm:col-span-2">
            <UInput v-model="state.logradouro" placeholder="Rua, Avenida..." class="w-full" />
          </UFormField>

          <UFormField label="Número" name="numero">
            <UInput v-model="state.numero" placeholder="Nº" class="w-full" />
          </UFormField>

          <UFormField label="Complemento" name="complemento">
            <UInput v-model="state.complemento" placeholder="Sala, Bloco..." class="w-full" />
          </UFormField>

          <UFormField label="Bairro" name="bairro">
            <UInput v-model="state.bairro" placeholder="Bairro" class="w-full" />
          </UFormField>

          <UFormField label="Município" name="municipio">
            <UInput v-model="state.municipio" placeholder="Cidade" class="w-full" />
          </UFormField>

          <UFormField label="UF" name="uf">
            <USelect
              v-model="state.uf"
              :items="ufOptions"
              placeholder="UF"
              class="w-full"
            />
          </UFormField>

          <UFormField label="CEP" name="cep">
            <UInput v-model="state.cep" placeholder="00000-000" class="w-full" />
          </UFormField>
        </div>

        <USeparator label="Plano" />

        <UFormField label="Plano" name="plan_id">
          <USelect
            v-model="state.plan_id"
            :items="plans.map(p => ({ label: `${p.name} — R$ ${Number(p.price).toFixed(2).replace('.', ',')}/mês`, value: p.id }))"
            placeholder="Nenhum plano"
            class="w-full"
            value-attrs
          />
        </UFormField>

        <USeparator label="Acesso do Contador" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField
            label="Nome do Usuário"
            name="user_name"
            required
            class="sm:col-span-2"
          >
            <UInput v-model="state.user_name" placeholder="Nome completo do contador" class="w-full" />
          </UFormField>

          <UFormField
            label="E-mail de Acesso"
            name="user_email"
            required
            class="sm:col-span-2"
          >
            <UInput
              v-model="state.user_email"
              type="email"
              placeholder="email@contador.com.br"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Senha" name="user_password" required>
            <UInput
              v-model="state.user_password"
              type="password"
              placeholder="Mínimo 6 caracteres"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Confirmar Senha" name="user_password_confirmation" required>
            <UInput
              v-model="state.user_password_confirmation"
              type="password"
              placeholder="Repita a senha"
              class="w-full"
            />
          </UFormField>
        </div>

        <UFormField label="Observações" name="notes">
          <UTextarea v-model="state.notes" placeholder="Observações internas..." class="w-full" />
        </UFormField>
      </UForm>
    </template>

    <template #footer="{ close }">
      <UButton
        label="Cancelar"
        color="neutral"
        variant="outline"
        @click="close"
      />
      <UButton
        label="Cadastrar"
        color="primary"
        :loading="loading"
        @click="formRef?.submit()"
      />
    </template>
  </UModal>
</template>

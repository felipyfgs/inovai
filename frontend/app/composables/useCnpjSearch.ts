export interface CnpjData {
  razao_social: string
  fantasia: string
  cnpj: string
  ie: string
  im: string
  crt: number
  logradouro: string
  numero: string
  complemento: string
  bairro: string
  municipio: string
  municipio_ibge: string
  uf: string
  cep: string
  telefone: string
  email: string
}

interface CnpjApiResponse {
  razao_social: string
  porte: { id: string, descricao: string }
  estabelecimento: {
    cnpj: string
    nome_fantasia: string | null
    tipo_logradouro: string | null
    logradouro: string | null
    numero: string | null
    complemento: string | null
    bairro: string | null
    cep: string | null
    ddd1: string | null
    telefone1: string | null
    email: string | null
    cidade: { nome: string, ibge_id: number } | null
    estado: { sigla: string } | null
    inscricoes_estaduais: Array<{
      inscricao_estadual: string
      ativo: boolean
      estado: { sigla: string }
    }>
  }
}

export function useCnpjSearch() {
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function search(cnpj: string): Promise<CnpjData | null> {
    const cleanCnpj = cnpj.replace(/\D/g, '')
    if (cleanCnpj.length !== 14) {
      error.value = 'CNPJ deve ter 14 dígitos'
      return null
    }

    loading.value = true
    error.value = null

    try {
      const response = await $fetch<CnpjApiResponse>(
        `https://publica.cnpj.ws/cnpj/${cleanCnpj}`,
        { timeout: 10000 }
      )

      const ieAtiva = response.estabelecimento.inscricoes_estaduais?.find(
        ie => ie.ativo
      )

      // Porte: 01, 02, 03 = Simples Nacional, 05 = Demais (Regime Normal)
      const porteId = response.porte?.id || '05'
      const crt = porteId === '05' ? 3 : 1

      const tipoLogradouro = response.estabelecimento.tipo_logradouro || ''
      const logradouro = response.estabelecimento.logradouro || ''
      const fullLogradouro = tipoLogradouro
        ? `${tipoLogradouro} ${logradouro}`.trim()
        : logradouro

      const ddd = response.estabelecimento.ddd1 || ''
      const telefone = response.estabelecimento.telefone1 || ''
      const fullTelefone = ddd && telefone ? `(${ddd}) ${telefone}` : ''

      return {
        razao_social: response.razao_social || '',
        fantasia: response.estabelecimento.nome_fantasia || '',
        cnpj: response.estabelecimento.cnpj || cleanCnpj,
        ie: ieAtiva?.inscricao_estadual || '',
        im: '',
        crt,
        logradouro: fullLogradouro,
        numero: response.estabelecimento.numero || '',
        complemento: response.estabelecimento.complemento || '',
        bairro: response.estabelecimento.bairro || '',
        municipio: response.estabelecimento.cidade?.nome || '',
        municipio_ibge: response.estabelecimento.cidade?.ibge_id?.toString() || '',
        uf: response.estabelecimento.estado?.sigla || '',
        cep: response.estabelecimento.cep || '',
        telefone: fullTelefone,
        email: response.estabelecimento.email || ''
      }
    } catch (e: unknown) {
      const err = e as { statusCode?: number }
      if (err.statusCode === 429) {
        error.value = 'Limite de consultas excedido. Aguarde 1 minuto.'
      } else if (err.statusCode === 404) {
        error.value = 'CNPJ não encontrado.'
      } else {
        error.value = 'Erro ao buscar CNPJ. Tente novamente.'
      }
      return null
    } finally {
      loading.value = false
    }
  }

  return {
    search,
    loading: readonly(loading),
    error: readonly(error)
  }
}

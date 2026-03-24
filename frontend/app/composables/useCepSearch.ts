export interface CepData {
  logradouro: string
  bairro: string
  municipio: string
  municipio_ibge: string
  uf: string
}

interface ViaCepResponse {
  logradouro: string
  bairro: string
  localidade: string
  uf: string
  ibge: string
  erro?: boolean
}

export function useCepSearch() {
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function search(cep: string): Promise<CepData | null> {
    const cleanCep = cep.replace(/\D/g, '')
    if (cleanCep.length !== 8) {
      return null
    }

    loading.value = true
    error.value = null

    try {
      const response = await $fetch<ViaCepResponse>(
        `https://viacep.com.br/ws/${cleanCep}/json/`,
        { timeout: 10000 }
      )

      if (response.erro) {
        error.value = 'CEP não encontrado.'
        return null
      }

      return {
        logradouro: response.logradouro || '',
        bairro: response.bairro || '',
        municipio: response.localidade || '',
        municipio_ibge: response.ibge || '',
        uf: response.uf || ''
      }
    } catch {
      error.value = 'Erro ao buscar CEP. Tente novamente.'
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

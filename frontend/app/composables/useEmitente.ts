import type { Company, EmitenteCertificado } from '~/types'

export function useEmitente() {
  const { data: emitente, status, refresh } = useApi<Company>('/emitente', {
    lazy: true
  })

  const { data: certificado, refresh: refreshCertificado } = useApi<EmitenteCertificado>('/emitente/certificado', {
    lazy: true
  })

  const { put, post, del } = useApiMutation()
  const { extractMessage } = useApiError()

  async function updateDados(data: Partial<Company>) {
    try {
      await put('/emitente/dados', data)
      useAppToast().success('Dados do emitente atualizados com sucesso.')
      await refresh()
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  async function updateNumeracao(data: {
    serie_nfe: number
    proximo_numero_nfe: number
    serie_nfce: number
    proximo_numero_nfce: number
    serie_cte: number
    proximo_numero_cte: number
    serie_mdfe: number
    proximo_numero_mdfe: number
  }) {
    try {
      await put('/emitente/numeracao', data)
      useAppToast().success('Numeração atualizada com sucesso.')
      await refresh()
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  async function updateCsc(data: { csc_id: string | null, csc_token: string | null }) {
    try {
      await put('/emitente/csc', data)
      useAppToast().success('CSC atualizado com sucesso.')
      await refresh()
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  async function updateAmbiente(ambiente: 'homologacao' | 'producao') {
    try {
      await put('/emitente/ambiente', { ambiente })
      useAppToast().success('Ambiente atualizado com sucesso.')
      await refresh()
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  async function uploadCertificado(file: File, senha: string) {
    const formData = new FormData()
    formData.append('certificado', file)
    formData.append('senha', senha)

    try {
      const result = await post<{ message: string, validade: string }>('/emitente/certificado', formData)
      useAppToast().success('Certificado enviado com sucesso.')
      await refresh()
      await refreshCertificado()
      return result
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  async function removerCertificado() {
    try {
      await del('/emitente/certificado')
      useAppToast().success('Certificado removido com sucesso.')
      await refresh()
      await refreshCertificado()
    } catch (error) {
      useAppToast().error(extractMessage(error))
      throw error
    }
  }

  return {
    emitente,
    status,
    refresh,
    certificado,
    refreshCertificado,
    updateDados,
    updateNumeracao,
    updateCsc,
    updateAmbiente,
    uploadCertificado,
    removerCertificado
  }
}

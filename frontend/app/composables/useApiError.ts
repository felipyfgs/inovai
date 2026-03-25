export function useApiError() {
  const toast = useToast()

  function extractMessage(error: unknown): string {
    const err = error as {
      response?: { _data?: { message?: string } }
      message?: string
    }
    return err?.response?._data?.message || err?.message || 'Erro desconhecido'
  }

  function handleError(error: unknown, fallbackMessage = 'Operação falhou'): string {
    const message = extractMessage(error) || fallbackMessage
    toast.add({ title: message, color: 'error' })
    console.error('[API Error]', error)
    return message
  }

  return { handleError, extractMessage }
}

import type { UseFetchOptions } from 'nuxt/app'

export function useApi<T>(url: string | Ref<string>, options: UseFetchOptions<T> = {}) {
  const { currentCompany } = useCurrentCompany()
  const { $sanctumClient } = useNuxtApp()

  const headers: Record<string, string> = {}
  if (currentCompany.value?.id) {
    headers['X-Company-Id'] = String(currentCompany.value.id)
  }

  const apiUrl = typeof url === 'string' ? `/api${url}` : computed(() => `/api${url.value}`)

  return useAsyncData(
    typeof url === 'string' ? url : url.value,
    () => $sanctumClient<T>(apiUrl, {
      ...options,
      headers: {
        ...headers,
        ...options.headers
      }
    })
  )
}

export function useApiMutation() {
  const { currentCompany } = useCurrentCompany()
  const { $sanctumClient } = useNuxtApp()

  function getHeaders(): Record<string, string> {
    const headers: Record<string, string> = {}
    if (currentCompany.value?.id) {
      headers['X-Company-Id'] = String(currentCompany.value.id)
    }
    return headers
  }

  async function mutate<T = any>(url: string, options: { method?: string, body?: any } = {}): Promise<T> {
    return await $sanctumClient<T>(`/api${url}`, {
      method: (options.method || 'POST') as any,
      body: options.body,
      headers: getHeaders()
    }) as Promise<T>
  }

  const post = <T = any>(url: string, body?: any) => mutate<T>(url, { method: 'POST', body })
  const put = <T = any>(url: string, body?: any) => mutate<T>(url, { method: 'PUT', body })
  const patch = <T = any>(url: string, body?: any) => mutate<T>(url, { method: 'PATCH', body })
  const del = <T = any>(url: string) => mutate<T>(url) => mutate<T>(url, { method: 'DELETE' })

  return { mutate, post, put, patch, del }
}

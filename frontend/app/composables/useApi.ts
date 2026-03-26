import { isRef } from 'vue'
import type { AsyncDataOptions } from 'nuxt/app'

type ApiOptions = {
  lazy?: boolean
  server?: boolean
  query?: Record<string, unknown> | Ref<Record<string, unknown>>
  headers?: Record<string, string>
  watch?: Ref[]
}

export function useApi<T>(url: string | Ref<string>, options: ApiOptions = {}) {
  const { currentCompany } = useCurrentCompany()
  const { currentOffice } = useCurrentOffice()
  const { $sanctumClient } = useNuxtApp()

  const { lazy, server, query, headers: extraHeaders, watch: watchOption } = options

  const getHeaders = (): Record<string, string> => {
    const h: Record<string, string> = {}
    if (currentCompany.value?.id) {
      h['X-Company-Id'] = String(currentCompany.value.id)
    }
    if (currentOffice.value?.id) {
      h['X-Office-Id'] = String(currentOffice.value.id)
    }
    return h
  }

  const companyId = computed(() => currentCompany.value?.id ?? 'none')

  const watchSources: Ref[] = [companyId]
  if (isRef(query)) watchSources.push(query as Ref)
  if (isRef(url)) watchSources.push(url as Ref)
  if (watchOption?.length) watchSources.push(...watchOption)

  const asyncDataOptions: AsyncDataOptions<T> = {
    lazy: lazy ?? false,
    server: server ?? false,
    watch: watchSources
  }

  const resolvedUrl = typeof url === 'string' ? url : url.value

  return useAsyncData<T>(
    resolvedUrl,
    () => {
      const resolvedQuery = query ? (isRef(query) ? query.value : query) : undefined
      const currentUrl = typeof url === 'string' ? url : url.value
      return $sanctumClient<T>(`/api${currentUrl}`, {
        query: resolvedQuery,
        headers: {
          ...getHeaders(),
          ...extraHeaders
        }
      })
    },
    asyncDataOptions
  )
}

export function useApiMutation() {
  const { currentCompany } = useCurrentCompany()
  const { currentOffice } = useCurrentOffice()
  const { $sanctumClient } = useNuxtApp()

  function getHeaders(): Record<string, string> {
    const headers: Record<string, string> = {}
    if (currentCompany.value?.id) {
      headers['X-Company-Id'] = String(currentCompany.value.id)
    }
    if (currentOffice.value?.id) {
      headers['X-Office-Id'] = String(currentOffice.value.id)
    }
    return headers
  }

  async function mutate<T = unknown>(url: string, options: { method?: string, body?: unknown } = {}): Promise<T> {
    return await $sanctumClient<T>(`/api${url}`, {
      method: (options.method || 'POST') as 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE',
      body: options.body,
      headers: getHeaders()
    }) as Promise<T>
  }

  const post = <T = unknown>(url: string, body?: unknown) => mutate<T>(url, { method: 'POST', body })
  const put = <T = unknown>(url: string, body?: unknown) => mutate<T>(url, { method: 'PUT', body })
  const patch = <T = unknown>(url: string, body?: unknown) => mutate<T>(url, { method: 'PATCH', body })
  const del = <T = unknown>(url: string) => mutate<T>(url, { method: 'DELETE' })

  return { mutate, post, put, patch, del }
}

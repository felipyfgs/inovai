declare module '#app' {
  interface NuxtApp {
    $sanctumClient: <T = unknown>(url: string, options?: Record<string, unknown>) => Promise<T>
  }
}

export {}

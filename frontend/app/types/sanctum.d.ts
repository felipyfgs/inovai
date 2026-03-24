declare module '#app' {
  interface NuxtApp {
    $sanctumClient: <T = any>(url: string, options?: any) => Promise<T>
  }
}

export {}

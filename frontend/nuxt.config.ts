// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  ssr: false,

  modules: [
    '@nuxt/eslint',
    '@nuxt/ui',
    '@vueuse/nuxt',
    'nuxt-auth-sanctum'
  ],

  devtools: {
    enabled: false
  },

  css: ['~/assets/css/main.css'],

  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE
    }
  },

  routeRules: {
    '/api/**': {
      cors: true
    }
  },

  vite: {
    optimizeDeps: {
      include: [
        '@tanstack/table-core',
        'zod',
        '@internationalized/date',
        'date-fns',
        'scule',
        '@unovis/ts',
        '@unovis/vue'
      ]
    }
  },

  compatibilityDate: '2024-07-11',

  eslint: {
    config: {
      stylistic: {
        commaDangle: 'never',
        braceStyle: '1tbs'
      }
    }
  },

  sanctum: {
    baseUrl: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000',
    endpoints: {
      csrf: '/sanctum/csrf-cookie',
      login: '/api/login',
      logout: '/api/logout',
      user: '/api/me'
    },
    csrf: {
      cookie: 'XSRF-TOKEN',
      header: 'X-XSRF-TOKEN'
    },
    client: {
      retry: false
    },
    redirect: {
      keepRequestedRoute: true,
      onLogin: '/',
      onLogout: '/login',
      onAuthOnly: '/login',
      onGuestOnly: '/'
    },
    globalMiddleware: {
      enabled: false
    }
  }
})

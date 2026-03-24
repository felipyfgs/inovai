import type { AuthUser } from '~/types'

export default defineNuxtRouteMiddleware(() => {
  const { user } = useSanctumAuth<AuthUser>()
  const isAdmin = user.value?.roles?.some(r => r.name === 'admin')

  if (!isAdmin) {
    return navigateTo('/')
  }
})

import type { AuthUser } from '~/types'

export default defineNuxtRouteMiddleware(() => {
  const { user } = useSanctumAuth<AuthUser>()

  if (!user.value) {
    return navigateTo('/login')
  }

  const isAdmin = user.value.roles?.some((r: { name: string }) => r.name === 'admin') ?? false

  if (!isAdmin) {
    return navigateTo('/')
  }
})

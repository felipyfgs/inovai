import type { AuthUser } from '~/types'

type Module = 'admin-dashboard' | 'admin-escritorios' | 'admin-planos' | 'admin-cobrancas' | 'admin-admins' | 'dashboard-office' | 'empresas' | 'usuarios' | 'inicio' | 'cadastros' | 'comercial' | 'fiscal' | 'estoque' | 'config'

type EffectiveRole = 'platform_admin' | 'accountant' | 'company_user'

export function useAccessContext() {
  const { user } = useSanctumAuth<AuthUser>()
  const { currentCompany } = useCurrentCompany()
  const { currentOffice } = useCurrentOffice()

  const isAdmin = computed(() => user.value?.roles?.some(r => r.name === 'admin') ?? false)
  const isAccountant = computed(() => user.value?.roles?.some(r => r.name === 'accountant' || r.name === 'office_user') ?? false)
  const isCompanyUser = computed(() => user.value?.roles?.some(r => r.name === 'company_user') ?? false)

  const isPlatformAdmin = computed(() => isAdmin.value && !currentOffice.value)
  const isOfficeAdmin = computed(() => (isAdmin.value || isAccountant.value) && currentOffice.value !== null && currentCompany.value === null)
  const isInCompanyContext = computed(() => (isAdmin.value || isAccountant.value) && currentCompany.value !== null)

  const effectiveRole = computed<EffectiveRole>(() => {
    if (isAdmin.value && !currentOffice.value) return 'platform_admin'
    if ((isAdmin.value || isAccountant.value) && currentOffice.value && !currentCompany.value) return 'accountant'
    if ((isAdmin.value || isAccountant.value || isCompanyUser.value) && currentCompany.value) return 'company_user'
    if (isAccountant.value) return 'accountant'
    return 'company_user'
  })

  const moduleMap: Record<EffectiveRole, Module[]> = {
    platform_admin: ['admin-dashboard', 'admin-escritorios', 'admin-planos', 'admin-cobrancas', 'admin-admins', 'config'],
    accountant: ['inicio', 'dashboard-office', 'empresas', 'usuarios', 'cadastros', 'comercial', 'fiscal', 'estoque', 'config'],
    company_user: ['inicio', 'cadastros', 'comercial', 'fiscal', 'estoque', 'config']
  }

  const modules = computed<Module[]>(() => moduleMap[effectiveRole.value])

  const canSee = (mod: Module): boolean => modules.value.includes(mod)

  return {
    isAdmin,
    isAccountant,
    isCompanyUser,
    isPlatformAdmin,
    isOfficeAdmin,
    isInCompanyContext,
    effectiveRole,
    modules,
    canSee
  }
}

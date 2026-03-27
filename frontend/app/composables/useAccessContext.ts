import type { AuthUser } from '~/types'

type Module = 'admin-dashboard' | 'admin-escritorios' | 'admin-planos' | 'admin-cobrancas' | 'admin-admins' | 'dashboard-office' | 'empresas' | 'usuarios' | 'inicio' | 'cadastros' | 'comercial' | 'fiscal' | 'financeiro' | 'estoque' | 'restaurante' | 'config'

type EffectiveRole = 'platform_admin' | 'accountant' | 'company_user'

const moduleToFeature: Partial<Record<Module, string>> = {
  financeiro: 'financeiro',
  estoque: 'estoque',
  restaurante: 'restaurante',
  comercial: 'orcamento'
}

const fiscalFeatures: Record<string, Module> = {
  nfe: 'fiscal',
  nfce: 'fiscal',
  cte: 'fiscal',
  mdfe: 'fiscal',
  nfse: 'fiscal'
}

export function useAccessContext() {
  const { user } = useSanctumAuth<AuthUser>()
  const { currentCompany } = useCurrentCompany()
  const { currentOffice } = useCurrentOffice()
  const { activeModules, loaded: modulesLoaded } = useCompanyModules()

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

  const roleModules: Record<EffectiveRole, Module[]> = {
    platform_admin: ['admin-dashboard', 'admin-escritorios', 'admin-planos', 'admin-cobrancas', 'admin-admins', 'config'],
    accountant: ['inicio', 'dashboard-office', 'empresas', 'usuarios', 'cadastros', 'comercial', 'fiscal', 'financeiro', 'estoque', 'restaurante', 'config'],
    company_user: ['inicio', 'cadastros', 'comercial', 'fiscal', 'financeiro', 'estoque', 'restaurante', 'config']
  }

  const modules = computed<Module[]>(() => {
    const roleBased = roleModules[effectiveRole.value]

    if (!currentCompany.value || !modulesLoaded.value) {
      return roleBased
    }

    return roleBased.filter((mod) => {
      const feature = moduleToFeature[mod]
      if (feature) {
        return activeModules.value.includes(feature)
      }

      if (mod === 'fiscal') {
        return Object.keys(fiscalFeatures).some(f => activeModules.value.includes(f))
      }

      return true
    })
  })

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

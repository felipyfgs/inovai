import type { AvatarProps } from '@nuxt/ui'

export type UserStatus = 'subscribed' | 'unsubscribed' | 'bounced'
export type SaleStatus = 'paid' | 'failed' | 'refunded'

export interface User {
  id: number
  name: string
  email: string
  avatar?: AvatarProps
  status: UserStatus
  location: string
}

export interface Mail {
  id: number
  unread?: boolean
  from: User
  subject: string
  body: string
  date: string
}

export interface Member {
  name: string
  username: string
  role: 'member' | 'owner'
  avatar: AvatarProps
}

export interface Stat {
  title: string
  icon: string
  value: number | string
  variation: number
  formatter?: (value: number) => string
}

export interface Sale {
  id: string
  date: string
  status: SaleStatus
  email: string
  amount: number
}

export interface Notification {
  id: number
  unread?: boolean
  sender: User
  body: string
  date: string
}

export type Period = 'daily' | 'weekly' | 'monthly'

export interface Range {
  start: Date
  end: Date
}

// ── Fiscal System Types ─────────────────────────────────────────────

export interface Office {
  id: number
  name: string
  cnpj: string | null
  phone: string | null
  email: string | null
  is_active: boolean
}

export interface AuthUser {
  id: number
  name: string
  email: string
  phone: string | null
  office_id: number | null
  is_active: boolean
  office?: Office
  companies?: Company[]
  roles?: { id: number, name: string }[]
}

export interface Company {
  id: number
  office_id: number
  razao_social: string
  fantasia: string | null
  cnpj: string
  ie: string | null
  im: string | null
  crt: number
  logradouro: string | null
  numero: string | null
  complemento: string | null
  bairro: string | null
  municipio: string | null
  municipio_ibge: string | null
  uf: string | null
  cep: string | null
  telefone: string | null
  email: string | null
  ambiente: 'homologacao' | 'producao'
  certificado_validade: string | null
  serie_nfe: number
  proximo_numero_nfe: number
  serie_nfce: number
  proximo_numero_nfce: number
  is_active: boolean
}

export interface Pessoa {
  id: number
  company_id: number
  tipo: 'cliente' | 'fornecedor' | 'ambos'
  tipo_pessoa: 'PF' | 'PJ'
  razao_social: string
  fantasia: string | null
  cpf_cnpj: string | null
  ie: string | null
  ind_ie: number
  logradouro: string | null
  numero: string | null
  complemento: string | null
  bairro: string | null
  municipio: string | null
  municipio_ibge: string | null
  uf: string | null
  cep: string | null
  telefone: string | null
  celular: string | null
  email: string | null
  observacoes: string | null
  is_active: boolean
}

export interface Produto {
  id: number
  company_id: number
  codigo: string | null
  codigo_barras: string | null
  descricao: string
  ncm: string | null
  cest: string | null
  cfop: string | null
  unidade: string
  preco_venda: number
  preco_custo: number
  origem: number
  is_active: boolean
}

export interface Transportadora {
  id: number
  company_id: number
  razao_social: string
  fantasia: string | null
  cnpj: string | null
  ie: string | null
  rntrc: string | null
  is_active: boolean
}

export interface Orcamento {
  id: number
  company_id: number
  pessoa_id: number | null
  numero: number
  data: string
  validade: string | null
  status: 'rascunho' | 'enviado' | 'aprovado' | 'recusado' | 'convertido'
  observacoes: string | null
  desconto: number
  valor_total: number
  pessoa?: Pessoa
}

export interface Pedido {
  id: number
  company_id: number
  pessoa_id: number | null
  orcamento_id: number | null
  numero: number
  data: string
  status: 'pendente' | 'confirmado' | 'faturado' | 'cancelado'
  observacoes: string | null
  desconto: number
  valor_total: number
  pessoa?: Pessoa
}

export interface NotaFiscal {
  id: number
  company_id: number
  pessoa_id: number | null
  modelo: 55 | 65
  serie: number
  numero: number
  chave: string | null
  natureza_operacao: string
  data_emissao: string
  status: string
  ambiente: number
  valor_total: number
  protocolo: string | null
  pessoa?: Pessoa
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

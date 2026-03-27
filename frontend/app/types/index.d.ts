import type { AvatarProps } from '@nuxt/ui'

export type UserStatus = 'subscribed' | 'unsubscribed' | 'bounced'
export type SaleStatus = 'paid' | 'failed' | 'refunded'

export interface Mail {
  id: number
  unread?: boolean
  from: { name: string, email: string, avatar?: AvatarProps }
  subject: string
  body: string
  date: string
}

export interface AppUser {
  id: number
  name: string
  email: string
  phone?: string | null
  office_id?: number | null
  is_active?: boolean
  office?: Office
  companies?: Company[]
  roles?: { id: number, name: string }[]
}

export interface AdminUser {
  id: number
  name: string
  email: string
  phone?: string | null
  office_id: null
  is_active: boolean
  must_change_password: boolean
  roles?: { id: number, name: string }[]
  created_at: string
  updated_at: string
}

export type User = AppUser

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
  logradouro: string | null
  numero: string | null
  complemento: string | null
  bairro: string | null
  municipio: string | null
  municipio_ibge: string | null
  uf: string | null
  cep: string | null
  ie: string | null
  is_active: boolean
  type: 'admin' | 'contador' | 'direct'
  is_reseller: boolean
  reseller_commission: number
  parent_office_id: number | null
  notes: string | null
  companies_count?: number
  users_count?: number
  subscription?: Subscription
  companies?: Company[]
  invoices?: Invoice[]
  users?: User[]
}

export interface Plan {
  id: number
  name: string
  description: string | null
  price: number
  max_companies: number
  max_nfs_month: number
  features: string[] | null
  is_active: boolean
  grace_period_days: number
  max_overdue_days: number
}

export interface Subscription {
  id: number
  office_id: number
  plan_id: number
  status: 'active' | 'cancelled' | 'expired' | 'trial'
  starts_at: string
  ends_at: string | null
  plan?: Plan
}

export interface InvoiceItem {
  id: number
  invoice_id: number
  description: string
  quantity: number
  unit_price: number
  total: number
}

export interface Invoice {
  id: number
  office_id: number
  plan_id: number | null
  status: 'pending' | 'paid' | 'cancelled' | 'overdue'
  amount: number
  reference: string | null
  notes: string | null
  due_at: string
  paid_at: string | null
  office?: Office
  plan?: Plan
  items?: InvoiceItem[]
}

export interface AdminDashboard {
  revenue: number
  previous_revenue: number
  revenue_variation: number
  pending: number
  overdue: number
  total_offices: number
  inadimplentes: number
  churn_rate: number
}

export interface AdminMap {
  contadores: Office[]
  diretas: Office[]
  totals: {
    contadores: number
    direct: number
    companies: number
  }
}

export interface AuthUser {
  id: number
  name: string
  email: string
  phone: string | null
  office_id: number | null
  is_active: boolean
  must_change_password: boolean
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
  pedido_id: number | null
  transportadora_id: number | null
  modelo: 55 | 65
  serie: number
  numero: number
  chave: string | null
  natureza_operacao: string
  tipo_operacao: string
  finalidade: string
  data_emissao: string | null
  data_saida: string | null
  status: string
  ambiente: number
  valor_produtos: number
  valor_frete: number
  valor_seguro: number
  valor_desconto: number
  valor_outras: number
  valor_icms: number
  valor_icms_st: number
  valor_ipi: number
  valor_pis: number
  valor_cofins: number
  valor_total: number
  frete_por: number | null
  protocolo: string | null
  recibo: string | null
  motivo: string | null
  informacoes_adicionais: string | null
  pessoa?: Pessoa
  transportadora?: Transportadora
  itens?: NotaFiscalItem[]
  itens_count?: number
  eventos?: NotaFiscalEvento[]
  eventos_count?: number
  created_at: string
  updated_at: string
}

export interface NotaFiscalItem {
  id: number
  nota_fiscal_id: number
  produto_id: number | null
  numero_item: number
  codigo: string
  descricao: string
  ncm: string | null
  cest: string | null
  cfop: string
  unidade: string
  quantidade: number
  valor_unitario: number
  valor_total: number
  valor_desconto: number
  valor_frete: number
  valor_seguro: number
  valor_outras: number
  origem: number
  cst_icms: string
  csosn: string | null
  bc_icms: number
  aliq_icms: number
  valor_icms: number
  bc_icms_st: number
  aliq_icms_st: number
  valor_icms_st: number
  cst_ipi: string
  bc_ipi: number
  aliq_ipi: number
  valor_ipi: number
  cst_pis: string
  bc_pis: number
  aliq_pis: number
  valor_pis: number
  cst_cofins: string
  bc_cofins: number
  aliq_cofins: number
  valor_cofins: number
  produto?: Produto
}

export interface NotaFiscalEvento {
  id: number
  nota_fiscal_id: number
  tipo: string
  sequencia: number
  protocolo: string | null
  justificativa: string | null
  correcao: string | null
  status: string
  created_at: string
}

export type CteStatus = 'rascunho' | 'assinada' | 'transmitida' | 'autorizada' | 'rejeitada' | 'cancelada'
export type CteModal = 1 | 2 | 3 | 4 | 5

export interface Cte {
  id: number
  company_id: number
  remetente_id: number | null
  destinatario_id: number | null
  expedidor_id: number | null
  recebedor_id: number | null
  tomador_id: number | null
  tomador_tipo: number | null
  modelo: number
  serie: number
  numero: number
  chave: string | null
  cfop: string | null
  natureza_operacao: string | null
  modal: CteModal | null
  data_emissao: string | null
  status: CteStatus
  ambiente: number
  valor_servico: number
  valor_receber: number
  valor_icms: number
  valor_total: number
  uf_inicio: string | null
  uf_fim: string | null
  protocolo: string | null
  motivo: string | null
  informacoes_adicionais: string | null
  remetente?: Pessoa
  destinatario?: Pessoa
  expedidor?: Pessoa
  recebedor?: Pessoa
  tomador?: Pessoa
  nfes?: CteNfe[]
  eventos_count?: number
  created_at: string
  updated_at: string
}

export interface CteNfe {
  id: number
  cte_id: number
  chave_nfe: string
}

export interface CteEvento {
  id: number
  cte_id: number
  tipo: string
  sequencia: number
  protocolo: string | null
  justificativa: string | null
  correcao: string | null
  status: string
  created_at: string
}

export type MdfeStatus = 'rascunho' | 'assinada' | 'transmitida' | 'autorizada' | 'rejeitada' | 'cancelada' | 'encerrada'

export interface Mdfe {
  id: number
  company_id: number
  veiculo_id: number | null
  motorista_id: number | null
  modelo: number
  serie: number
  numero: number
  chave: string | null
  modal: number
  data_emissao: string | null
  status: MdfeStatus
  ambiente: number
  uf_carregamento: string
  uf_descarregamento: string
  veiculo_placa: string | null
  motorista_cpf: string | null
  motorista_nome: string | null
  valor_carga: number
  peso_bruto: number
  uf_percurso: string[] | null
  protocolo: string | null
  motivo: string | null
  informacoes_adicionais: string | null
  documentos?: MdfeDocumento[]
  eventos_count?: number
  created_at: string
  updated_at: string
}

export interface MdfeDocumento {
  id: number
  mdfe_id: number
  tipo: 'nfe' | 'cte'
  chave: string
}

export interface MdfeEvento {
  id: number
  mdfe_id: number
  tipo: string
  sequencia: number
  protocolo: string | null
  justificativa: string | null
  status: string
  created_at: string
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

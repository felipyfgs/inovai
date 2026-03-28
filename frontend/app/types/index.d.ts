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
  pais: string | null
  pais_ibge: string | null
  telefone: string | null
  email: string | null
  ambiente: 'homologacao' | 'producao'
  certificado_validade: string | null
  tem_certificado?: boolean
  serie_nfe: number
  proximo_numero_nfe: number
  serie_nfce: number
  proximo_numero_nfce: number
  serie_cte: number
  proximo_numero_cte: number
  serie_mdfe: number
  proximo_numero_mdfe: number
  csc_id: string | null
  csc_token: string | null
  is_active: boolean
  office_plan_id?: number | null
  modules?: CompanyModule[]
  subscription?: CompanySubscription | null
  office_plan?: OfficePlan | null
}

export interface CompanyModule {
  id: number
  company_id: number
  module: string
  is_active: boolean
}

export interface OfficePlan {
  id: number
  office_id: number
  name: string
  description: string | null
  price: number
  max_nfs_month: number | null
  modules: string[]
  is_active: boolean
  is_default: boolean
}

export interface CompanySubscription {
  id: number
  company_id: number
  office_plan_id: number
  status: 'active' | 'cancelled' | 'expired'
  starts_at: string
  ends_at: string | null
  office_plan?: OfficePlan
}

export interface CompanyInvoiceItem {
  id: number
  company_invoice_id: number
  description: string
  quantity: number
  unit_price: number
  total: number
}

export interface CompanyInvoice {
  id: number
  company_id: number
  office_plan_id: number | null
  status: 'pending' | 'paid' | 'cancelled' | 'overdue'
  amount: number
  reference: string | null
  notes: string | null
  due_at: string
  paid_at: string | null
  company?: Company
  office_plan?: OfficePlan
  items?: CompanyInvoiceItem[]
  created_at?: string
  updated_at?: string
}

export interface AvailableModule {
  id: string
  label: string
  is_active: boolean
  allowed_by_plan: boolean
}

export interface EmitenteCertificado {
  tem_certificado: boolean
  validade: string | null
  info?: {
    cnpj: string
    razao_social: string
    valido_de: string
    valido_ate: string
    expirado: boolean
    erro?: string
  }
}

export interface Estoque {
  id: number
  company_id: number
  produto_id: number
  quantidade: number
  custo_medio: number
  localizacao: string | null
  produto?: Produto
}

export interface EstoqueMovimentacao {
  id: number
  estoque_id: number
  user_id: number
  tipo: 'entrada' | 'saida' | 'ajuste'
  quantidade: number
  custo_unitario: number
  documento_tipo: string | null
  documento_id: number | null
  observacoes: string | null
  data: string
  estoque?: Estoque
  user?: AppUser
}

export interface EstoqueResumo {
  total_produtos: number
  com_estoque: number
  sem_estoque: number
  estoque_baixo: number
  valor_total_estoque: number
}

export interface Conta {
  id: number
  company_id: number
  tipo: 'pagar' | 'receber'
  pessoa_id: number | null
  descricao: string
  documento: string | null
  pedido_id: number | null
  categoria: string | null
  data_emissao: string
  data_vencimento: string
  data_baixa: string | null
  valor_original: number
  valor_desconto: number
  valor_juros: number
  valor_multa: number
  valor_baixado: number
  status: 'pendente' | 'pago_parcial' | 'pago' | 'vencido' | 'cancelado'
  observacoes: string | null
  parcelas_count?: number
  pessoa?: Pessoa
  parcelas?: ContaParcela[]
  created_at: string
  updated_at: string
}

export interface ContaParcela {
  id: number
  conta_id: number
  numero: number
  data_vencimento: string
  data_baixa: string | null
  valor: number
  valor_desconto: number
  valor_juros: number
  valor_multa: number
  valor_baixado: number
  forma_pagamento: string | null
  observacoes: string | null
  status: 'pendente' | 'pago_parcial' | 'pago'
}

export interface ContaMovimentacao {
  id: number
  conta_id: number
  parcela_id: number | null
  user_id: number
  tipo: 'entrada' | 'saida'
  valor: number
  forma_pagamento: string | null
  observacoes: string | null
  data: string
}

export interface FinanceiroResumo {
  total_a_pagar: number
  total_a_receber: number
  total_vencido: number
  total_a_vencer_30: number
  saldo_previsto: number
}

export interface Fornecedor extends Pessoa {
  condicao_pagamento: string | null
  prazo_entrega: number | null
  avaliacao: number | null
}

export type NfseStatus = 'rascunho' | 'assinada' | 'transmitida' | 'autorizada' | 'rejeitada' | 'cancelada'

export interface Nfse {
  id: number
  company_id: number
  pessoa_id: number | null
  serie: string
  numero: number
  chave: string | null
  codigo_verificacao: string | null
  data_emissao: string
  data_competencia: string
  status: NfseStatus
  ambiente: number
  natureza_operacao: string
  codigo_servico: string
  descricao_servico: string
  valor_servico: number
  valor_deducoes: number
  valor_desconto: number
  valor_ir: number
  valor_inss: number
  valor_pis: number
  valor_cofins: number
  valor_csll: number
  valor_outras: number
  valor_total: number
  cnae: string | null
  cidade_ibge: string
  cidade: string
  uf: string
  tomador_nome: string | null
  tomador_cpf_cnpj: string | null
  tomador_logradouro: string | null
  tomador_numero: string | null
  tomador_bairro: string | null
  tomador_cep: string | null
  tomador_municipio: string | null
  tomador_uf: string | null
  tomador_email: string | null
  tomador_telefone: string | null
  protocolo: string | null
  motivo: string | null
  informacoes_adicionais: string | null
  pessoa?: Pessoa
  itens?: NfseItem[]
  created_at: string
  updated_at: string
}

export interface NfseItem {
  id: number
  nfse_id: number
  numero_item: number
  discriminacao: string
  quantidade: number
  unidade: string | null
  valor_unitario: number
  valor_total: number
}

export interface NfseEvento {
  id: number
  nfse_id: number
  tipo: string
  sequencia: number
  protocolo: string | null
  justificativa: string | null
  status: string | null
  created_at: string
}

export interface RestauranteMesa {
  id: number
  company_id: number
  nome: string
  capacidade: number
  status: 'livre' | 'ocupada' | 'reservada' | 'inativa'
  localizacao: string | null
}

export interface RestauranteCardapioGrupo {
  id: number
  company_id: number
  nome: string
  ordem: number
  is_active: boolean
  itens?: RestauranteCardapioItem[]
}

export interface RestauranteCardapioItem {
  id: number
  company_id: number
  grupo_id: number
  nome: string
  descricao: string | null
  preco: number
  imagem_url: string | null
  is_active: boolean
  disponivel: boolean
  codigo: string | null
}

export interface RestauranteComanda {
  id: number
  company_id: number
  mesa_id: number | null
  garcom_id: number | null
  codigo: string
  status: 'aberta' | 'fechada' | 'cancelada'
  valor_total: number
  desconto: number
  opened_at: string
  closed_at: string | null
  pessoas: number
  mesa?: RestauranteMesa
  garcom?: AppUser
  itens?: RestauranteComandaItem[]
}

export interface RestauranteComandaItem {
  id: number
  comanda_id: number
  item_id: number
  quantidade: number
  preco_unitario: number
  valor_total: number
  observacoes: string | null
  status: 'pendente' | 'preparando' | 'pronto' | 'entregue' | 'cancelado'
  created_at: string
  item?: RestauranteCardapioItem
}

export interface RestauranteResumo {
  mesas_livres: number
  mesas_ocupadas: number
  comandas_abertas: number
  receita_hoje: number
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

export interface OrcamentoItem {
  id?: number
  produto_id: number | null
  descricao: string
  quantidade: number
  valor_unitario: number
  desconto: number
  valor_total?: number
  produto?: Produto
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
  itens?: OrcamentoItem[]
  created_at?: string
  updated_at?: string
}

export interface PedidoItem {
  id?: number
  produto_id: number | null
  descricao: string
  quantidade: number
  valor_unitario: number
  desconto: number
  valor_total?: number
  produto?: Produto
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
  orcamento?: Orcamento
  itens?: PedidoItem[]
  created_at?: string
  updated_at?: string
}

export interface Nfe {
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
  itens?: NfeItem[]
  itens_count?: number
  eventos?: NfeEvento[]
  eventos_count?: number
  created_at: string
  updated_at: string
}

export interface NfeItem {
  id: number
  nfe_id: number
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

export interface NfeEvento {
  id: number
  nfe_id: number
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

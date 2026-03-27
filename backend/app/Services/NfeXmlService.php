<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Nfe;
use App\Models\NfeItem;
use Illuminate\Support\Facades\Config;
use NFePHP\NFe\Make;

class NfeXmlService
{
    public function make(Nfe $nfe): string
    {
        $nfe->loadMissing([
            'company',
            'pessoa',
            'transportadora',
            'itens.produto',
        ]);

        $company = $nfe->company;
        $pessoa = $nfe->pessoa;
        $isNfce = $nfe->isNfce();
        $versao = Config::get('fiscal.nfe.versao', '4.00');

        $maker = new Make;
        $std = new \stdClass;
        $std->versao = $versao;
        $std->Id = null;
        $std->pk_nItem = '';
        $maker->taginfNFe($std);

        $this->tagIde($maker, $nfe, $company, $isNfce);
        $this->tagEmit($maker, $company);
        $this->tagDest($maker, $pessoa, $nfe);

        foreach ($nfe->itens as $index => $item) {
            $this->tagDet($maker, $item, $index + 1);
        }

        $this->tagTotal($maker, $nfe);
        $this->tagTransp($maker, $nfe);
        $this->tagPag($maker, $nfe);

        if ($nfe->informacoes_adicionais) {
            $std = new \stdClass;
            $std->infAdFisco = $nfe->informacoes_fisco;
            $std->infCpl = $nfe->informacoes_adicionais;
            $maker->taginfAdic($std);
        }

        return $maker->getXML();
    }

    private function tagIde(Make $nfe, Nfe $nfeRef, Company $company, bool $isNfce): void
    {
        $cUF = Config::get("fiscal.uf_cuf.{$company->uf}", 35);

        $std = new \stdClass;
        $std->cUF = $cUF;
        $std->cNF = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $std->natOp = $nfeRef->natureza_operacao;
        $std->mod = $nfeRef->modelo;
        $std->serie = $nfeRef->serie;
        $std->nNF = $nfeRef->numero;
        $std->dhEmi = $nfeRef->data_emissao->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format('Y-m-d\TH:i:sP');
        $std->dhSaiEnt = $nfeRef->data_saida
            ? $nfeRef->data_saida->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format('Y-m-d\TH:i:sP')
            : $std->dhEmi;
        $std->tpNF = $nfeRef->tipo_operacao;
        $std->idDest = $this->getIdDest($company, $nfeRef->pessoa);
        $std->cMunFG = (int) $company->municipio_ibge;
        $std->tpImp = $isNfce ? 4 : 1;
        $std->tpEmis = $nfeRef->ambiente === 1 ? 1 : 2;
        $std->cDV = $this->extractDV($nfeRef->chave);
        $std->tpAmb = $nfeRef->ambiente;
        $std->finNFe = $nfeRef->finalidade;
        $std->indFinal = $this->getIndFinal($nfeRef);
        $std->indPres = $this->getIndPres($nfeRef);
        $std->procEmi = '0';
        $std->verProc = '1.00';

        $nfe->tagide($std);
    }

    private function tagEmit(Make $nfe, Company $company): void
    {
        $std = new \stdClass;
        $std->xNome = $company->razao_social;
        $std->xFant = $company->fantasia;
        $std->IE = $company->ie;
        $std->IM = $company->im;
        $std->CRT = $company->crt ?? 3;
        $std->CNPJ = preg_replace('/\D/', '', $company->cnpj);
        $nfe->tagemit($std);

        $std = new \stdClass;
        $std->xLgr = $company->logradouro;
        $std->nro = $company->numero;
        $std->xCpl = $company->complemento;
        $std->xBairro = $company->bairro;
        $std->cMun = (int) $company->municipio_ibge;
        $std->xMun = $company->municipio;
        $std->UF = $company->uf;
        $std->CEP = preg_replace('/\D/', '', $company->cep);
        $std->cPais = $company->pais_ibge ?? '1058';
        $std->xPais = $company->pais ?? 'BRASIL';
        $std->fone = preg_replace('/\D/', '', $company->telefone ?? '');
        $nfe->tagenderEmit($std);
    }

    private function tagDest(Make $nfe, $pessoa, Nfe $nfeRef): void
    {
        if (! $pessoa) {
            return;
        }

        $std = new \stdClass;
        $std->xNome = mb_substr($pessoa->razao_social, 0, 60);
        $std->indIEDest = $this->getIndIEDest($pessoa, $nfeRef);
        $std->IE = $pessoa->ie;
        $std->ISUF = null;
        $std->IM = $pessoa->im;
        $std->email = $pessoa->email;

        if ($pessoa->tipo_pessoa === 'PJ') {
            $std->CNPJ = preg_replace('/\D/', '', $pessoa->cpf_cnpj);
        } else {
            $std->CPF = preg_replace('/\D/', '', $pessoa->cpf_cnpj);
        }
        $nfe->tagdest($std);

        $std = new \stdClass;
        $std->xLgr = $pessoa->logradouro;
        $std->nro = $pessoa->numero;
        $std->xCpl = $pessoa->complemento;
        $std->xBairro = $pessoa->bairro;
        $std->cMun = $pessoa->municipio_ibge ? (int) $pessoa->municipio_ibge : null;
        $std->xMun = $pessoa->municipio;
        $std->UF = $pessoa->uf;
        $std->CEP = preg_replace('/\D/', '', $pessoa->cep ?? '');
        $std->cPais = $pessoa->pais_ibge ?? '1058';
        $std->xPais = $pessoa->pais ?? 'BRASIL';
        $std->fone = preg_replace('/\D/', '', $pessoa->celular ?? $pessoa->telefone ?? '');
        $nfe->tagenderDest($std);
    }

    private function tagDet(Make $maker, NfeItem $item, int $numeroItem): void
    {
        $std = new \stdClass;
        $std->item = $numeroItem;
        $std->cEAN = '';
        $std->cEANTrib = '';
        $std->cProd = $item->codigo ?? $item->produto_id ?? '';
        $std->xProd = mb_substr($item->descricao, 0, 120);
        $std->NCM = $item->ncm;
        $std->CEST = $item->cest;
        $std->CFOP = $item->cfop;
        $std->uCom = $item->unidade;
        $std->qCom = (float) $item->quantidade;
        $std->vUnCom = (float) $item->valor_unitario;
        $std->vProd = (float) $item->valor_total;
        $std->cEANTrib = '';
        $std->uTrib = $item->unidade;
        $std->qTrib = (float) $item->quantidade;
        $std->vUnTrib = (float) $item->valor_unitario;
        $std->vDesc = (float) ($item->valor_desconto ?? 0);
        $std->vFrete = (float) ($item->valor_frete ?? 0);
        $std->vSeg = (float) ($item->valor_seguro ?? 0);
        $std->vOutro = (float) ($item->valor_outras ?? 0);
        $std->indTot = 1;
        $std->nItemPed = null;
        $std->xPed = null;
        $maker->tagprod($std);

        $std = new \stdClass;
        $std->item = $numeroItem;
        $std->vTotTrib = 0;
        $maker->tagimposto($std);

        $this->tagIcms($maker, $item, $numeroItem);
        $this->tagIpi($maker, $item, $numeroItem);
        $this->tagPis($maker, $item, $numeroItem);
        $this->tagCofins($maker, $item, $numeroItem);
    }

    private function tagIcms(Make $maker, NfeItem $item, int $numeroItem): void
    {
        $origem = $item->origem ?? 0;

        if ($item->csosn) {
            $std = new \stdClass;
            $std->item = $numeroItem;
            $std->orig = $origem;
            $std->CSOSN = $item->csosn;
            $std->pCredSN = 0;
            $std->vCredICMSSN = 0;
            $maker->tagICMSSN($std);
        } elseif ($item->cst_icms) {
            $cst = $item->cst_icms;

            $std = new \stdClass;
            $std->item = $numeroItem;
            $std->orig = $origem;
            $std->CST = $cst;
            $std->modBC = 0;
            $std->vBC = (float) ($item->bc_icms ?? 0);
            $std->pICMS = (float) ($item->aliq_icms ?? 0);
            $std->vICMS = (float) ($item->valor_icms ?? 0);
            $std->pRedBC = null;
            $std->vICMSDeson = null;
            $std->motDesICMS = null;
            $std->modBCST = null;
            $std->pMVAST = null;
            $std->vBCST = (float) ($item->bc_icms_st ?? 0);
            $std->pICMSST = (float) ($item->aliq_icms_st ?? 0);
            $std->vICMSST = (float) ($item->valor_icms_st ?? 0);
            $std->vBCFCPST = null;
            $std->pFCPST = null;
            $std->vFCPST = null;
            $maker->tagICMS($std);
        }
    }

    private function tagIpi(Make $maker, NfeItem $item, int $numeroItem): void
    {
        if (! $item->cst_ipi) {
            return;
        }

        $std = new \stdClass;
        $std->item = $numeroItem;
        $std->cEnq = '999';
        $std->CST = $item->cst_ipi;
        $std->vBC = (float) ($item->bc_ipi ?? 0);
        $std->pIPI = (float) ($item->aliq_ipi ?? 0);
        $std->vIPI = (float) ($item->valor_ipi ?? 0);
        $std->qUnid = null;
        $std->vUnid = null;
        $maker->tagIPI($std);
    }

    private function tagPis(Make $maker, NfeItem $item, int $numeroItem): void
    {
        if (! $item->cst_pis) {
            return;
        }

        $std = new \stdClass;
        $std->item = $numeroItem;
        $std->CST = $item->cst_pis;
        $std->vBC = (float) ($item->bc_pis ?? 0);
        $std->pPIS = (float) ($item->aliq_pis ?? 0);
        $std->vPIS = (float) ($item->valor_pis ?? 0);
        $std->qBCProd = null;
        $std->vAliqProd = null;
        $maker->tagPIS($std);
    }

    private function tagCofins(Make $maker, NfeItem $item, int $numeroItem): void
    {
        if (! $item->cst_cofins) {
            return;
        }

        $std = new \stdClass;
        $std->item = $numeroItem;
        $std->CST = $item->cst_cofins;
        $std->vBC = (float) ($item->bc_cofins ?? 0);
        $std->pCOFINS = (float) ($item->aliq_cofins ?? 0);
        $std->vCOFINS = (float) ($item->valor_cofins ?? 0);
        $std->qBCProd = null;
        $std->vAliqProd = null;
        $maker->tagCOFINS($std);
    }

    private function tagTotal(Make $maker, Nfe $nfe): void
    {
        $std = new \stdClass;
        $std->vBC = (float) $nfe->valor_icms;
        $std->vICMS = (float) $nfe->valor_icms;
        $std->vICMSDeson = 0.00;
        $std->vFCP = 0.00;
        $std->vBCST = (float) $nfe->valor_icms_st;
        $std->vST = (float) $nfe->valor_icms_st;
        $std->vFCPST = 0.00;
        $std->vFCPSTRet = 0.00;
        $std->vProd = (float) $nfe->valor_produtos;
        $std->vFrete = (float) $nfe->valor_frete;
        $std->vSeg = (float) $nfe->valor_seguro;
        $std->vDesc = (float) $nfe->valor_desconto;
        $std->vII = 0.00;
        $std->vIPI = (float) $nfe->valor_ipi;
        $std->vIPIDevol = 0.00;
        $std->vPIS = (float) $nfe->valor_pis;
        $std->vCOFINS = (float) $nfe->valor_cofins;
        $std->vOutro = (float) $nfe->valor_outras;
        $std->vNF = (float) $nfe->valor_total;
        $std->vTotTrib = 0.00;
        $maker->tagICMSTot($std);
    }

    private function tagTransp(Make $maker, Nfe $nfe): void
    {
        $std = new \stdClass;
        $std->modFrete = $nfe->frete_por ?? 9;
        $maker->tagtransp($std);

        if ($nfe->transportadora) {
            $t = $nfe->transportadora;
            $std = new \stdClass;
            $std->xNome = mb_substr($t->razao_social, 0, 60);
            $std->CNPJ = preg_replace('/\D/', '', $t->cnpj);
            $std->IE = $t->ie;
            $std->xEnder = "{$t->logradouro}, {$t->numero}";
            $std->xMun = $t->municipio;
            $std->UF = $t->uf;
            $maker->tagtransporta($std);

            if ($nfe->transportadora->veiculos->isNotEmpty()) {
                $veiculo = $nfe->transportadora->veiculos->first();
                $std = new \stdClass;
                $std->placa = $veiculo->placa ?? '';
                $std->UF = $veiculo->uf ?? '';
                $std->RNTC = '';
                $maker->tagveicTransp($std);
            }
        }
    }

    private function tagPag(Make $maker, Nfe $nfe): void
    {
        $std = new \stdClass;
        $std->vTroco = 0;
        $maker->tagpag($std);

        $std = new \stdClass;
        $std->indPag = 0;
        $std->tPag = '01';
        $std->vPag = (float) $nfe->valor_total;
        $std->indPag = 0;
        $maker->tagdetPag($std);
    }

    private function getIdDest(Company $company, $pessoa): int
    {
        if (! $pessoa) {
            return 1;
        }

        if ($pessoa->uf === $company->uf) {
            return 1;
        }

        return 2;
    }

    private function getIndFinal(Nfe $nfe): int
    {
        return $nfe->isNfce() ? 1 : 0;
    }

    private function getIndPres(Nfe $nfe): int
    {
        if ($nfe->isNfce()) {
            return 1;
        }

        return 0;
    }

    private function getIndIEDest($pessoa, Nfe $nfe): int
    {
        if ($nfe->tipo_operacao === 0) {
            return 2;
        }

        if ($pessoa->tipo_pessoa === 'PF') {
            return 9;
        }

        return empty($pessoa->ie) ? 2 : ($pessoa->uf === $nfe->company->uf ? 1 : 2);
    }

    private function extractDV(?string $chave): int
    {
        if (empty($chave) || strlen($chave) !== 44) {
            return 0;
        }

        return (int) substr($chave, -1);
    }
}

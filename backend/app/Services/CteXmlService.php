<?php

namespace App\Services;

use App\Models\Cte;
use Illuminate\Support\Facades\Config;
use NFePHP\CTe\MakeCTe;

class CteXmlService
{
    public function make(Cte $cte): string
    {
        $cte->loadMissing([
            'company',
            'remetente',
            'destinatario',
            'expedidor',
            'recebedor',
            'tomador',
            'nfes',
        ]);

        $company = $cte->company;
        $versao = Config::get('fiscal.cte.versao', '4.00');

        $make = new MakeCTe;
        $std = new \stdClass;
        $std->versao = $versao;
        $std->Id = null;
        $std->pk_nItem = '';
        $make->taginfCTe($std);

        $this->tagIde($make, $cte, $company);
        $this->tagEmit($make, $company);
        $this->tagRem($make, $cte);
        $this->tagDest($make, $cte);
        $this->tagExped($make, $cte);
        $this->tagReceb($make, $cte);
        $this->tagToma($make, $cte);
        $this->tagServico($make, $cte);
        $this->tagInfNFes($make, $cte);
        $this->tagTotal($make, $cte);

        if ($cte->informacoes_adicionais) {
            $std = new \stdClass;
            $std->infCpl = $cte->informacoes_adicionais;
            $make->tagcompl($std);
        }

        return $make->getXML();
    }

    private function tagIde(MakeCTe $make, Cte $cte, $company): void
    {
        $cUF = Config::get("fiscal.uf_cuf.{$company->uf}", 35);

        $std = new \stdClass;
        $std->cUF = $cUF;
        $std->cCT = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $std->CFOP = $cte->cfop;
        $std->natOp = $cte->natureza_operacao;
        $std->mod = $cte->modelo;
        $std->serie = $cte->serie;
        $std->nCT = $cte->numero;
        $std->dhEmi = $cte->data_emissao->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format('Y-m-d\TH:i:sP');
        $std->tpImp = 1;
        $std->tpEmis = $cte->ambiente === 1 ? 1 : 2;
        $std->cDV = $this->extractDV($cte->chave);
        $std->tpAmb = $cte->ambiente;
        $std->tpCTe = 0;
        $std->procEmi = '0';
        $std->verProc = '1.00';
        $std->cMunEnv = (int) ($company->municipio_ibge ?? 0);
        $std->xMunEnv = $company->municipio ?? '';
        $std->UFEnv = $company->uf ?? '';
        $std->modal = $cte->modal;
        $std->tpServ = 0;
        $std->cMunIni = (int) ($cte->municipio_inicio_ibge ?? 0);
        $std->xMunIni = $cte->municipio_inicio ?? '';
        $std->UFIni = $cte->uf_inicio ?? '';
        $std->cMunFim = (int) ($cte->municipio_fim_ibge ?? 0);
        $std->xMunFim = $cte->municipio_fim ?? '';
        $std->UFFim = $cte->uf_fim ?? '';
        $std->retira = 1;
        $std->indIEToma = 0;
        $make->tagide($std);
    }

    private function tagEmit(MakeCTe $make, $company): void
    {
        $std = new \stdClass;
        $std->CNPJ = preg_replace('/\D/', '', $company->cnpj);
        $std->IE = $company->ie;
        $std->xNome = mb_substr($company->razao_social, 0, 60);
        $std->xFant = mb_substr($company->fantasia ?? '', 0, 60);
        $make->tagemit($std);

        $std = new \stdClass;
        $std->xLgr = $company->logradouro;
        $std->nro = $company->numero;
        $std->xCpl = $company->complemento;
        $std->xBairro = $company->bairro;
        $std->cMun = (int) ($company->municipio_ibge ?? 0);
        $std->xMun = $company->municipio;
        $std->CEP = preg_replace('/\D/', '', $company->cep ?? '');
        $std->UF = $company->uf;
        $std->fone = preg_replace('/\D/', '', $company->telefone ?? '');
        $make->tagenderEmit($std);
    }

    private function tagRem(MakeCTe $make, Cte $cte): void
    {
        if (! $cte->remetente) {
            return;
        }

        $p = $cte->remetente;
        $std = new \stdClass;
        $std->CNPJ = $p->tipo_pessoa === 'PJ' ? preg_replace('/\D/', '', $p->cpf_cnpj) : null;
        $std->CPF = $p->tipo_pessoa === 'PF' ? preg_replace('/\D/', '', $p->cpf_cnpj) : null;
        $std->IE = $p->ie;
        $std->xNome = mb_substr($p->razao_social, 0, 60);
        $std->fone = preg_replace('/\D/', '', $p->celular ?? $p->telefone ?? '');
        $std->enderR = null;
        $make->tagrem($std);

        $std = new \stdClass;
        $std->xLgr = $p->logradouro;
        $std->nro = $p->numero;
        $std->xCpl = $p->complemento;
        $std->xBairro = $p->bairro;
        $std->cMun = $p->municipio_ibge ? (int) $p->municipio_ibge : 0;
        $std->xMun = $p->municipio;
        $std->CEP = preg_replace('/\D/', '', $p->cep ?? '');
        $std->UF = $p->uf;
        $std->cPais = $p->pais_ibge ?? '1058';
        $make->tagenderReme($std);
    }

    private function tagDest(MakeCTe $make, Cte $cte): void
    {
        if (! $cte->destinatario) {
            return;
        }

        $p = $cte->destinatario;
        $std = new \stdClass;
        $std->CNPJ = $p->tipo_pessoa === 'PJ' ? preg_replace('/\D/', '', $p->cpf_cnpj) : null;
        $std->CPF = $p->tipo_pessoa === 'PF' ? preg_replace('/\D/', '', $p->cpf_cnpj) : null;
        $std->IE = $p->ie;
        $std->xNome = mb_substr($p->razao_social, 0, 60);
        $std->fone = preg_replace('/\D/', '', $p->celular ?? $p->telefone ?? '');
        $std->enderD = null;
        $make->tagdest($std);

        $std = new \stdClass;
        $std->xLgr = $p->logradouro;
        $std->nro = $p->numero;
        $std->xCpl = $p->complemento;
        $std->xBairro = $p->bairro;
        $std->cMun = $p->municipio_ibge ? (int) $p->municipio_ibge : 0;
        $std->xMun = $p->municipio;
        $std->CEP = preg_replace('/\D/', '', $p->cep ?? '');
        $std->UF = $p->uf;
        $std->cPais = $p->pais_ibge ?? '1058';
        $make->tagenderDest($std);
    }

    private function tagExped(MakeCTe $make, Cte $cte): void
    {
        if (! $cte->expedidor || $cte->expedidor_id === $cte->remetente_id) {
            return;
        }

        $p = $cte->expedidor;
        $std = new \stdClass;
        $std->CNPJ = $p->tipo_pessoa === 'PJ' ? preg_replace('/\D/', '', $p->cpf_cnpj) : null;
        $std->CPF = $p->tipo_pessoa === 'PF' ? preg_replace('/\D/', '', $p->cpf_cnpj) : null;
        $std->IE = $p->ie;
        $std->xNome = mb_substr($p->razao_social, 0, 60);
        $std->fone = preg_replace('/\D/', '', $p->celular ?? $p->telefone ?? '');
        $make->tagexped($std);
    }

    private function tagReceb(MakeCTe $make, Cte $cte): void
    {
        if (! $cte->recebedor || $cte->recebedor_id === $cte->destinatario_id) {
            return;
        }

        $p = $cte->recebedor;
        $std = new \stdClass;
        $std->CNPJ = $p->tipo_pessoa === 'PJ' ? preg_replace('/\D/', '', $p->cpf_cnpj) : null;
        $std->CPF = $p->tipo_pessoa === 'PF' ? preg_replace('/\D/', '', $p->cpf_cnpj) : null;
        $std->IE = $p->ie;
        $std->xNome = mb_substr($p->razao_social, 0, 60);
        $std->fone = preg_replace('/\D/', '', $p->celular ?? $p->telefone ?? '');
        $make->tagreceb($std);
    }

    private function tagToma(MakeCTe $make, Cte $cte): void
    {
        $tomador = $cte->tomador;
        if (! $tomador) {
            $tomador = match ($cte->tomador_tipo) {
                0 => $cte->remetente,
                1 => $cte->expedidor,
                2 => $cte->recebedor,
                3 => $cte->destinatario,
                default => null,
            };
        }

        if (! $tomador) {
            return;
        }

        $std = new \stdClass;
        $std->toma = $cte->tomador_tipo ?? 0;
        $make->tagtoma3($std);

        $std = new \stdClass;
        $std->CNPJ = $tomador->tipo_pessoa === 'PJ' ? preg_replace('/\D/', '', $tomador->cpf_cnpj) : null;
        $std->CPF = $tomador->tipo_pessoa === 'PF' ? preg_replace('/\D/', '', $tomador->cpf_cnpj) : null;
        $std->IE = $tomador->ie;
        $std->xNome = mb_substr($tomador->razao_social, 0, 60);
        $std->xFant = '';
        $std->fone = preg_replace('/\D/', '', $tomador->celular ?? $tomador->telefone ?? '');
        $std->email = $tomador->email;
        $make->tagtoma4($std);

        $std = new \stdClass;
        $std->xLgr = $tomador->logradouro;
        $std->nro = $tomador->numero;
        $std->xCpl = $tomador->complemento;
        $std->xBairro = $tomador->bairro;
        $std->cMun = $tomador->municipio_ibge ? (int) $tomador->municipio_ibge : 0;
        $std->xMun = $tomador->municipio;
        $std->CEP = preg_replace('/\D/', '', $tomador->cep ?? '');
        $std->UF = $tomador->uf;
        $std->cPais = $tomador->pais_ibge ?? '1058';
        $make->tagenderToma($std);
    }

    private function tagServico(MakeCTe $make, Cte $cte): void
    {
        $make->taginfCTeNorm();

        $std = new \stdClass;
        $std->vTPrest = (float) $cte->valor_servico;
        $std->vRec = (float) $cte->valor_receber;
        $make->tagvPrest($std);

        $std = new \stdClass;
        $std->cst = '00';
        $std->vBC = (float) $cte->valor_servico;
        $std->pICMS = $cte->valor_servico > 0 ? round(($cte->valor_icms / $cte->valor_servico) * 100, 2) : 0;
        $std->vICMS = (float) $cte->valor_icms;
        $make->tagicms($std);
    }

    private function tagInfNFes(MakeCTe $make, Cte $cte): void
    {
        foreach ($cte->nfes as $nfe) {
            $std = new \stdClass;
            $std->chave = $nfe->chave_nfe;
            $make->taginfNFe($std);
        }
    }

    private function tagTotal(MakeCTe $make, Cte $cte): void
    {
        $std = new \stdClass;
        $std->vTPrest = (float) $cte->valor_servico;
        $std->vRec = (float) $cte->valor_receber;
        $make->tagvPrest($std);
    }

    private function extractDV(?string $chave): int
    {
        if (empty($chave) || strlen($chave) !== 44) {
            return 0;
        }

        return (int) substr($chave, -1);
    }
}

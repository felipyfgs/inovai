<?php

namespace App\Services;

use App\Models\Mdfe;
use Illuminate\Support\Facades\Config;
use NFePHP\MDFe\Make;

class MdfeXmlService
{
    public function make(Mdfe $mdfe): string
    {
        $mdfe->loadMissing([
            'company',
            'veiculo',
            'motorista',
            'documentos',
        ]);

        $company = $mdfe->company;
        $versao = Config::get('fiscal.mdfe.versao', '3.00');

        $make = new Make;
        $make->versao = $versao;

        $this->tagIde($make, $mdfe, $company);
        $this->tagEmit($make, $company);
        $this->tagInfMunCarrega($make, $mdfe);
        $this->tagInfPercurso($make, $mdfe);
        $this->tagVeicTracao($make, $mdfe);
        $this->tagInfNFe($make, $mdfe);
        $this->tagInfCTe($make, $mdfe);
        $this->tagSeg($make, $mdfe);
        $this->tagTot($make, $mdfe);

        if ($mdfe->informacoes_adicionais) {
            $std = new \stdClass;
            $std->infCpl = $mdfe->informacoes_adicionais;
            $make->taginfAdic($std);
        }

        return (string) $make->getXML();
    }

    private function tagIde(Make $make, Mdfe $mdfe, $company): void
    {
        $cUF = Config::get("fiscal.uf_cuf.{$company->uf}", 35);

        $std = new \stdClass;
        $std->cUF = $cUF;
        $std->tpAmb = $mdfe->ambiente;
        $std->tpEmit = 1;
        $std->mod = $mdfe->modelo;
        $std->serie = $mdfe->serie;
        $std->nMDF = $mdfe->numero;
        $std->cMDF = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $std->cDV = $this->extractDV($mdfe->chave);
        $std->dhEmi = $mdfe->data_emissao->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format('Y-m-d\TH:i:sP');
        $std->tpEmis = $mdfe->ambiente === 1 ? 1 : 2;
        $std->procEmi = '0';
        $std->verProc = '1.00';
        $std->UFIni = $mdfe->uf_carregamento;
        $std->UFFim = $mdfe->uf_descarregamento;
        $make->tagide($std);
    }

    private function tagEmit(Make $make, $company): void
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
        $std->email = $company->email;
        $make->tagenderEmit($std);
    }

    private function tagInfMunCarrega(Make $make, Mdfe $mdfe): void
    {
        $std = new \stdClass;
        $std->cMunCarrega = (int) ($mdfe->municipio_carregamento_ibge ?? 0);
        $std->xMunCarrega = $mdfe->municipio_carregamento ?? '';
        $make->taginfMunCarrega($std);
    }

    private function tagInfPercurso(Make $make, Mdfe $mdfe): void
    {
        foreach ($mdfe->uf_percurso ?? [] as $uf) {
            $std = new \stdClass;
            $std->UF = $uf;
            $make->taginfPercurso($std);
        }
    }

    private function tagVeicTracao(Make $make, Mdfe $mdfe): void
    {
        $veiculo = $mdfe->veiculo;
        $motorista = $mdfe->motorista;

        $std = new \stdClass;
        $std->cInt = '';
        $std->placa = $mdfe->veiculo_placa ?? $veiculo->placa ?? '';
        $std->RENAVAM = $veiculo->renavam ?? '';
        $std->tara = (float) ($veiculo->tara ?? 0);
        $std->capKG = (float) ($veiculo->capacidade_kg ?? 0);
        $std->capM3 = 0;
        $std->tpRod = '';
        $std->tpCar = '';
        $std->UF = $veiculo->uf ?? '';

        if ($motorista || $mdfe->motorista_cpf) {
            $condutor = new \stdClass;
            $condutor->xNome = $mdfe->motorista_nome ?? $motorista->nome ?? '';
            $condutor->CPF = preg_replace('/\D/', '', $mdfe->motorista_cpf ?? $motorista->cpf ?? '');
            $std->condutor = [$condutor];
        }

        $make->tagveicTracao($std);
    }

    private function tagInfNFe(Make $make, Mdfe $mdfe): void
    {
        foreach ($mdfe->documentos->where('tipo', 'nfe') as $doc) {
            $std = new \stdClass;
            $std->chNFe = $doc->chave;
            $make->taginfNFe($std);
        }
    }

    private function tagInfCTe(Make $make, Mdfe $mdfe): void
    {
        foreach ($mdfe->documentos->where('tipo', 'cte') as $doc) {
            $std = new \stdClass;
            $std->chCTe = $doc->chave;
            $make->taginfCTe($std);
        }
    }

    private function tagSeg(Make $make, Mdfe $mdfe): void
    {
        $std = new \stdClass;
        $std->infResp = 0;
        $std->CNPJ = '';
        $std->nApol = '';
        $std->nAver = '';
        $make->tagseg($std);
    }

    private function tagTot(Make $make, Mdfe $mdfe): void
    {
        $std = new \stdClass;
        $std->qCTe = $mdfe->documentos->where('tipo', 'cte')->count();
        $std->qNFe = $mdfe->documentos->where('tipo', 'nfe')->count();
        $std->qMDFe = 0;
        $std->vCarga = (float) ($mdfe->valor_carga ?? 0);
        $std->cUnid = '01';
        $std->qCarga = (float) ($mdfe->peso_bruto ?? 0);
        $make->tagtot($std);
    }

    private function extractDV(?string $chave): int
    {
        if (empty($chave) || strlen($chave) !== 44) {
            return 0;
        }

        return (int) substr($chave, -1);
    }
}

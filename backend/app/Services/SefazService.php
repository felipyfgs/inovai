<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Config;
use NFePHP\NFe\Tools;

class SefazService
{
    public function __construct(
        private CertificateService $certificateService
    ) {}

    public function buildNfeConfigJson(Company $company): string
    {
        $uf = $company->uf;
        $cUF = Config::get("fiscal.uf_cuf.{$uf}", 35);
        $ambiente = $company->ambiente === 'producao' ? 1 : 2;

        $config = [
            'atualizacao' => now()->format('Y-m-d H:i:s'),
            'tpAmb' => $ambiente,
            'razaosocial' => $company->razao_social,
            'cnpj' => $company->cnpj,
            'ie' => $company->ie,
            'siglaUF' => $uf,
            'cUF' => $cUF,
            'schemes' => Config::get('fiscal.nfe.layout', 'PL_009_V4'),
            'versao' => Config::get('fiscal.nfe.versao', '4.00'),
            'CSC' => $company->csc_token ?? '',
            'CSCid' => $company->csc_id ?? '',
            'aProxyConf' => [
                'proxyIp' => Config::get('fiscal.proxy.ip', ''),
                'proxyPort' => Config::get('fiscal.proxy.port', ''),
                'proxyUser' => Config::get('fiscal.proxy.user', ''),
                'proxyPass' => Config::get('fiscal.proxy.pass', ''),
            ],
        ];

        return json_encode($config);
    }

    public function buildCteConfigJson(Company $company): string
    {
        $uf = $company->uf;
        $cUF = Config::get("fiscal.uf_cuf.{$uf}", 35);
        $ambiente = $company->ambiente === 'producao' ? 1 : 2;

        $config = [
            'atualizacao' => now()->format('Y-m-d H:i:s'),
            'tpAmb' => $ambiente,
            'razaosocial' => $company->razao_social,
            'cnpj' => $company->cnpj,
            'ie' => $company->ie,
            'siglaUF' => $uf,
            'cUF' => $cUF,
            'schemes' => Config::get('fiscal.cte.layout', 'CTe_v4.00'),
            'versao' => Config::get('fiscal.cte.versao', '4.00'),
            'aProxyConf' => [
                'proxyIp' => Config::get('fiscal.proxy.ip', ''),
                'proxyPort' => Config::get('fiscal.proxy.port', ''),
                'proxyUser' => Config::get('fiscal.proxy.user', ''),
                'proxyPass' => Config::get('fiscal.proxy.pass', ''),
            ],
        ];

        return json_encode($config);
    }

    public function buildMdfeConfigJson(Company $company): string
    {
        $uf = $company->uf;
        $cUF = Config::get("fiscal.uf_cuf.{$uf}", 35);
        $ambiente = $company->ambiente === 'producao' ? 1 : 2;

        $config = [
            'atualizacao' => now()->format('Y-m-d H:i:s'),
            'tpAmb' => $ambiente,
            'razaosocial' => $company->razao_social,
            'cnpj' => $company->cnpj,
            'ie' => $company->ie,
            'siglaUF' => $uf,
            'cUF' => $cUF,
            'schemes' => Config::get('fiscal.mdfe.layout', 'MDFe_v3.00'),
            'versao' => Config::get('fiscal.mdfe.versao', '3.00'),
            'aProxyConf' => [
                'proxyIp' => Config::get('fiscal.proxy.ip', ''),
                'proxyPort' => Config::get('fiscal.proxy.port', ''),
                'proxyUser' => Config::get('fiscal.proxy.user', ''),
                'proxyPass' => Config::get('fiscal.proxy.pass', ''),
            ],
        ];

        return json_encode($config);
    }

    public function makeNfeTools(Company $company): Tools
    {
        $configJson = $this->buildNfeConfigJson($company);
        $certificate = $this->certificateService->load($company);

        return new Tools($configJson, $certificate);
    }

    public function makeCteTools(Company $company): \NFePHP\CTe\Tools
    {
        $configJson = $this->buildCteConfigJson($company);
        $certificate = $this->certificateService->load($company);

        return new \NFePHP\CTe\Tools($configJson, $certificate);
    }

    public function makeMdfeTools(Company $company): \NFePHP\MDFe\Tools
    {
        $configJson = $this->buildMdfeConfigJson($company);
        $certificate = $this->certificateService->load($company);

        return new \NFePHP\MDFe\Tools($configJson, $certificate);
    }
}

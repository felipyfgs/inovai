<?php

namespace App\Services;

use App\Models\Company;
use NFePHP\Common\Keys;

class FiscalNumberService
{
    public function getNextNumero(Company $company, string $tipo): int
    {
        return match ($tipo) {
            'nfe' => $this->incrementAndGet($company, 'proximo_numero_nfe'),
            'nfce' => $this->incrementAndGet($company, 'proximo_numero_nfce'),
            'cte' => $this->incrementAndGet($company, 'proximo_numero_cte'),
            'mdfe' => $this->incrementAndGet($company, 'proximo_numero_mdfe'),
            default => throw new \InvalidArgumentException("Tipo de documento invalido: {$tipo}"),
        };
    }

    public function getSerie(Company $company, string $tipo): int
    {
        return match ($tipo) {
            'nfe' => $company->serie_nfe ?? 1,
            'nfce' => $company->serie_nfce ?? 1,
            'cte' => $company->serie_cte ?? 1,
            'mdfe' => $company->serie_mdfe ?? 1,
            default => 1,
        };
    }

    public function gerarChave(
        int $cUF,
        int $anoMes,
        string $cnpj,
        int $modelo,
        int $serie,
        int $numero,
        int $tpEmis,
        int $codigoAleatorio,
    ): string {
        return Keys::build(
            $cUF,
            $anoMes,
            $cnpj,
            $modelo,
            $serie,
            $numero,
            $tpEmis,
            $codigoAleatorio
        );
    }

    public function gerarCodigoAleatorio(): int
    {
        return random_int(10000000, 99999999);
    }

    public function gerarIdLote(): string
    {
        return str_pad((string) now()->timestamp, 15, '0', STR_PAD_LEFT);
    }

    private function incrementAndGet(Company $company, string $field): int
    {
        $company->increment($field);

        return $company->fresh()->$field - 1;
    }
}

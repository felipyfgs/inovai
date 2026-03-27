<?php

namespace App\Services;

use App\Models\Company;
use NFePHP\Common\Certificate;
use NFePHP\Common\Exception\CertificateException;

class CertificateService
{
    public function load(Company $company): Certificate
    {
        if (empty($company->certificado_pfx) || empty($company->certificado_senha)) {
            throw new \RuntimeException('Certificado digital nao configurado para esta empresa.');
        }

        $pfxRaw = base64_decode($company->certificado_pfx);

        try {
            return Certificate::readPfx($pfxRaw, $company->certificado_senha);
        } catch (CertificateException $e) {
            throw new \RuntimeException('Falha ao ler certificado: '.$e->getMessage());
        }
    }

    public function isExpired(Company $company): bool
    {
        if (! $company->certificado_validade) {
            return true;
        }

        return $company->certificado_validade->isPast();
    }

    public function daysUntilExpiry(Company $company): int
    {
        if (! $company->certificado_validade) {
            return 0;
        }

        return now()->diffInDays($company->certificado_validade, false);
    }

    public function getInfo(Company $company): array
    {
        try {
            $cert = $this->load($company);

            return [
                'cnpj' => $cert->getCnpj(),
                'razao_social' => $cert->getCompanyName(),
                'valido_de' => $cert->getValidFrom()->format('Y-m-d'),
                'valido_ate' => $cert->getValidTo()->format('Y-m-d'),
                'expirado' => $cert->isExpired(),
            ];
        } catch (\Throwable $e) {
            return [
                'erro' => $e->getMessage(),
            ];
        }
    }
}

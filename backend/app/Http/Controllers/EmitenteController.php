<?php

namespace App\Http\Controllers;

use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmitenteController extends Controller
{
    public function __construct(private CertificateService $certificateService) {}

    public function show(): JsonResponse
    {
        $company = app('current_company');

        return response()->json([
            'id' => $company->id,
            'razao_social' => $company->razao_social,
            'fantasia' => $company->fantasia,
            'cnpj' => $company->cnpj,
            'ie' => $company->ie,
            'im' => $company->im,
            'crt' => $company->crt,
            'logradouro' => $company->logradouro,
            'numero' => $company->numero,
            'complemento' => $company->complemento,
            'bairro' => $company->bairro,
            'municipio' => $company->municipio,
            'municipio_ibge' => $company->municipio_ibge,
            'uf' => $company->uf,
            'cep' => $company->cep,
            'pais' => $company->pais,
            'pais_ibge' => $company->pais_ibge,
            'telefone' => $company->telefone,
            'email' => $company->email,
            'ambiente' => $company->ambiente,
            'certificado_validade' => $company->certificado_validade,
            'tem_certificado' => ! empty($company->certificado_pfx),
            'serie_nfe' => $company->serie_nfe,
            'proximo_numero_nfe' => $company->proximo_numero_nfe,
            'serie_nfce' => $company->serie_nfce,
            'proximo_numero_nfce' => $company->proximo_numero_nfce,
            'serie_cte' => $company->serie_cte,
            'proximo_numero_cte' => $company->proximo_numero_cte,
            'serie_mdfe' => $company->serie_mdfe,
            'proximo_numero_mdfe' => $company->proximo_numero_mdfe,
            'csc_id' => $company->csc_id,
            'csc_token' => $company->csc_token,
        ]);
    }

    public function updateDados(Request $request): JsonResponse
    {
        $company = app('current_company');

        $validated = $request->validate([
            'razao_social' => ['required', 'string', 'max:255'],
            'fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'max:18', "unique:companies,cnpj,{$company->id}"],
            'ie' => ['nullable', 'string', 'max:20'],
            'im' => ['nullable', 'string', 'max:20'],
            'crt' => ['required', 'integer', 'in:1,2,3'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:10'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'municipio_ibge' => ['nullable', 'string', 'max:7'],
            'uf' => ['nullable', 'string', 'size:2'],
            'cep' => ['nullable', 'string', 'max:9'],
            'pais' => ['nullable', 'string', 'max:255'],
            'pais_ibge' => ['nullable', 'string', 'max:4'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $company->update($validated);

        return response()->json(['message' => 'Dados do emitente atualizados com sucesso.']);
    }

    public function updateNumeracao(Request $request): JsonResponse
    {
        $company = app('current_company');

        $validated = $request->validate([
            'serie_nfe' => ['required', 'integer', 'min:0', 'max:999'],
            'proximo_numero_nfe' => ['required', 'integer', 'min:1', 'max:999999999'],
            'serie_nfce' => ['required', 'integer', 'min:0', 'max:999'],
            'proximo_numero_nfce' => ['required', 'integer', 'min:1', 'max:999999999'],
            'serie_cte' => ['required', 'integer', 'min:0', 'max:999'],
            'proximo_numero_cte' => ['required', 'integer', 'min:1', 'max:999999999'],
            'serie_mdfe' => ['required', 'integer', 'min:0', 'max:999'],
            'proximo_numero_mdfe' => ['required', 'integer', 'min:1', 'max:999999999'],
        ]);

        $company->update($validated);

        return response()->json(['message' => 'Numeração atualizada com sucesso.']);
    }

    public function updateCsc(Request $request): JsonResponse
    {
        $company = app('current_company');

        $validated = $request->validate([
            'csc_id' => ['nullable', 'string', 'max:10'],
            'csc_token' => ['nullable', 'string', 'max:100'],
        ]);

        $company->update($validated);

        return response()->json(['message' => 'CSC atualizado com sucesso.']);
    }

    public function updateAmbiente(Request $request): JsonResponse
    {
        $company = app('current_company');

        $validated = $request->validate([
            'ambiente' => ['required', 'string', 'in:homologacao,producao'],
        ]);

        $company->update($validated);

        return response()->json(['message' => 'Ambiente atualizado com sucesso.']);
    }

    public function certificado(): JsonResponse
    {
        $company = app('current_company');

        if (empty($company->certificado_pfx)) {
            return response()->json([
                'tem_certificado' => false,
                'validade' => null,
            ]);
        }

        $info = $this->certificateService->getInfo($company);

        return response()->json([
            'tem_certificado' => true,
            'validade' => $company->certificado_validade?->format('Y-m-d'),
            'info' => $info,
        ]);
    }

    public function uploadCertificado(Request $request): JsonResponse
    {
        $company = app('current_company');

        $request->validate([
            'certificado' => ['required', 'file', 'max:10240'],
            'senha' => ['required', 'string'],
        ]);

        $pfxContent = base64_encode(file_get_contents($request->file('certificado')->path()));

        $certData = [];
        $pfxRaw = base64_decode($pfxContent);
        if (! openssl_pkcs12_read($pfxRaw, $certData, $request->input('senha'))) {
            return response()->json(['message' => 'Certificado inválido ou senha incorreta.'], 422);
        }

        $certInfo = openssl_x509_parse($certData['cert']);
        $validade = date('Y-m-d', $certInfo['validTo_time_t']);

        $company->update([
            'certificado_pfx' => $pfxContent,
            'certificado_senha' => $request->input('senha'),
            'certificado_validade' => $validade,
        ]);

        return response()->json([
            'message' => 'Certificado enviado com sucesso.',
            'validade' => $validade,
        ]);
    }

    public function removerCertificado(): JsonResponse
    {
        $company = app('current_company');

        $company->update([
            'certificado_pfx' => null,
            'certificado_senha' => null,
            'certificado_validade' => null,
        ]);

        return response()->json(['message' => 'Certificado removido com sucesso.']);
    }
}

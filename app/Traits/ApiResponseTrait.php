<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trait ApiResponseTrait
 *
 * Trait para respostas padronizadas da API, garantindo um formato consistente
 * em todos os endpoints, tanto para sucesso quanto para erros.
 */
trait ApiResponseTrait
{
    /**
     * Retorna uma resposta de sucesso padronizada.
     *
     * @param string $message Mensagem descritiva da operação (padrão: 'Operação realizada com sucesso.').
     * @param mixed $data Dados a serem retornados na resposta (pode ser array, string ou null).
     * @param int $status Código HTTP da resposta (padrão: 200).
     * @param array $headers Headers adicionais da resposta.
     *
     * @return JsonResponse Retorna uma instância JsonResponse com estrutura {'success': true, 'code': int, 'message': string, 'data': mixed}.
     */
    public function successResponse(
        string $message = 'Operação realizada com sucesso.',
        $data = null,
        int $status = 200,
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'status' => $status,
        ], $status, $headers);
    }

    /**
     * Retorna uma resposta de erro padronizada.
     *
     * @param string $message Mensagem descritiva do erro (padrão: 'Ocorreu um erro.').
     * @param mixed $data Dados a serem retornados na resposta (pode ser null se não houver dados).
     * @param int $status Código HTTP da resposta (padrão: 500).
     * @param ?array $errors Array de erros detalhados (pode ser null se não houver erros de validação específicos).
     * @param array $headers Headers adicionais da resposta.
     *
     * @return JsonResponse Retorna uma instância JsonResponse com estrutura {'success': false, 'code': int, 'message': string, 'data': mixed, 'errors': array|null}.
     */
    public function errorResponse(
        string $message = 'Ocorreu um erro.',
        $data = null,
        int $status = 500,
        ?array $errors = null,
        array $headers = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'data' => $data,
            'message' => $message,
            'status' => $status,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status, $headers);
    }

    /**
     * Retorna uma resposta paginada padronizada.
     *
     * @param LengthAwarePaginator $paginator Instância do paginador com os dados e metadados de paginação.
     * @param string $message Mensagem descritiva da operação (padrão: 'Dados listados com sucesso.').
     * @param int $status Código HTTP da resposta (padrão: 200).
     * @param array $headers Headers adicionais da resposta.
     *
     * @return JsonResponse Retorna uma instância JsonResponse com estrutura de sucesso contendo 'items' e 'pagination'.
     */
    public function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = 'Dados listados com sucesso.',
        int $status = 200,
        array $headers = []
    ): JsonResponse {
        return $this->successResponse(
            message: $message,
            data: [
                'items' => $paginator->items(),
                'pagination' => [
                    'total' => $paginator->total(),
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
            status: $status,
            headers: $headers
        );
    }
}
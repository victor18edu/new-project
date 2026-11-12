@extends('layouts.app')

@section('content')
<div class='card shadow-sm border-0'>
    <div class='card-body p-4 text-center'>
        <span class='badge bg-success mb-3 fs-6'>Docker + Vite + Bootstrap 5</span>
        <h1 class='card-title fw-bold'>Ambiente Frontend Conectado</h1>
        <p class='card-text text-muted'>
            Backend configurado em: <code>{{ config('services.backend.base_url', env('API_BASE_URL')) }}</code>
        </p>
        <button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#testModal'>
            Testar JS Bootstrap
        </button>
    </div>
</div>

<!-- Modal de teste -->
<div class='modal fade' id='testModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog modal-dialog-centered'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h5 class='modal-title'>Bootstrap 5 Ativo</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>
                O JavaScript do Bootstrap e o Vite HMR estao funcionando perfeitamente dentro do container!
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Fechar</button>
            </div>
        </div>
    </div>
</div>
@endsection
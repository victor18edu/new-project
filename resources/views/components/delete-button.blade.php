@props([
    'action',
    'message' => 'Tem certeza que deseja remover este registro?',
    'formId',
    'label' => 'Excluir',
    'class' => 'text-sm font-medium text-red-600 hover:text-red-500',
])

<form id="{{ $formId }}" method="POST" action="{{ $action }}" class="hidden">
    @csrf
    @method('DELETE')
</form>

<button
    type="button"
    class="{{ $class }}"
    data-confirm-message="{{ $message }}"
    data-confirm-form-id="{{ $formId }}"
    @click='$dispatch("crm-confirm", { message: $el.dataset.confirmMessage, formId: $el.dataset.confirmFormId })'
>{{ $label }}</button>

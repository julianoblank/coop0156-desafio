<?php

namespace App\Http\Requests;

use App\Enums\TipoCredito;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnaliseCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'digits:11'],
            'renda_mensal' => ['required', 'numeric', 'gt:0'],
            'tipo_credito' => ['required', Rule::enum(TipoCredito::class)],
            'valor_solicitado' => ['required', 'numeric', 'gt:0'],
        ];
    }
}

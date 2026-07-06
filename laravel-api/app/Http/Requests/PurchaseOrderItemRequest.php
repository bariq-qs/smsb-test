<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'product_id' => ['required', 'uuid', 'exists:products,id'],
                'quantity' => ['required', 'integer', 'min:1'],
            ];
        }

        return [
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}

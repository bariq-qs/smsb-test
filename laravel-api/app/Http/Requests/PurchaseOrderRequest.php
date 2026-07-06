<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $purchaseOrderId = $this->route('purchase_order')?->id;

        return [
            'supplier_id' => ['required', 'uuid', 'exists:suppliers,id'],
            'po_number' => ['required', 'string', 'max:100', Rule::unique('purchase_orders', 'po_number')->ignore($purchaseOrderId)],
            'order_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['draft', 'submitted', 'approved', 'rejected', 'completed'])],
            'is_urgent' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'min:100', 'max:500'],
        ];
    }
}

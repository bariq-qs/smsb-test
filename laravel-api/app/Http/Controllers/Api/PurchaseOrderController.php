<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderRequest;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::query()->with('supplier:id,name')->withCount('items')->withSum('items', 'subtotal');

        if ($search = $request->query('search')) {
            $query->where('po_number', 'like', "%{$search}%");
        }

        if ($supplierId = $request->query('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $sortBy = in_array($request->query('sort_by'), ['po_number', 'order_date', 'created_at']) ? $request->query('sort_by') : 'order_date';
        $sortDir = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return response()->json($query->paginate($request->integer('per_page', 15)));
    }

    public function store(PurchaseOrderRequest $request)
    {
        $data = $request->validated();
        $data['notes'] = $this->wrapNotes($data['notes'] ?? null);

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('purchase-orders', 'public');
        }

        $purchaseOrder = PurchaseOrder::create($data);

        return response()->json($this->present($purchaseOrder), 201);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier:id,name', 'items.product:id,name,sku']);

        return response()->json($purchaseOrder);
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validated();
        $data['notes'] = $this->wrapNotes($data['notes'] ?? null);

        if ($request->hasFile('attachment')) {
            if ($purchaseOrder->attachment_path) {
                Storage::disk('public')->delete($purchaseOrder->attachment_path);
            }
            $data['attachment_path'] = $request->file('attachment')->store('purchase-orders', 'public');
        }

        $purchaseOrder->update($data);

        return response()->json($this->present($purchaseOrder));
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->delete();

        return response()->json(['message' => 'Purchase order deleted']);
    }

    private function wrapNotes(?string $notes): ?array
    {
        return $notes ? ['remarks' => $notes] : null;
    }

    private function present(PurchaseOrder $purchaseOrder)
    {
        return $purchaseOrder->load('supplier:id,name');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\Inventory\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService
    ) {}

    /**
     * GET /api/gudang/supplier?search=&status=&page= — SD-13
     * search mencari di nama_supplier DAN no_telp.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $keyword = $request->string('search');
            $query->where(function ($q) use ($keyword) {
                $q->where('nama_supplier', 'like', "%{$keyword}%")
                  ->orWhere('no_telp', 'like', "%{$keyword}%");
            });
        }

        $status = $request->input('status', 'aktif');
        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        $limit    = min((int) $request->input('limit', 20), 100);
        $supplier = $query->orderBy('nama_supplier')->paginate($limit);

        return response()->json([
            'data'       => $supplier->items(),
            'pagination' => [
                'current_page' => $supplier->currentPage(),
                'last_page'    => $supplier->lastPage(),
                'per_page'     => $supplier->perPage(),
                'total'        => $supplier->total(),
            ],
        ], 200);
    }

    /**
     * POST /api/gudang/supplier — SD-13
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create($request->validated());

        return response()->json([
            'data'    => $supplier,
            'message' => 'Supplier berhasil ditambahkan',
        ], 201);
    }

    /**
     * GET /api/gudang/supplier/{id} — SD-13
     */
    public function show(int $supplier): JsonResponse
    {
        $s = Supplier::find($supplier);

        if (! $s) {
            return response()->json(['message' => 'Supplier tidak ditemukan'], 404);
        }

        return response()->json(['data' => $s], 200);
    }

    /**
     * PUT /api/gudang/supplier/{id} — SD-13
     */
    public function update(UpdateSupplierRequest $request, int $supplier): JsonResponse
    {
        $s = Supplier::find($supplier);

        if (! $s) {
            return response()->json(['message' => 'Supplier tidak ditemukan'], 404);
        }

        $this->supplierService->update($supplier, $request->validated());

        return response()->json([
            'data'    => $s->refresh(),
            'message' => 'Supplier berhasil diperbarui',
        ], 200);
    }

    /**
     * DELETE /api/gudang/supplier/{id} — SD-13, AD-13
     *
     * Smart delete: soft jika pernah ada PO, hard jika belum.
     */
    public function destroy(int $supplier): JsonResponse
    {
        $s = Supplier::find($supplier);

        if (! $s) {
            return response()->json(['message' => 'Supplier tidak ditemukan'], 404);
        }

        $result  = $this->supplierService->destroy($supplier);
        $message = $result === 'soft'
            ? 'Supplier dinonaktifkan (masih ada di riwayat pembelian)'
            : 'Supplier dihapus';

        return response()->json(['message' => $message], 200);
    }
}

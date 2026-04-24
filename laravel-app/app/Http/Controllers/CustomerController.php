<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\Inventory\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService
    ) {}

    /**
     * GET /api/kasir/customer?search=&status=&page= — SD-11, SD-12
     *
     * search mencari di nama DAN no_telp (kasir mungkin cari by nama atau nomor HP).
     * Default status=aktif — kasir biasanya tidak perlu lihat customer nonaktif.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $keyword = $request->string('search');
            $query->where(function ($q) use ($keyword) {
                $q->where('nama', 'like', "%{$keyword}%")
                  ->orWhere('no_telp', 'like', "%{$keyword}%");
            });
        }

        $status = $request->input('status', 'aktif');
        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        $limit    = min((int) $request->input('limit', 20), 100);
        $customer = $query->orderBy('nama')->paginate($limit);

        return response()->json([
            'data'       => $customer->items(),
            'pagination' => [
                'current_page' => $customer->currentPage(),
                'last_page'    => $customer->lastPage(),
                'per_page'     => $customer->perPage(),
                'total'        => $customer->total(),
            ],
        ], 200);
    }

    /**
     * POST /api/kasir/customer — SD-11
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->create($request->validated());

        return response()->json([
            'data'    => $customer,
            'message' => 'Customer berhasil ditambahkan',
        ], 201);
    }

    /**
     * GET /api/kasir/customer/{id} — SD-11
     */
    public function show(int $customer): JsonResponse
    {
        $c = Customer::find($customer);

        if (! $c) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }

        return response()->json(['data' => $c], 200);
    }

    /**
     * PUT /api/kasir/customer/{id} — SD-11
     */
    public function update(UpdateCustomerRequest $request, int $customer): JsonResponse
    {
        $c = Customer::find($customer);

        if (! $c) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }

        $this->customerService->update($customer, $request->validated());

        return response()->json([
            'data'    => $c->refresh(),
            'message' => 'Customer berhasil diperbarui',
        ], 200);
    }

    /**
     * DELETE /api/kasir/customer/{id} — SD-11, AD-11
     *
     * Smart delete: soft jika pernah transaksi, hard jika belum.
     * Pola identik dengan ProdukService::destroy() — konsistensi di seluruh codebase.
     */
    public function destroy(int $customer): JsonResponse
    {
        $c = Customer::find($customer);

        if (! $c) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }

        $result  = $this->customerService->destroy($customer);
        $message = $result === 'soft'
            ? 'Customer dinonaktifkan (masih ada di riwayat transaksi)'
            : 'Customer dihapus';

        return response()->json(['message' => $message], 200);
    }

    /**
     * GET /api/kasir/customer/{id}/riwayat?page=1&limit=20 — SD-12
     *
     * Agregasi total_belanja dan frekuensi hanya dari COMPLETED.
     * jumlah_item per transaksi di-count via withCount — tidak N+1.
     */
    public function riwayatBelanja(int $customer, Request $request): JsonResponse
    {
        $c = Customer::find($customer);

        if (! $c) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }

        $page   = max(1, (int) $request->input('page', 1));
        $limit  = min((int) $request->input('limit', 20), 100);

        $result = $this->customerService->getRiwayatBelanja($customer, $page, $limit);

        return response()->json(['data' => $result], 200);
    }
}

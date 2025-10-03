<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Models\Operational;

class OperationController extends Controller
{
    //
    use ApiResponse;

    public function getOperation(Request $request)
    {
        // Buat query dengan filter
        $query = $this->buildOperationQuery($request);

        // Order berdasarkan created_at (default desc)
        $order = $request->order ?? 'desc';
        $query->orderBy('created_at', $order);

        // Pagination
        $limit = $request->limit ?? 10;
        $operations = $query->paginate($limit);

        // Hitung total amount dari semua operational yang difilter
        $totalAmountQuery = $this->buildOperationQuery($request);
        $totalAmount = $totalAmountQuery->sum('amount');

        // Hitung per status
        $totalPendingAmount = $this->buildOperationQuery($request)->where('status', 'pending')->sum('amount');
        $totalSuccessAmount = $this->buildOperationQuery($request)->where('status', 'success')->sum('amount');
        $totalFailedAmount = $this->buildOperationQuery($request)->where('status', 'failed')->sum('amount');

        return $this->successResponseWithPagination([
            'data' => $operations->items(),
            'current_page' => $operations->currentPage(),
            'per_page' => $operations->perPage(),
            'total' => $operations->total(),
            'last_page' => $operations->lastPage(),
            'from' => $operations->firstItem(),
            'to' => $operations->lastItem(),
            'statistics' => [
                'total' => $totalAmount,
                'pending' => $totalPendingAmount,
                'success' => $totalSuccessAmount,
                'failed' => $totalFailedAmount
            ]
        ], 'Data operation berhasil diambil', 200);
    }

    private function buildOperationQuery(Request $request)
    {
        $query = Operational::query();

        // Filter berdasarkan rentang tanggal
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $this->parseDate($request->start_date)->startOfDay();
            $endDate = $this->parseDate($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($request->has('start_date')) {
            $startDate = $this->parseDate($request->start_date)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        } elseif ($request->has('end_date')) {
            $endDate = $this->parseDate($request->end_date)->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }

        // Filter berdasarkan search (nama atau description)
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter berdasarkan type
        if ($request->has('type')) {
            if ($request->type == 'modal') {
                $query->whereNotNull('transaksi_id');
            } else {
                $query->whereNull('transaksi_id');
            }
        }

        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter no modal
        if ($request->has('no_modal')) {
            $query->whereNull('transaksi_id');
        }

        // Filter soft deleted records
        $query->whereNull('deleted_at');

        return $query;
    }

    public function createOperation(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'amount' => 'required',
            'description' => 'nullable',
        ]);
        $operation = Operational::create($request->all());
        return $this->successResponse($operation, 'Data operation berhasil dibuat', 201);
    }

    public function updateOperation(Request $request, $id)
    {
        $operation = Operational::find($id);
        if (!$operation) {
            return $this->errorResponse('Data operation tidak ditemukan', 404);
        }
        if ($operation->status !== 'pending') {
            return $this->errorResponse('Data operation tidak dapat diubah status', 400);
        }
        if ($operation->transaksi_id) {
            return $this->errorResponse('Data operation tidak dapat diubah status', 400);
        }
        if ($operation->deleted_at) {
            return $this->errorResponse('Data operation tidak aktif', 401);
        }
        if ($request->has('name')) {
            $operation->name = $request->name;
        }
        if ($request->has('amount')) {
            $operation->amount = $request->amount;
        }
        if ($request->has('description')) {
            $operation->description = $request->description;
        }
        if ($request->has('status')) {
            $operation->status = $request->status;
        }
        $operation->updated_at = now();
        $operation->save();
        return $this->successResponse($operation, 'Data operation berhasil diubah', 200);
    }


    public function getOperationById(Request $request, $id)
    {
        $operation = Operational::find($id);
        if (!$operation) {
            return $this->errorResponse('Data operation tidak ditemukan', 404);
        }
        if ($operation->deleted_at) {
            return $this->errorResponse('Data operation tidak aktif', 401);
        }
        return $this->successResponse($operation, 'Data operation berhasil diambil', 200);
    }


    private function parseDate($dateString)
    {
        // Coba berbagai format tanggal
        $formats = [
            'm-d-Y',      // 09-18-2025
            'd-m-Y',      // 18-09-2025
            'Y-m-d',      // 2025-09-18
            'm/d/Y',      // 09/18/2025
            'd/m/Y',      // 18/09/2025
            'Y/m/d',      // 2025/09/18
        ];

        foreach ($formats as $format) {
            try {
                return \Carbon\Carbon::createFromFormat($format, $dateString);
            } catch (\Exception $e) {
                continue;
            }
        }

        // Jika semua format gagal, coba parse biasa
        try {
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Tidak bisa memparse tanggal: {$dateString}");
        }
    }
}

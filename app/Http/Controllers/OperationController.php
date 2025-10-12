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
        $query = $this->buildOperationQuery($request)->with(['transaksi.pasien:id,nama,no_rm,domisili,no_hp', 'transaksi.docter:id,name', 'transaksi.dantel:id,name']);

        // Order berdasarkan created_at
        // Jika ada transaksi_id: order by transaksi.created_at
        // Jika tidak ada transaksi_id: order by operational.created_at
        $order = $request->order ?? 'desc';
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc'; // Validasi order
        $query->leftJoin('transaksis', 'operationals.transaksi_id', '=', 'transaksis.id')
            ->select('operationals.*')
            ->orderByRaw("COALESCE(transaksis.created_at, operationals.created_at) {$order}");

        // Pagination
        $limit = $request->limit ?? 10;
        $operations = $query->paginate($limit);

        // Hitung total amount dari semua operational yang difilter
        $totalAmount = $this->buildOperationQuery($request)->sum('amount');
        $totalModalAmount = $this->buildOperationQuery($request)->whereNotNull('transaksi_id')->where('status', 'success')->sum('amount');
        $totalNonModalAmount = $this->buildOperationQuery($request)->whereNull('transaksi_id')->where('status', 'success')->sum('amount');
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
                'total_modal' => $totalModalAmount,
                'total_non_modal' => $totalNonModalAmount,
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
        // Jika ada transaksi_id: pakai created_at transaksi
        // Jika tidak ada transaksi_id: pakai created_at operational
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $this->parseDate($request->start_date)->startOfDay();
            $endDate = $this->parseDate($request->end_date)->endOfDay();

            $query->where(function ($q) use ($startDate, $endDate) {
                // Operational yang punya transaksi_id: filter berdasarkan transaksi.created_at
                $q->whereHas('transaksi', function ($subQ) use ($startDate, $endDate) {
                    $subQ->whereBetween('transaksis.created_at', [$startDate, $endDate]);
                })
                    // ATAU operational yang tidak punya transaksi_id: filter berdasarkan operational.created_at
                    ->orWhere(function ($subQ) use ($startDate, $endDate) {
                        $subQ->whereNull('operationals.transaksi_id')
                            ->whereBetween('operationals.created_at', [$startDate, $endDate]);
                    });
            });
        } elseif ($request->has('start_date')) {
            $startDate = $this->parseDate($request->start_date)->startOfDay();

            $query->where(function ($q) use ($startDate) {
                // Operational yang punya transaksi_id: filter berdasarkan transaksi.created_at
                $q->whereHas('transaksi', function ($subQ) use ($startDate) {
                    $subQ->where('transaksis.created_at', '>=', $startDate);
                })
                    // ATAU operational yang tidak punya transaksi_id: filter berdasarkan operational.created_at
                    ->orWhere(function ($subQ) use ($startDate) {
                        $subQ->whereNull('operationals.transaksi_id')
                            ->where('operationals.created_at', '>=', $startDate);
                    });
            });
        } elseif ($request->has('end_date')) {
            $endDate = $this->parseDate($request->end_date)->endOfDay();

            $query->where(function ($q) use ($endDate) {
                // Operational yang punya transaksi_id: filter berdasarkan transaksi.created_at
                $q->whereHas('transaksi', function ($subQ) use ($endDate) {
                    $subQ->where('transaksis.created_at', '<=', $endDate);
                })
                    // ATAU operational yang tidak punya transaksi_id: filter berdasarkan operational.created_at
                    ->orWhere(function ($subQ) use ($endDate) {
                        $subQ->whereNull('operationals.transaksi_id')
                            ->where('operationals.created_at', '<=', $endDate);
                    });
            });
        }

        // Filter berdasarkan tipe
        if ($request->has('type')) {
            if ($request->type == 'modal') {
                $query->whereNotNull('operationals.transaksi_id');
            } else {
                $query->whereNull('operationals.transaksi_id');
            }
        }


        // Filter berdasarkan search (nama atau description)
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('operationals.name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('operationals.description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter berdasarkan type
        if ($request->has('type')) {
            if ($request->type == 'modal') {
                $query->whereNotNull('operationals.transaksi_id');
            } else {
                $query->whereNull('operationals.transaksi_id');
            }
        }

        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('operationals.status', $request->status);
        }

        // Filter no modal
        if ($request->has('no_modal')) {
            $query->whereNull('operationals.transaksi_id');
        }

        // Filter soft deleted records
        $query->whereNull('operationals.deleted_at');

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

    public function softDeleteOperation(Request $request, $id)
    {
        $operation = Operational::find($id);
        if (!$operation) {
            return $this->errorResponse('Data operation tidak ditemukan', 404);
        }
        $operation->deleted_at = now();
        $operation->save();
        return $this->successResponse($operation, 'Data operation berhasil dihapus', 200);
    }
    public function updateStatusOperation(Request $request, $id)
    {
        $operation = Operational::find($id);
        if (!$operation) {
            return $this->errorResponse('Data operation tidak ditemukan', 404);
        }
        if ($operation->deleted_at) {
            return $this->errorResponse('Data operation tidak aktif', 401);
        }


        if ($request->has('status')) {
            $operation->status = $request->status;
        }
        $operation->save();
        return $this->successResponse($operation, 'Data operation berhasil diubah status', 200);
    }
}

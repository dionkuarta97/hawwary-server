<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Models\FeeDistribution;
use App\Models\Tranksaksi;

class FeeDistributionController extends Controller
{
    use ApiResponse;

    public function getFeeDistributions(Request $request)
    {
        try {
            // Buat query dengan filter
            $query = $this->buildFeeDistributionQuery($request);

            // Order berdasarkan created_at transaksi (default desc)
            $order = $request->order ?? 'desc';
            $orderby = $request->orderby ?? 'transaksi_created_at';

            // Jika order by transaksi_created_at, lakukan JOIN
            if ($orderby === 'transaksi_created_at') {
                $query->join('transaksis', 'fee_distributions.transaksi_id', '=', 'transaksis.id')
                    ->select('fee_distributions.*')
                    ->orderBy('transaksis.created_at', $order);
            } else {
                // Qualify column dengan nama tabel untuk menghindari ambiguitas
                $qualifiedOrderBy = strpos($orderby, '.') === false ? 'fee_distributions.' . $orderby : $orderby;
                $query->orderBy($qualifiedOrderBy, $order);
            }

            // Eager load relationships
            $query->withRecipient()
                ->with(['transaksi.pasien:id,nama,no_rm', 'additionalFee:id,name,percentage', 'transaksi.docter:id,name', 'transaksi.dantel:id,name', 'transaksi.operational']);

            // Pagination
            $limit = $request->limit ?? 10;
            $feeDistributions = $query->paginate($limit);

            // Hitung total amount dari semua fee distributions yang difilter
            $totalAmountQuery = $this->buildFeeDistributionQuery($request);
            $totalAmount = $totalAmountQuery->sum('amount');

            // Hitung total per additional fee (dinamis berdasarkan data di database)
            $statisticsByFee = $this->buildFeeDistributionQuery($request)
                ->join('addtional_fees', 'fee_distributions.additional_fee_id', '=', 'addtional_fees.id')
                ->selectRaw('addtional_fees.name as fee_name, SUM(fee_distributions.amount) as total_amount, COUNT(fee_distributions.id) as total_count')
                ->groupBy('addtional_fees.id', 'addtional_fees.name')
                ->get();

            // Format statistik berdasarkan additional fee
            $feeStatistics = [];
            foreach ($statisticsByFee as $stat) {
                $feeKey = strtolower(str_replace([' ', ',', '.'], ['_', '', ''], $stat->fee_name));
                $feeStatistics[$feeKey] = [
                    'name' => $stat->fee_name,
                    'total_amount' => (float) $stat->total_amount,
                    'total_count' => (int) $stat->total_count,
                ];
            }

            return $this->successResponseWithPagination([
                'data' => $feeDistributions->items(),
                'current_page' => $feeDistributions->currentPage(),
                'per_page' => $feeDistributions->perPage(),
                'total' => $feeDistributions->total(),
                'last_page' => $feeDistributions->lastPage(),
                'from' => $feeDistributions->firstItem(),
                'to' => $feeDistributions->lastItem(),
                'statistics' => [
                    'total_amount' => $totalAmount,
                    'by_additional_fee' => $feeStatistics,
                ]
            ], 'Data fee distribution berhasil diambil', 200);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    private function buildFeeDistributionQuery(Request $request)
    {
        $query = FeeDistribution::query();

        // Filter berdasarkan rentang tanggal (berdasarkan created_at transaksi)
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $this->parseDate($request->start_date)->startOfDay();
            $endDate = $this->parseDate($request->end_date)->endOfDay();
            $query->whereHas('transaksi', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            });
        } elseif ($request->has('start_date')) {
            $startDate = $this->parseDate($request->start_date)->startOfDay();
            $query->whereHas('transaksi', function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
            });
        } elseif ($request->has('end_date')) {
            $endDate = $this->parseDate($request->end_date)->endOfDay();
            $query->whereHas('transaksi', function ($q) use ($endDate) {
                $q->where('created_at', '<=', $endDate);
            });
        }

        // Filter berdasarkan transaksi_id
        if ($request->has('transaksi_id')) {
            $query->where('fee_distributions.transaksi_id', $request->transaksi_id);
        }

        // Filter berdasarkan recipient (type dan id harus bersamaan)
        if ($request->has('recipient_type') && $request->has('recipient_id')) {
            // Jika ada recipient_type dan recipient_id, filter keduanya
            $query->where('fee_distributions.recipient_type', 'like', '%' . $request->recipient_type . '%')
                ->where('fee_distributions.recipient_id', $request->recipient_id);
        } elseif ($request->has('recipient_type') && !$request->has('recipient_id')) {
            // Jika hanya ada recipient_type tanpa recipient_id, filter by type saja
            $query->where('fee_distributions.recipient_type', 'like', '%' . $request->recipient_type . '%');
        } elseif ($request->has('recipient_id') && !$request->has('recipient_type')) {
            // Jika ada recipient_id tanpa recipient_type, throw error
            throw new \InvalidArgumentException('Parameter recipient_id harus disertai dengan recipient_type untuk menghindari ambiguitas (docter id 1 berbeda dengan dantel id 1)');
        }

        // Filter berdasarkan search (nama recipient atau nama pasien)
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                // Search di docter
                $q->whereHas('docter', function ($subQ) use ($searchTerm) {
                    $subQ->where('name', 'like', '%' . $searchTerm . '%');
                })
                    // Search di dantel
                    ->orWhereHas('dantel', function ($subQ) use ($searchTerm) {
                        $subQ->where('name', 'like', '%' . $searchTerm . '%');
                    })
                    // Search di pasien via transaksi
                    ->orWhereHas('transaksi.pasien', function ($subQ) use ($searchTerm) {
                        $subQ->where('nama', 'like', '%' . $searchTerm . '%')
                            ->orWhere('no_rm', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        return $query;
    }

    public function getFeeDistributionById($id)
    {
        $feeDistribution = FeeDistribution::withRecipient()
            ->with([
                'transaksi.pasien:id,nama,no_rm,domisili,no_hp',
                'transaksi.docter:id,name',
                'transaksi.dantel:id,name',
                'additionalFee:id,name,percentage'
            ])
            ->find($id);

        if (!$feeDistribution) {
            return $this->errorResponse('Fee distribution tidak ditemukan', 404);
        }

        return $this->successResponse($feeDistribution, 'Fee distribution berhasil diambil', 200);
    }

    public function getFeeDistributionByTransaksi($transaksi_id)
    {
        $transaksi = Tranksaksi::find($transaksi_id);
        if (!$transaksi) {
            return $this->errorResponse('Transaksi tidak ditemukan', 404);
        }

        $feeDistributions = FeeDistribution::where('transaksi_id', $transaksi_id)
            ->withRecipient()
            ->with(['additionalFee:id,name,percentage'])
            ->get();

        $totalAmount = $feeDistributions->sum('amount');

        // Hitung statistik berdasarkan additional fee
        $feeStatistics = [];
        $groupedByFee = $feeDistributions->groupBy('additional_fee_id');
        foreach ($groupedByFee as $feeId => $distributions) {
            $firstDistribution = $distributions->first();
            if ($firstDistribution && $firstDistribution->additionalFee) {
                $feeName = $firstDistribution->additionalFee->name;
                $feeKey = strtolower(str_replace([' ', ',', '.'], ['_', '', ''], $feeName));
                $feeStatistics[$feeKey] = [
                    'name' => $feeName,
                    'total_amount' => (float) $distributions->sum('amount'),
                    'total_count' => $distributions->count(),
                    'percentage' => $firstDistribution->additionalFee->percentage,
                ];
            }
        }

        return $this->successResponse([
            'transaksi_id' => $transaksi_id,
            'fee_distributions' => $feeDistributions,
            'statistics' => [
                'total_amount' => $totalAmount,
                'total_count' => $feeDistributions->count(),
                'by_additional_fee' => $feeStatistics,
            ],
        ], 'Fee distributions berhasil diambil', 200);
    }

    public function getFeeDistributionByRecipient(Request $request)
    {
        $request->validate([
            'recipient_type' => 'required',
            'recipient_id' => 'required',
        ]);

        $query = FeeDistribution::where('recipient_type', 'like', '%' . $request->recipient_type . '%')
            ->where('recipient_id', $request->recipient_id);

        // Filter berdasarkan rentang tanggal (opsional)
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $this->parseDate($request->start_date)->startOfDay();
            $endDate = $this->parseDate($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $feeDistributions = $query->withRecipient()
            ->with([
                'transaksi.pasien:id,nama,no_rm',
                'additionalFee:id,name,percentage'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalAmount = $feeDistributions->sum('amount');

        // Hitung statistik berdasarkan additional fee
        $feeStatistics = [];
        $groupedByFee = $feeDistributions->groupBy('additional_fee_id');
        foreach ($groupedByFee as $feeId => $distributions) {
            $firstDistribution = $distributions->first();
            if ($firstDistribution && $firstDistribution->additionalFee) {
                $feeName = $firstDistribution->additionalFee->name;
                $feeKey = strtolower(str_replace([' ', ',', '.'], ['_', '', ''], $feeName));
                $feeStatistics[$feeKey] = [
                    'name' => $feeName,
                    'total_amount' => (float) $distributions->sum('amount'),
                    'total_count' => $distributions->count(),
                ];
            }
        }

        return $this->successResponse([
            'recipient_type' => $request->recipient_type,
            'recipient_id' => $request->recipient_id,
            'fee_distributions' => $feeDistributions,
            'statistics' => [
                'total_amount' => $totalAmount,
                'total_count' => $feeDistributions->count(),
                'by_additional_fee' => $feeStatistics,
            ],
        ], 'Fee distributions berhasil diambil', 200);
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

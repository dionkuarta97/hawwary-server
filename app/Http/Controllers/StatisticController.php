<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Models\Tranksaksi;
use App\Models\Pasien;
use App\Models\FeeDistribution;
use App\Models\AddtionalFees;
use Carbon\Carbon;

class StatisticController extends Controller
{
    use ApiResponse;

    public function getStatistic(Request $request)
    {
        // Validasi type parameter
        $request->validate([
            'type' => 'required|in:today,month,year'
        ]);

        $type = $request->type;

        // Tentukan periode berdasarkan type
        $periods = $this->getPeriods($type);

        // Hitung statistik untuk periode current
        $currentStats = $this->calculateStats(
            $periods['current_start'],
            $periods['current_end']
        );

        // Hitung statistik untuk periode previous
        $previousStats = $this->calculateStats(
            $periods['previous_start'],
            $periods['previous_end']
        );

        return $this->successResponse([
            'type' => $type,
            'current' => [
                'period' => [
                    'start' => $periods['current_start']->format('Y-m-d H:i:s'),
                    'end' => $periods['current_end']->format('Y-m-d H:i:s'),
                ],
                'statistics' => $currentStats
            ],
            'previous' => [
                'period' => [
                    'start' => $periods['previous_start']->format('Y-m-d H:i:s'),
                    'end' => $periods['previous_end']->format('Y-m-d H:i:s'),
                ],
                'statistics' => $previousStats
            ]
        ], 'Data statistik berhasil diambil', 200);
    }

    private function getPeriods($type)
    {
        $now = Carbon::now();

        switch ($type) {
            case 'today':
                return [
                    'current_start' => $now->copy()->startOfDay(),
                    'current_end' => $now->copy()->endOfDay(),
                    'previous_start' => $now->copy()->subDay()->startOfDay(),
                    'previous_end' => $now->copy()->subDay()->endOfDay(),
                ];

            case 'month':
                return [
                    'current_start' => $now->copy()->startOfMonth(),
                    'current_end' => $now->copy()->endOfMonth(),
                    'previous_start' => $now->copy()->subMonth()->startOfMonth(),
                    'previous_end' => $now->copy()->subMonth()->endOfMonth(),
                ];

            case 'year':
                return [
                    'current_start' => $now->copy()->startOfYear(),
                    'current_end' => $now->copy()->endOfYear(),
                    'previous_start' => $now->copy()->subYear()->startOfYear(),
                    'previous_end' => $now->copy()->subYear()->endOfYear(),
                ];

            default:
                return [];
        }
    }

    private function calculateStats($startDate, $endDate)
    {
        // Query base untuk transaksi dalam periode
        $baseQuery = function () use ($startDate, $endDate) {
            return Tranksaksi::whereBetween('created_at', [$startDate, $endDate])
                ->whereNull('deleted_at');
        };

        // Mapping status transaksi (Indonesia) ke status operational (Inggris)
        $statusMapping = [
            'sukses' => 'success',
            'pending' => 'pending',
            'gagal' => 'failed',
        ];

        // Function helper untuk hitung stats per status
        $getStatusStats = function ($status = null) use ($baseQuery, $statusMapping, $startDate, $endDate) {
            // Buat query terpisah untuk setiap perhitungan
            $queryForCount = $baseQuery();
            if ($status) {
                $queryForCount->where('status', $status);
            }
            $count = $queryForCount->count();

            $queryForAmount = $baseQuery();
            if ($status) {
                $queryForAmount->where('status', $status);
            }
            $amount = $queryForAmount->sum('total_amount');

            $queryForNetAmount = $baseQuery();
            if ($status) {
                $queryForNetAmount->where('status', $status);
            }
            $netAmount = $queryForNetAmount->sum('net_amount');

            // Hitung modal dari operational yang terkait dengan transaksi
            // Filter berdasarkan created_at transaksi, bukan operational
            $queryForIds = $baseQuery();
            if ($status) {
                $queryForIds->where('status', $status);
            }
            $transaksiIds = $queryForIds->pluck('id');

            $modalQuery = \App\Models\Operational::whereIn('transaksi_id', $transaksiIds)
                ->whereNull('deleted_at')
                ->whereHas('transaksi', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                });

            // Filter modal berdasarkan status transaksi (mapping ke bahasa Inggris)
            if ($status && isset($statusMapping[$status])) {
                $modalQuery->where('status', $statusMapping[$status]);
            }

            $modal = $modalQuery->sum('amount');

            return [
                'count' => $count,
                'amount' => (float) $amount,
                'modal' => (float) $modal,
                'net_amount' => (float) $netAmount,
            ];
        };

        // Hitung jumlah pasien yang dibuat dalam periode
        $pasienCount = Pasien::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->count();

        // Hitung fee distribution berdasarkan additional fee
        // Filter berdasarkan created_at transaksi
        $feeDistributions = FeeDistribution::whereHas('transaksi', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate])
                ->whereNull('deleted_at');
        })
            ->with(['additionalFee' => function ($query) {
                $query->whereNull('deleted_at');
            }])
            ->get()
            ->filter(function ($distribution) {
                // Filter hanya fee distribution yang additional fee-nya tidak dihapus
                return $distribution->additionalFee && $distribution->additionalFee->deleted_at === null;
            })
            ->groupBy('additional_fee_id')
            ->map(function ($distributions, $additionalFeeId) {
                $totalAmount = $distributions->sum('amount');
                $count = $distributions->count();
                $additionalFee = $distributions->first()->additionalFee;

                return [
                    'additional_fee_id' => $additionalFeeId,
                    'additional_fee_name' => $additionalFee ? $additionalFee->name : 'Unknown',
                    'additional_fee_type' => $additionalFee ? $additionalFee->type : null,
                    'count' => $count,
                    'total_amount' => (float) $totalAmount,
                ];
            })
            ->values()
            ->toArray();

        // Hitung operational non-modal (transaksi_id null) berdasarkan status
        $getOperationalStats = function ($status = null) use ($startDate, $endDate) {
            // Query untuk count
            $queryForCount = \App\Models\Operational::whereBetween('created_at', [$startDate, $endDate])
                ->whereNull('deleted_at')
                ->whereNull('transaksi_id');
            if ($status) {
                $queryForCount->where('status', $status);
            }
            $count = $queryForCount->count();

            // Query untuk amount
            $queryForAmount = \App\Models\Operational::whereBetween('created_at', [$startDate, $endDate])
                ->whereNull('deleted_at')
                ->whereNull('transaksi_id');
            if ($status) {
                $queryForAmount->where('status', $status);
            }
            $amount = $queryForAmount->sum('amount');

            return [
                'count' => $count,
                'amount' => (float) $amount,
            ];
        };

        $operationalNonModal = [
            'total' => $getOperationalStats(null),
            'success' => $getOperationalStats('success'),
            'pending' => $getOperationalStats('pending'),
            'failed' => $getOperationalStats('failed'),
        ];

        // Hitung statistik pendapatan
        // Total pendapatan dari transaksi sukses
        $totalPendapatan = Tranksaksi::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->where('status', 'sukses')
            ->sum('total_amount');

        // Total keseluruhan operational (modal + non-modal) yang success
        // Modal: filter berdasarkan created_at transaksi
        $totalOperationalModal = \App\Models\Operational::whereNotNull('transaksi_id')
            ->whereNull('deleted_at')
            ->where('status', 'success')
            ->whereHas('transaksi', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->sum('amount');

        // Non-modal: filter berdasarkan created_at operational
        $totalOperationalNonModal = \App\Models\Operational::whereNull('transaksi_id')
            ->whereNull('deleted_at')
            ->where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $totalOperational = $totalOperationalModal + $totalOperationalNonModal;

        // Pendapatan bersih (pendapatan - operational)
        $pendapatanBersih = $totalPendapatan - $totalOperational;

        $pendapatan = [
            'total_pendapatan' => (float) $totalPendapatan,
            'total_operational' => (float) $totalOperational,
            'pendapatan_bersih' => (float) $pendapatanBersih,
        ];

        return [
            'transaksi' => [
                'total' => $getStatusStats(null),
                'success' => $getStatusStats('sukses'),
                'pending' => $getStatusStats('pending'),
                'failed' => $getStatusStats('gagal'),
            ],
            'pasien' => [
                'count' => $pasienCount
            ],
            'fee_distribution' => $feeDistributions,
            'operational_non_modal' => $operationalNonModal,
            'pendapatan' => $pendapatan
        ];
    }
}

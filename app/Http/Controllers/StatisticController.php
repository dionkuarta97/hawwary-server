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

    public function getTrend(Request $request)
    {
        $request->validate([
            'type' => 'required|in:today,month,year'
        ]);

        $type = $request->type;
        $periods = $this->getPeriods($type);
        $granularity = $this->getTrendGranularity($type);

        $currentSeries = $this->buildTrendSeries(
            $periods['current_start'],
            $periods['current_end'],
            $granularity
        );
        $previousSeries = $this->buildTrendSeries(
            $periods['previous_start'],
            $periods['previous_end'],
            $granularity
        );

        return $this->successResponse([
            'type' => $type,
            'granularity' => $granularity,
            'current' => [
                'period' => [
                    'start' => $periods['current_start']->format('Y-m-d H:i:s'),
                    'end' => $periods['current_end']->format('Y-m-d H:i:s'),
                ],
                'series' => $currentSeries,
            ],
            'previous' => [
                'period' => [
                    'start' => $periods['previous_start']->format('Y-m-d H:i:s'),
                    'end' => $periods['previous_end']->format('Y-m-d H:i:s'),
                ],
                'series' => $previousSeries,
            ],
            'comparison' => $this->mergeTrendComparison($currentSeries, $previousSeries, $granularity),
        ], 'Data tren statistik berhasil diambil', 200);
    }

    private function getTrendGranularity($type)
    {
        switch ($type) {
            case 'today':
                return 'hour';
            case 'month':
                return 'day';
            case 'year':
                return 'month';
            default:
                return 'day';
        }
    }

    private function buildTrendSeries($startDate, $endDate, $granularity)
    {
        $buckets = $this->generateTrendBuckets($startDate, $endDate, $granularity);
        $pendapatanMap = $this->aggregateTransaksiByBucket($startDate, $endDate, $granularity);
        $operationalMap = $this->aggregateOperationalByBucket($startDate, $endDate, $granularity);
        $pasienMap = $this->aggregatePasienByBucket($startDate, $endDate, $granularity);

        $series = [];
        foreach ($buckets as $bucket) {
            $key = $bucket['key'];
            $pendapatan = (float) ($pendapatanMap[$key]['pendapatan'] ?? 0);
            $transaksiCount = (int) ($pendapatanMap[$key]['count'] ?? 0);
            $operational = (float) ($operationalMap[$key] ?? 0);
            $pasienCount = (int) ($pasienMap[$key] ?? 0);

            $series[] = [
                'key' => $key,
                'label' => $bucket['label'],
                'pendapatan' => $pendapatan,
                'operational' => $operational,
                'pendapatan_bersih' => $pendapatan - $operational,
                'transaksi_count' => $transaksiCount,
                'pasien_count' => $pasienCount,
            ];
        }

        return $series;
    }

    private function generateTrendBuckets($startDate, $endDate, $granularity)
    {
        $buckets = [];
        $cursor = $startDate->copy();

        if ($granularity === 'hour') {
            while ($cursor->lte($endDate)) {
                $buckets[] = [
                    'key' => $cursor->format('Y-m-d H'),
                    'label' => $cursor->format('H:00'),
                ];
                $cursor->addHour();
            }
            return $buckets;
        }

        if ($granularity === 'month') {
            while ($cursor->lte($endDate)) {
                $buckets[] = [
                    'key' => $cursor->format('Y-m'),
                    'label' => $cursor->translatedFormat('M'),
                ];
                $cursor->addMonth();
            }
            return $buckets;
        }

        while ($cursor->lte($endDate)) {
            $buckets[] = [
                'key' => $cursor->format('Y-m-d'),
                'label' => $cursor->format('d'),
            ];
            $cursor->addDay();
        }

        return $buckets;
    }

    private function getBucketKeyExpression($granularity, $column = 'created_at')
    {
        switch ($granularity) {
            case 'hour':
                return "DATE_FORMAT({$column}, '%Y-%m-%d %H')";
            case 'month':
                return "DATE_FORMAT({$column}, '%Y-%m')";
            default:
                return "DATE_FORMAT({$column}, '%Y-%m-%d')";
        }
    }

    private function aggregateTransaksiByBucket($startDate, $endDate, $granularity)
    {
        $bucketExpr = $this->getBucketKeyExpression($granularity, 'created_at');

        $rows = Tranksaksi::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->where('status', 'sukses')
            ->selectRaw("{$bucketExpr} as bucket_key, SUM(total_amount) as pendapatan, COUNT(*) as total_count")
            ->groupByRaw($bucketExpr)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->bucket_key] = [
                'pendapatan' => (float) $row->pendapatan,
                'count' => (int) $row->total_count,
            ];
        }

        return $map;
    }

    private function aggregateOperationalByBucket($startDate, $endDate, $granularity)
    {
        $map = [];

        // Non-modal: bucket by operational.created_at
        $nonModalExpr = $this->getBucketKeyExpression($granularity, 'created_at');
        $nonModalRows = \App\Models\Operational::whereNull('transaksi_id')
            ->whereNull('deleted_at')
            ->where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("{$nonModalExpr} as bucket_key, SUM(amount) as total_amount")
            ->groupByRaw($nonModalExpr)
            ->get();

        foreach ($nonModalRows as $row) {
            $map[$row->bucket_key] = ($map[$row->bucket_key] ?? 0) + (float) $row->total_amount;
        }

        // Modal: bucket by transaksi.created_at so it aligns with pendapatan
        $modalExpr = $this->getBucketKeyExpression($granularity, 'transaksis.created_at');
        $modalRows = \App\Models\Operational::query()
            ->join('transaksis', 'operationals.transaksi_id', '=', 'transaksis.id')
            ->whereNotNull('operationals.transaksi_id')
            ->whereNull('operationals.deleted_at')
            ->where('operationals.status', 'success')
            ->whereNull('transaksis.deleted_at')
            ->whereBetween('transaksis.created_at', [$startDate, $endDate])
            ->selectRaw("{$modalExpr} as bucket_key, SUM(operationals.amount) as total_amount")
            ->groupByRaw($modalExpr)
            ->get();

        foreach ($modalRows as $row) {
            $map[$row->bucket_key] = ($map[$row->bucket_key] ?? 0) + (float) $row->total_amount;
        }

        return $map;
    }

    private function aggregatePasienByBucket($startDate, $endDate, $granularity)
    {
        $bucketExpr = $this->getBucketKeyExpression($granularity, 'created_at');

        $rows = Pasien::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->selectRaw("{$bucketExpr} as bucket_key, COUNT(*) as total_count")
            ->groupByRaw($bucketExpr)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->bucket_key] = (int) $row->total_count;
        }

        return $map;
    }

    private function mergeTrendComparison($currentSeries, $previousSeries, $granularity)
    {
        $maxLength = max(count($currentSeries), count($previousSeries));
        $comparison = [];

        for ($i = 0; $i < $maxLength; $i++) {
            $current = $currentSeries[$i] ?? null;
            $previous = $previousSeries[$i] ?? null;

            $label = $current['label'] ?? $previous['label'] ?? (string) ($i + 1);
            if ($granularity === 'hour') {
                $label = $current['label'] ?? $previous['label'] ?? sprintf('%02d:00', $i);
            }

            $comparison[] = [
                'label' => $label,
                'current_pendapatan' => (float) ($current['pendapatan'] ?? 0),
                'previous_pendapatan' => (float) ($previous['pendapatan'] ?? 0),
                'current_pendapatan_bersih' => (float) ($current['pendapatan_bersih'] ?? 0),
                'previous_pendapatan_bersih' => (float) ($previous['pendapatan_bersih'] ?? 0),
                'current_operational' => (float) ($current['operational'] ?? 0),
                'previous_operational' => (float) ($previous['operational'] ?? 0),
                'current_transaksi_count' => (int) ($current['transaksi_count'] ?? 0),
                'previous_transaksi_count' => (int) ($previous['transaksi_count'] ?? 0),
                'current_pasien_count' => (int) ($current['pasien_count'] ?? 0),
                'previous_pasien_count' => (int) ($previous['pasien_count'] ?? 0),
            ];
        }

        return $comparison;
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

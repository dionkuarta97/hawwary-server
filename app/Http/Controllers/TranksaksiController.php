<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Models\Tranksaksi;
use App\Models\Operational;
use Illuminate\Support\Facades\DB;
use App\Models\AddtionalFees;
use App\Models\FeeDistribution;

class TranksaksiController extends Controller
{
    use ApiResponse;

    public function createTranksaksi(Request $request)
    {
        $request->validate([
            'pasien_id' => 'required',
            'docter_id' => 'required',
            'dantel_id' => 'required',
            'total_amount' => 'required',
            'net_amount' => 'required',
            'description' => 'nullable',
            'modal' => 'nullable',
        ]);

        $modal = $request->modal;

        $result = DB::transaction(function () use ($request, $modal) {
            $tranksaksi = Tranksaksi::create([
                'pasien_id' => $request->pasien_id,
                'docter_id' => $request->docter_id,
                'dantel_id' => $request->dantel_id,
                'total_amount' => $request->total_amount,
                'net_amount' => $request->net_amount,
                'description' => $request->description,
            ]);

            if ($modal) {
                Operational::create([
                    'transaksi_id' => $tranksaksi->id,
                    'name' => $modal['name'],
                    'amount' => $modal['amount'],
                    'description' => $modal['description'],
                    'status' => 'pending',
                ]);
            }


            // Load relationships untuk response yang lengkap
            $tranksaksi->load([
                'pasien:id,nama,no_rm,domisili,no_hp',
                'docter:id,name',
                'dantel:id,name'
            ]);

            return [
                "tranksaksi" => $tranksaksi,
                "operational" => $modal ? Operational::where('transaksi_id', $tranksaksi->id)->first() : null
            ];
        });
        return $this->successResponse($result, 'Transaksi berhasil dibuat', 201);
    }

    public function updateTranksaksi(Request $request)
    {
        $tranksaksi = Tranksaksi::with('operational')->find($request->id);
        if (!$tranksaksi) {
            return $this->errorResponse('Transaksi tidak ditemukan', 404);
        }
        if ($tranksaksi->status !== 'pending') {
            return $this->errorResponse('Transaksi tidak dapat diubah status', 400);
        }
        $request->validate([
            'pasien_id' => 'required',
            'docter_id' => 'required',
            'dantel_id' => 'required',
            'total_amount' => 'required',
            'net_amount' => 'required',
            'description' => 'nullable',
            'modal' => 'nullable',
        ]);

        $modal = $request->modal;

        $result = DB::transaction(function () use ($request, $modal, $tranksaksi) {
            $tranksaksi->update([
                'pasien_id' => $request->pasien_id ?? $tranksaksi->pasien_id,
                'docter_id' => $request->docter_id ?? $tranksaksi->docter_id,
                'dantel_id' => $request->dantel_id ?? $tranksaksi->dantel_id,
                'total_amount' => $request->total_amount ?? $tranksaksi->total_amount,
                'net_amount' => $request->net_amount ?? $tranksaksi->net_amount,
                'description' => $request->description ?? $tranksaksi->description,
            ]);

            // ... existing code ...
            if ($modal) {
                if ($tranksaksi->operational()->count() > 0) {
                    // Update operational yang sudah ada
                    $tranksaksi->operational()->update([
                        'name' => $modal['name'] ?? $tranksaksi->operational->first()->name,
                        'amount' => $modal['amount'] ?? $tranksaksi->operational->first()->amount,
                        'description' => $modal['description'] ?? $tranksaksi->operational->first()->description,
                        'status' => 'pending',
                    ]);
                } else {
                    // Buat operational baru jika belum ada
                    Operational::create([
                        'transaksi_id' => $tranksaksi->id,
                        'name' => $modal['name'],
                        'amount' => $modal['amount'],
                        'description' => $modal['description'],
                        'status' => 'pending',
                    ]);
                }
            } else {
                $tranksaksi->operational()->delete();
            }
            // ... existing code ...

            // Load relationships untuk response yang lengkap
            $tranksaksi->load([
                'pasien:id,nama,no_rm,domisili,no_hp',
                'docter:id,name',
                'dantel:id,name'
            ]);

            return [
                "tranksaksi" => $tranksaksi,
                "operational" => $modal ? Operational::where('transaksi_id', $tranksaksi->id)->first() : null
            ];
        });
        return $this->successResponse($result, 'Transaksi berhasil dibuat', 201);
    }

    public function getTransaksi(Request $request)
    {
        $query = Tranksaksi::query();

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

        // Filter berdasarkan search (nama dokter, dantel, pasien, atau description)
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('docter', function ($subQ) use ($searchTerm) {
                    $subQ->where('name', 'like', '%' . $searchTerm . '%');
                })
                    ->orWhereHas('dantel', function ($subQ) use ($searchTerm) {
                        $subQ->where('name', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('pasien', function ($subQ) use ($searchTerm) {
                        $subQ->where('nama', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter berdasarkan nomor RM pasien
        if ($request->has('pasien_no_rm')) {
            $query->whereHas('pasien', function ($q) use ($request) {
                $q->where('no_rm', $request->pasien_no_rm);
            });
        }

        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Order berdasarkan created_at (default desc)
        $order = $request->order ?? 'desc';
        $query->orderBy('created_at', $order);

        // Pagination
        $limit = $request->limit ?? 10;
        $transaksis = $query->with([
            'pasien:id,nama,no_rm,domisili,no_hp,nik,tanggal_lahir,jenis_kelamin',
            'docter:id,name',
            'dantel:id,name',
            'operational'
        ])->whereNull('deleted_at')->paginate($limit);


        return $this->successResponseWithPagination($transaksis, 'Data transaksi berhasil diambil', 200);
    }

    public function updateStatusTransaksi(Request $request, $id)
    {
        $transaksi = Tranksaksi::find($id);
        if (!$transaksi) {
            return $this->errorResponse('Transaksi tidak ditemukan', 404);
        }
        if ($transaksi->status !== 'pending') {
            return $this->errorResponse('Transaksi tidak dapat diubah status', 400);
        }
        DB::beginTransaction();
        try {
            $transaksi->status = $request->status;
            $transaksi->save();
            if ($request->status == 'gagal') {
                // Update status operational menjadi cancelled
                $transaksi->operational()->update(['status' => 'cancelled']);
            }
            if ($request->status == 'sukses') {
                // Update status operational menjadi success
                $transaksi->operational()->update(['status' => 'success']);
                $additionalFees = AddtionalFees::whereNull('deleted_at')->get();
                $feeDistributions = [];

                foreach ($additionalFees as $additionalFee) {
                    $calculatedAmount = ($transaksi->net_amount * $additionalFee->percentage) / 100;

                    $recipientType = strtolower(str_replace([' ', ',', '.'], ['-', '', ''], $additionalFee->name));

                    // Tentukan recipient_id berdasarkan type dari additional_fee
                    $recipientId = null;
                    if ($additionalFee->name === 'docter' || $additionalFee->name === 'dokter') {
                        $recipientId = $transaksi->docter_id;
                    } elseif ($additionalFee->name === 'dantel') {
                        $recipientId = $transaksi->dantel_id;
                    }

                    $feeDistribution = FeeDistribution::create([
                        'transaksi_id' => $transaksi->id,
                        'additional_fee_id' => $additionalFee->id,
                        'recipient_type' => $recipientType,
                        'recipient_id' => $recipientId,
                        'percentage' => $additionalFee->percentage,
                        'amount' => $calculatedAmount,
                    ]);

                    $feeDistributions[] = $feeDistribution;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 400);
        }
        return $this->successResponse(['transaksi' => $transaksi, 'feeDistributions' => $feeDistributions ?? []], 'Status transaksi berhasil diubah', 200);
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

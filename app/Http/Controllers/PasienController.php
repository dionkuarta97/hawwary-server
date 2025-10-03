<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Models\Pasien;

class PasienController extends Controller
{
    //
    use ApiResponse;
    public function createPasien(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'domisili' => 'required',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|string',
        ]);



        $pasien = Pasien::create([
            'no_rm' => $request->no_rm,
            'nama' => $request->nama,
            'domisili' => $request->domisili,
            'tanggal_lahir' => $request->tanggal_lahir,
            'jenis_kelamin' => $request->jenis_kelamin,
            'no_hp' => $request->no_hp,
            'nik' => $request->nik,
        ]);

        return $this->successResponse($pasien, 'Pasien berhasil dibuat', 201);
    }

    public function getPasien(Request $request)
    {
        $search = $request->search ?? '';
        $no_rm = $request->no_rm ?? null;
        $limit = $request->limit ?? 10;

        $query = Pasien::whereNull('deleted_at');

        if (!empty($search)) {
            $query->where('nama', 'like', '%' . $search . '%');
        }
        if ($no_rm !== null) {
            $query->where('no_rm', $no_rm);
        }

        $pasien = $query->paginate($limit);
        return $this->successResponseWithPagination($pasien, 'Pasien berhasil diambil', 200);
    }

    public function updatePasien(Request $request, $id)
    {
        $pasien = Pasien::find($id);
        if (!$pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }
        if ($pasien->deleted_at) {
            return $this->errorResponse('Pasien tidak aktif', 401);
        }
        if ($request->has('nama')) {
            $pasien->nama = $request->nama;
        }
        if ($request->has('domisili')) {
            $pasien->domisili = $request->domisili;
        }
        if ($request->has('tanggal_lahir')) {
            $pasien->tanggal_lahir = $request->tanggal_lahir;
        }
        if ($request->has('jenis_kelamin')) {
            $pasien->jenis_kelamin = $request->jenis_kelamin;
        }
        if ($request->has('no_hp')) {
            $pasien->no_hp = $request->no_hp;
        }
        if ($request->has('nik')) {
            $pasien->nik = $request->nik;
        }
        $pasien->updated_at = now();
        $pasien->save();
        return $this->successResponse($pasien, 'Pasien berhasil diubah', 200);
    }

    public function getPasienById(Request $request, $id)
    {
        $pasien = Pasien::find($id);
        if (!$pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }
        return $this->successResponse($pasien, 'Pasien berhasil diambil', 200);
    }

    public function getNoRm(Request $request)
    {
        // Ambil nomor RM terakhir dari database
        $lastNoRm = Pasien::whereNull('deleted_at')
            ->orderBy('no_rm', 'desc')
            ->value('no_rm');

        // Jika belum ada data, mulai dari 1
        // Jika ada data, tambahkan 1
        $newNoRm = $lastNoRm ? $lastNoRm + 1 : 1;

        // Pastikan nomor RM tidak duplicate
        $maxAttempts = 10; // Maksimal 10 percobaan untuk mencegah infinite loop
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            // Cek apakah nomor RM sudah ada
            $exists = Pasien::where('no_rm', $newNoRm)
                ->whereNull('deleted_at')
                ->exists();

            if (!$exists) {
                // Nomor RM belum ada, aman untuk digunakan
                break;
            }

            // Nomor RM sudah ada, coba nomor berikutnya
            $newNoRm++;
            $attempts++;
        }

        // Jika masih duplicate setelah max attempts, return error
        if ($attempts >= $maxAttempts) {
            return $this->errorResponse('Gagal menghasilkan nomor RM unik', 500);
        }

        return $this->successResponse([
            'no_rm' => $newNoRm
        ], 'Nomor RM berhasil diambil', 200);
    }
}

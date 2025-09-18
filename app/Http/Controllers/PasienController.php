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
        ]);

        // Generate no_rm otomatis berdasarkan ID terakhir + 1
        $lastPasien = Pasien::orderBy('id', 'desc')->first();
        $nextNoRm = $lastPasien ? $lastPasien->id + 1 : 1;

        $pasien = Pasien::create([
            'no_rm' => $nextNoRm,
            'nama' => $request->nama,
            'domisili' => $request->domisili,
            'tanggal_lahir' => $request->tanggal_lahir,
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
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Models\Docter;

class DocterController extends Controller
{

    use ApiResponse;
    public function createDocter(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $docter = Docter::create([
            'name' => $request->name,
        ]);
        return $this->successResponse($docter, 'Dokter berhasil dibuat', 201);
    }

    public function getDocter(Request $request)
    {
        $search = $request->search ?? '';
        $limit = $request->limit ?? 10;
        $docter = Docter::where('name', 'like', '%' . $search . '%')->whereNull('deleted_at')->paginate($limit);
        return $this->successResponseWithPagination($docter, 'Dokter berhasil diambil', 200);
    }

    public function updateDocter(Request $request, $id)
    {
        $docter = Docter::find($id);
        if (!$docter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }
        if ($docter->deleted_at) {
            return $this->errorResponse('Dokter tidak aktif', 401);
        }
        if ($request->has('name')) {
            $docter->name = $request->name;
        }
        $docter->updated_at = now();
        $docter->save();
        return $this->successResponse($docter, 'Dokter berhasil diubah', 200);
    }

    public function softDeleteDocter(Request $request, $id)
    {
        $docter = Docter::find($id);
        if (!$docter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }
        if ($docter->deleted_at) {
            return $this->errorResponse('Dokter tidak aktif', 401);
        }
        $docter->deleted_at = now();
        $docter->save();
        return $this->successResponse($docter, 'Dokter berhasil dihapus', 200);
    }
}

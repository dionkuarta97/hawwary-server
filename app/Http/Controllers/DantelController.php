<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Models\Dantel;

class DantelController extends Controller
{

    use ApiResponse;
    public function createDantel(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $dantel = Dantel::create([
            'name' => $request->name,
        ]);
        return $this->successResponse($dantel, 'Dantel berhasil dibuat', 201);
    }
    public function getDantel(Request $request)
    {
        $search = $request->search ?? '';
        $limit = $request->limit ?? 10;
        $dantel = Dantel::where('name', 'like', '%' . $search . '%')->whereNull('deleted_at')->paginate($limit);
        return $this->successResponseWithPagination($dantel, 'Dantel berhasil diambil', 200);
    }
    public function updateDantel(Request $request, $id)
    {
        $dantel = Dantel::find($id);
        if (!$dantel) {
            return $this->errorResponse('Dantel tidak ditemukan', 404);
        }
        if ($dantel->deleted_at) {
            return $this->errorResponse('Dantel tidak aktif', 401);
        }
        if ($request->has('name')) {
            $dantel->name = $request->name;
        }
        $dantel->updated_at = now();
        $dantel->save();
        return $this->successResponse($dantel, 'Dantel berhasil diubah', 200);
    }
    public function softDeleteDantel(Request $request, $id)
    {
        $dantel = Dantel::find($id);
        if (!$dantel) {
            return $this->errorResponse('Dantel tidak ditemukan', 404);
        }
        if ($dantel->deleted_at) {
            return $this->errorResponse('Dantel tidak aktif', 401);
        }
        $dantel->deleted_at = now();
        $dantel->save();
        return $this->successResponse($dantel, 'Dantel berhasil dihapus', 200);
    }
}

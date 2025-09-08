<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Models\AddtionalFees;

class AddtionalFeesController extends Controller
{
    use ApiResponse;
    public function createAddtionalFees(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'percentage' => 'required',
        ]);
        $addtionalFees = AddtionalFees::create([
            'name' => $request->name,
            'percentage' => $request->percentage,
        ]);
        return $this->successResponse($addtionalFees, 'Addtional Fees berhasil dibuat', 201);
    }
    public function getAddtionalFees(Request $request)
    {
        $search = $request->search ?? '';
        $limit = $request->limit ?? 10;
        $addtionalFees = AddtionalFees::where('name', 'like', '%' . $search . '%')->whereNull('deleted_at')->paginate($limit);
        return $this->successResponseWithPagination($addtionalFees, 'Addtional Fees berhasil diambil', 200);
    }
    public function updateAddtionalFees(Request $request, $id)
    {
        $addtionalFees = AddtionalFees::find($id);
        if (!$addtionalFees) {
            return $this->errorResponse('Addtional Fees tidak ditemukan', 404);
        }
        if ($addtionalFees->deleted_at) {
            return $this->errorResponse('Addtional Fees tidak aktif', 401);
        }
        if ($request->has('name')) {
            $addtionalFees->name = $request->name;
        }
        if ($request->has('percentage')) {
            $addtionalFees->percentage = $request->percentage;
        }
        $addtionalFees->updated_at = now();
        $addtionalFees->save();
        return $this->successResponse($addtionalFees, 'Addtional Fees berhasil diubah', 200);
    }
    public function softDeleteAddtionalFees(Request $request, $id)
    {
        $addtionalFees = AddtionalFees::find($id);
        if (!$addtionalFees) {
            return $this->errorResponse('Addtional Fees tidak ditemukan', 404);
        }
        if ($addtionalFees->deleted_at) {
            return $this->errorResponse('Addtional Fees tidak aktif', 401);
        }
        $addtionalFees->deleted_at = now();
        $addtionalFees->save();
        return $this->successResponse($addtionalFees, 'Addtional Fees berhasil dihapus', 200);
    }
}

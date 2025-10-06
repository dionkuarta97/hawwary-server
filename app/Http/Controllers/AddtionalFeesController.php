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
            'percentage' => 'required|numeric|min:0|max:100',
        ]);

        // Hitung total persentase yang sudah ada (tidak termasuk yang dihapus)
        $totalPercentage = AddtionalFees::whereNull('deleted_at')->sum('percentage');

        // Cek apakah total persentase akan melebihi 100%
        if (($totalPercentage + $request->percentage) > 100) {
            return $this->errorResponse('Total persentase tidak boleh melebihi 100%. Persentase saat ini: ' . $totalPercentage . '%', 400);
        }

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
        $percentage = AddtionalFees::whereNull('deleted_at')->sum('percentage');
        return $this->successResponseWithPagination([
            'data' => $addtionalFees->items(),
            'current_page' => $addtionalFees->currentPage(),
            'per_page' => $addtionalFees->perPage(),
            'total' => $addtionalFees->total(),
            'last_page' => $addtionalFees->lastPage(),
            'from' => $addtionalFees->firstItem(),
            'to' => $addtionalFees->lastItem(),
            'statistics' => [
                'total_percentage' => $percentage,
            ]
        ], 'Addtional Fees berhasil diambil', 200);
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

        // Validasi persentase jika ada
        if ($request->has('percentage')) {
            $request->validate([
                'percentage' => 'numeric|min:0|max:100',
            ]);

            // Hitung total persentase yang sudah ada (tidak termasuk yang dihapus dan record yang sedang diupdate)
            $totalPercentage = AddtionalFees::whereNull('deleted_at')
                ->where('id', '!=', $id)
                ->sum('percentage');

            // Cek apakah total persentase akan melebihi 100%
            if (($totalPercentage + $request->percentage) > 100) {
                return $this->errorResponse('Total persentase tidak boleh melebihi 100%. Persentase saat ini: ' . $totalPercentage . '%', 400);
            }
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

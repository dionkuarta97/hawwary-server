<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Traits\ApiResponse;

class UserController extends Controller
{
    //
    use ApiResponse;
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return $this->errorResponse('username atau password tidak cocok', 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->errorResponse('username atau password tidak cocok', 401);
        }

        if ($user->deleted_at) {
            return $this->errorResponse('User tidak aktif', 401);
        }

        // Delete semua token lama (single device login)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse(['user' => $user, 'token' => $token]);
    }

    public function changePassword(Request $request, $id)
    {
        $request->validate([

            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan', 404);
        }
        if ($user->deleted_at) {
            return $this->errorResponse('User tidak aktif', 401);
        }


        // Update password dan set waktu perubahan
        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
        ]);

        // Revoke semua token lama
        $user->tokens()->delete();

        return $this->successResponse('Password berhasil diubah. Silakan login ulang.');
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return $this->successResponse('Berhasil logout');
    }

    public function createStaff(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required',
            'password' => 'required',
        ]);
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'staff',
        ]);
        return $this->successResponse('Staff berhasil dibuat');
    }

    public function getStaff(Request $request)
    {
        $limit = $request->limit ?? 10;
        $search = $request->search ?? '';
        $staff = User::where('role', 'staff')
            ->whereNull('deleted_at')
            ->where('name', 'like', '%' . $search . '%')
            ->paginate($limit);
        return $this->successResponseWithPagination($staff);
    }

    public function softDeleteStaff(Request $request, $id)
    {
        $staff = User::find($id);
        if (!$staff) {
            return $this->errorResponse('Staff tidak ditemukan', 404);
        }
        if ($staff->deleted_at) {
            return $this->errorResponse('Staff tidak aktif', 401);
        }
        $staff->deleted_at = now();
        $staff->save();
        return $this->successResponse('Staff berhasil dihapus');
    }
    public function updateStaff(Request $request, $id)
    {
        $staff = User::find($id);
        if (!$staff) {
            return $this->errorResponse('Staff tidak ditemukan', 404);
        }
        if ($staff->deleted_at) {
            return $this->errorResponse('Staff tidak aktif', 401);
        }

        // Update hanya field yang ada di request
        if ($request->has('name')) {
            $staff->name = $request->name;
        }
        if ($request->has('username')) {
            $staff->username = $request->username;
        }


        $staff->updated_at = now();
        $staff->save();
        return $this->successResponse('Staff berhasil diubah');
    }
}

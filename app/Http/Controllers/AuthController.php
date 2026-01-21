<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi Input: Wajib Username & Password
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // 2. Cari User Berdasarkan Username
        $user = User::where('username', $request->username)->first();

        // 3. Cek Status Akun (Hanya jika user ditemukan)
        if ($user) {
            // Cek apakah akun aktif
            if (!$user->is_active) {
                // Audit Log Login Ditolak (Akun Nonaktif) - Opsional
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'LOGIN_FAILED',
                    'details' => 'Login attempt on inactive account',
                    'ip_address' => $request->ip(),
                ]);

                throw ValidationException::withMessages([
                    'username' => ['Akun Anda dinonaktifkan. Silakan hubungi administrator.'],
                ]);
            }

            // Cek apakah akun sedang terkunci (Rate Limiting)
            if ($user->locked_until && $user->locked_until->isFuture()) {
                $unlockTime = $user->locked_until->timezone('Asia/Makassar')->format('H:i:s'); // Sesuaikan timezone jika perlu

                throw ValidationException::withMessages([
                    'username' => ["Akun terkunci sementara karena terlalu banyak percobaan gagal. Coba lagi setelah pukul $unlockTime WITA."],
                ]);
            }
        }

        // 4. Validasi Kredensial (Password Check)
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Jika user ada tapi password salah, kita catat kegagalannya
            if ($user) {
                $user->increment('failed_login_attempts');

                // Kunci akun jika gagal 5 kali berturut-turut
                if ($user->failed_login_attempts >= 5) {
                    $user->update([
                        'locked_until' => now()->addMinutes(15),
                        'failed_login_attempts' => 0 // Reset counter saat dikunci agar bersih setelah masa hukuman habis
                    ]);

                    $details = 'Account locked due to 5 failed login attempts';
                } else {
                    $details = 'Failed login attempt: Incorrect password';
                }

                // Audit Log Gagal
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'LOGIN_FAILED',
                    'details' => $details,
                    'ip_address' => $request->ip(),
                ]);
            }

            // Return error umum untuk keamanan (agar tidak membocorkan apakah username ada/tidak)
            throw ValidationException::withMessages([
                'username' => ['Username atau password yang Anda masukkan salah.'],
            ]);
        }

        // 5. Login Berhasil: Reset semua counter keamanan
        if ($user->failed_login_attempts > 0 || $user->locked_until) {
            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);
        }

        // 6. Buat Token Akses
        $token = $user->createToken('auth_token')->plainTextToken;

        // 7. Audit Log Sukses
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'LOGIN_SUCCESS',
            'details' => 'User logged in successfully via Username',
            'ip_address' => $request->ip(),
        ]);

        // 8. Kembalikan Response
        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->fresh()), // Menggunakan Resource agar format data konsisten
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}

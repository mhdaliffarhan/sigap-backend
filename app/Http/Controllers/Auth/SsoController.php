<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;                    // <--- PENTING: Import Model User
use Illuminate\Http\Request;            // <--- PENTING: Import Request
use Illuminate\Support\Facades\Http;    // <--- PENTING: Import Http Facade
use Illuminate\Support\Str;             // <--- PENTING: Import Str Helper
use Illuminate\Support\Facades\Auth;    // <--- PENTING: Import Auth

class SsoController extends Controller
{
  // 1. Generate URL untuk Tombol "Login SSO" di React
  public function getLoginUrl()
  {
    // Generate State Random
    $state = Str::random(40);

    // Build Query String
    $query = http_build_query([
      'client_id' => env('SSO_CLIENT_ID'),
      'redirect_uri' => env('SSO_REDIRECT_URI'),
      'response_type' => 'code',
      'scope' => '',
      'state' => $state,
    ]);

    // Return JSON berisi URL redirect
    return response()->json([
      'url' => env('SSO_BASE_URL') . '/oauth/authorize?' . $query
    ]);
  }

  // 2. Proses Callback (Ditembak oleh React membawa 'code')
  public function handleCallback(Request $request)
  {
    // Validasi Input
    $request->validate(['code' => 'required']);

    // A. Tukar Authorization Code dengan Access Token ke SSO
    try {
      $response = Http::post(env('SSO_BASE_URL') . '/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        'redirect_uri' => env('SSO_REDIRECT_URI'),
        'code' => $request->code,
      ]);

      $tokenData = $response->json();

      if (!isset($tokenData['access_token'])) {
        return response()->json([
          'message' => 'Gagal autentikasi ke SSO',
          'debug' => $tokenData // Opsional: untuk cek error
        ], 401);
      }

      // B. Ambil Data User dari SSO menggunakan Token tadi
      $userResponse = Http::withHeaders([
        'Authorization' => 'Bearer ' . $tokenData['access_token'],
        'Accept' => 'application/json',
      ])->get(env('SSO_BASE_URL') . '/api/me');

      $userData = $userResponse->json();

      // Cek struktur JSON dari SSO (Sesuaikan dengan output /api/me Anda)
      // Asumsi output SSO: { "user": { "id": 1, "email": "...", "pegawai": {...} } }
      $ssoUser = $userData['user'] ?? $userData;

      if (!$ssoUser) {
        return response()->json(['message' => 'Gagal mengambil profil user dari SSO'], 400);
      }

      $email = $ssoUser['email'];
      $username = $ssoUser['username'];

      // C. Cari atau Buat User di Database SIGAP
      $localUser = User::where('email', $email)->orWhere('username', $username)->first();

      if (!$localUser) {
        // Register user baru otomatis (Auto-Provisioning)
        // Pastikan struktur database SIGAP mendukung field ini
        $localUser = User::create([
          'name' => $ssoUser['pegawai']['nama_lengkap'] ?? $username, // Ambil nama pegawai jika ada
          'email' => $email,
          'username' => $username,
          'password' => bcrypt(Str::random(16)), // Password acak karena login via SSO
          // 'role' => 'user', // Default role (sesuaikan dengan sistem Anda)
        ]);
      }

      // D. Buat Token Login SIGAP (Sanctum)
      // Ini token untuk Frontend React SIGAP bicara dengan Backend SIGAP
      $token = $localUser->createToken('sigap-token')->plainTextToken;

      return response()->json([
        'token' => $token,
        'user' => $localUser,
        'message' => 'Login Berhasil via SSO'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Terjadi kesalahan server',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}

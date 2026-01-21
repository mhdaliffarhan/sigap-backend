<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;

class SsoController extends Controller
{
  // 1. Generate URL untuk Frontend
  public function getLoginUrl()
  {
    $state = Str::random(40);

    $query = http_build_query([
      'client_id' => env('SSO_CLIENT_ID'),
      'redirect_uri' => env('SSO_REDIRECT_URI'),
      'response_type' => 'code',
      'scope' => '',
      'state' => $state,
    ]);

    return response()->json([
      'url' => env('SSO_BASE_URL') . '/oauth/authorize?' . $query
    ]);
  }

  // 2. Callback Handler
  public function handleCallback(Request $request)
  {
    $request->validate(['code' => 'required']);

    try {
      // A. Tukar Code jadi Token ke SSO
      $response = Http::post(env('SSO_BASE_URL') . '/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        'redirect_uri' => env('SSO_REDIRECT_URI'),
        'code' => $request->code,
      ]);

      $tokenData = $response->json();

      if (!isset($tokenData['access_token'])) {
        // Audit Log Gagal (Token)
        AuditLog::create([
          'user_id' => null, // Belum login
          'action' => 'LOGIN_SSO_FAILED',
          'details' => 'Failed to exchange token with SSO provider',
          'ip_address' => $request->ip(),
        ]);

        return response()->json([
          'message' => 'Gagal validasi ke SSO Server',
          'details' => $tokenData
        ], 401);
      }

      // B. Ambil Profil User dari SSO
      $userResponse = Http::withHeaders([
        'Authorization' => 'Bearer ' . $tokenData['access_token'],
        'Accept' => 'application/json',
      ])->get(env('SSO_BASE_URL') . '/api/me');

      $userData = $userResponse->json();

      // Adaptasi struktur JSON dari SSO (biasanya dibungkus 'user' atau 'data')
      $ssoUser = $userData['user'] ?? $userData;

      if (!$ssoUser || (!isset($ssoUser['email']) && !isset($ssoUser['username']))) {
        return response()->json(['message' => 'Data user tidak valid dari SSO'], 400);
      }

      // Ambil data kunci
      $username = $ssoUser['username']; // Wajib ada di SSO (NIP/Username)
      $email = $ssoUser['email'];
      $name = $ssoUser['pegawai']['nama_lengkap'] ?? $ssoUser['name'] ?? $username;

      // C. LOGIKA PENCARIAN USER (Prioritas Username)
      // 1. Cari pakai Username
      $localUser = User::where('username', $username)->first();

      // 2. Jika tidak ketemu, cari pakai Email (Fallback User Lama)
      if (!$localUser && $email) {
        $localUser = User::where('email', $email)->first();

        // Jika ketemu user lama via email, update username-nya biar ke depan bisa login via username
        if ($localUser && empty($localUser->username)) {
          $localUser->update(['username' => $username]);
        }
      }

      // 3. Jika masih tidak ketemu, Register Baru (Auto-Provisioning)
      if (!$localUser) {
        $localUser = User::create([
          'name' => $name,
          'username' => $username,
          'email' => $email,
          'password' => Hash::make(Str::random(20)), // Password acak
          'role' => 'pegawai', // Default role
          'is_active' => true,
        ]);

        // Log Register Baru
        AuditLog::create([
          'user_id' => $localUser->id,
          'action' => 'USER_REGISTERED_SSO',
          'details' => 'New user auto-registered via SSO',
          'ip_address' => $request->ip(),
        ]);
      }

      // Cek status akun
      if (!$localUser->is_active) {
        AuditLog::create([
          'user_id' => $localUser->id,
          'action' => 'LOGIN_SSO_BLOCKED',
          'details' => 'Login attempt on inactive account via SSO',
          'ip_address' => $request->ip(),
        ]);
        return response()->json(['message' => 'Akun Anda dinonaktifkan.'], 403);
      }

      // D. Generate Token Sanctum untuk SIGAP
      // $localUser->tokens()->delete(); 
      $token = $localUser->createToken('sso-login')->plainTextToken;

      // E. Audit Log Sukses
      AuditLog::create([
        'user_id' => $localUser->id,
        'action' => 'LOGIN_SSO_SUCCESS',
        'details' => 'User logged in successfully via SSO',
        'ip_address' => $request->ip(),
      ]);

      return response()->json([
        'message' => 'Login Berhasil',
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => new UserResource($localUser->fresh()),
      ]);
    } catch (\Exception $e) {
      // Log Error Sistem
      \Illuminate\Support\Facades\Log::error('SSO Login Error: ' . $e->getMessage());

      return response()->json([
        'message' => 'Terjadi kesalahan Internal Server',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}

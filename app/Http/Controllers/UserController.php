<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role; // Pastikan model Role diimport
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\AuditLog;
use App\Mail\NewUserMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Get all users (paginated)
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role (Dynamic Check in JSON or String column)
        if ($request->has('role')) {
            $role = $request->role;
            $query->where(function ($q) use ($role) {
                // Cek di kolom single role
                $q->where('role', $role)
                    // ATAU cek di dalam JSON array roles
                    ->orWhereJsonContains('roles', $role);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Search by name, email or NIP (privacy-aware)
        if ($request->has('search') && $request->search != '') {
            $search = trim((string) $request->search);

            // Require minimum 3 characters for privacy
            if (mb_strlen($search) < 3) {
                // Return empty pagination instead of error
                return UserResource::collection(User::whereRaw('0=1')->paginate($request->get('per_page', 15)));
            }

            // Jika role tidak spesifik diminta, default cari di pegawai saja (privacy)
            // KECUALI yang login adalah super_admin/admin
            $user = auth()->user();
            /*
            // Opsional: Batasi pencarian jika bukan admin
            if (!$request->has('role') && !$user->hasRole('super_admin')) {
                 $query->whereJsonContains('roles', 'pegawai');
            }
            */

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('username', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('nip', 'like', "%$search%");
            });
        }

        $users = $query->latest()->paginate($request->get('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Get single user by ID
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Create new user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            // 'username' => 'required|string|unique:users,username', // Optional jika auto-generate dari email/nip
            'password' => 'required|string|min:8',
            'nip' => 'nullable|string|unique:users,nip', // NIP bisa nullable untuk user non-PNS
            'jabatan' => 'nullable|string|max:255',
            'unit_kerja' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',

            // Validasi Role Dinamis (Cek ke tabel roles)
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,code',

            'is_active' => 'boolean',
        ]);

        // Generate username if not provided (from email prefix)
        $username = $request->username ?? explode('@', $validated['email'])[0];

        // Store plain password for email
        $plainPassword = $validated['password'];

        // Determine Primary Role
        // Kita ambil role pertama yang dipilih sebagai primary role
        $primaryRole = $validated['roles'][0] ?? 'pegawai';

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $username,
            'password' => Hash::make($plainPassword),
            'nip' => $request->nip,
            'jabatan' => $request->jabatan,
            'unit_kerja' => $request->unit_kerja,
            'phone' => $request->phone,
            'role' => $primaryRole, // String column
            'roles' => $validated['roles'], // JSON/Array column
            'is_active' => $request->input('is_active', true),
        ]);

        // Send welcome email
        try {
            // Pastikan Mail Config sudah benar di .env
            // Mail::to($user->email)->send(new NewUserMail($user, $plainPassword));
        } catch (\Exception $e) {
            Log::error('Failed to send new user email: ' . $e->getMessage());
        }

        // Audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'USER_CREATED',
            'details' => "User created: {$user->name} ({$user->email}) with roles: " . implode(',', $validated['roles']),
            'ip_address' => request()->ip(),
        ]);

        return response()->json(['message' => 'User berhasil dibuat', 'data' => new UserResource($user)], 201);
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'username' => 'string|unique:users,username,' . $user->id,
            'email' => 'email|unique:users,email,' . $user->id,
            'nip' => 'nullable|string|unique:users,nip,' . $user->id,
            'jabatan' => 'nullable|string|max:255',
            'unit_kerja' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',

            // Validasi Role Dinamis
            'roles' => 'sometimes|array',
            'roles.*' => 'exists:roles,code',

            'is_active' => 'boolean',
            'password' => 'nullable|string|min:8',
        ]);

        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Update Roles Logic
        if (isset($validated['roles'])) {
            // Update primary role juga jika roles berubah
            // Ambil role pertama sebagai primary
            $validated['role'] = $validated['roles'][0] ?? $user->role;
        }

        $user->update($validated);

        // Audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'USER_UPDATED',
            'details' => "User updated: {$user->name} ({$user->email})",
            'ip_address' => request()->ip(),
        ]);

        return new UserResource($user);
    }

    /**
     * Delete user
     */
    public function destroy(User $user)
    {
        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Anda tidak dapat menghapus akun sendiri'], 403);
        }

        $userData = "{$user->name} ({$user->email})";
        $user->delete();

        // Audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'USER_DELETED',
            'details' => "User deleted: {$userData}",
            'ip_address' => request()->ip(),
        ]);

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Update user roles only (Short-cut)
     */
    public function updateRoles(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,code', // Dynamic Validation
        ]);

        $user->roles = $validated['roles'];

        // Update primary role to the first one
        $user->role = $validated['roles'][0];

        $user->save();

        // Audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'USER_ROLES_UPDATED',
            'details' => "Roles updated for {$user->name}: " . implode(', ', $validated['roles']),
            'ip_address' => request()->ip(),
        ]);

        return new UserResource($user);
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Update current user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username,' . $user->id,
            'nip' => 'nullable|string|unique:users,nip,' . $user->id,
            'jabatan' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            // Unit kerja biasanya tidak diubah sendiri
        ]);

        $user->update($validated);

        // Audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'PROFILE_UPDATED',
            'details' => "User updated their profile: {$user->name}",
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Change user active role (Session Switch)
     */
    public function changeRole(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'role' => 'required|string|exists:roles,code', // Validasi ke DB
        ]);

        $requestedRole = $validated['role'];

        // Ensure the requested role is in the user's assigned roles
        // Handle array casting
        $userRoles = is_string($user->roles) ? json_decode($user->roles, true) : $user->roles;

        if (!in_array($requestedRole, $userRoles)) {
            throw ValidationException::withMessages([
                'role' => ['Anda tidak memiliki hak akses untuk role ini.'],
            ]);
        }

        // Update primary 'role' column to switch context
        $user->role = $requestedRole;
        $user->save();

        // Audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'ROLE_SWITCHED',
            'details' => "User switched active role to: {$requestedRole}",
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Role berhasil diubah',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',      // at least one lowercase letter
                'regex:/[A-Z]/',      // at least one uppercase letter
                'regex:/[0-9]/',      // at least one number
                // 'regex:/[@$!%*#?&]/', // (Optional) at least one special character
                'different:current_password',
            ],
            'new_password_confirmation' => 'required|same:new_password',
        ], [
            'new_password.regex' => 'Password harus mengandung huruf besar, huruf kecil, dan angka',
            'new_password.different' => 'Password baru harus berbeda dengan password lama',
            'new_password_confirmation.same' => 'Konfirmasi password tidak cocok',
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak sesuai'],
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'PASSWORD_CHANGED',
            'details' => "User changed their password",
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah',
        ]);
    }

    /**
     * Upload user avatar
     */
    public function uploadAvatar(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png|max:2048', // max 2MB
        ]);

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
                \Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');

            // Update user
            $user->update([
                'avatar' => $path,
            ]);

            return response()->json(new UserResource($user->fresh()), 200);
        }

        return response()->json(['message' => 'Tidak ada file yang diupload'], 400);
    }

    /**
     * Bulk update users (for admin)
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'is_active' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,code', // Dynamic Role Validation
        ]);

        $updates = [];

        if (isset($validated['is_active'])) {
            $updates['is_active'] = $validated['is_active'];
        }

        if (isset($validated['roles'])) {
            $updates['roles'] = $validated['roles'];
            // Jika update role massal, reset primary role ke yang pertama di list
            $updates['role'] = $validated['roles'][0];
        }

        if (empty($updates)) {
            return response()->json(['message' => 'Tidak ada data yang diupdate'], 422);
        }

        // Loop update manual karena 'roles' adalah JSON casting
        // User::whereIn('id', $validated['user_ids'])->update($updates); <- Ini bisa error untuk JSON di DB lama

        $users = User::whereIn('id', $validated['user_ids'])->get();
        foreach ($users as $u) {
            $u->update($updates);
        }

        // Audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'USERS_BULK_UPDATED',
            'details' => 'Bulk updated ' . count($validated['user_ids']) . ' users',
            'ip_address' => request()->ip(),
        ]);

        return response()->json(['message' => 'Users updated successfully']);
    }
}

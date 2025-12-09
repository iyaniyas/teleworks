<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List semua user (admin, company, job_seeker).
     */
    public function index(Request $request)
    {
        $role  = $request->get('role');
        $q     = $request->get('q');

        $users = User::with(['company', 'companies', 'profile', 'roles'])
            ->when($role, function ($query) use ($role) {
                // filter berdasarkan role (spatie)
                $query->role($role);
            })
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $availableRoles = ['admin', 'company', 'job_seeker'];

        return view('admin.users.index', compact('users', 'availableRoles', 'role', 'q'));
    }

    /**
     * Form edit user.
     */
    public function edit(User $user)
    {
        $user->load(['company', 'companies', 'profile', 'resumes', 'roles']);

        $availableRoles = ['admin', 'company', 'job_seeker'];
        $currentRole = $user->getRoleNames()->first();

        // Semua company untuk dropdown (kalau mau assign company_id)
        $companies = Company::orderBy('name')->get(['id','name']);

        return view('admin.users.edit', compact('user', 'availableRoles', 'currentRole', 'companies'));
    }

    /**
     * Update data user.
     */
    public function update(Request $request, User $user)
    {
        $availableRoles = ['admin', 'company', 'job_seeker'];

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in($availableRoles)],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->company_id = $validated['company_id'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = $validated['password']; // sudah di-hash via cast
        }

        $user->save();

        // Update role (spatie)
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User berhasil diperbarui.');
    }

    /**
     * Hapus user.
     */
    public function destroy(User $user)
    {
        // Jangan kasih admin bunuh dirinya sendiri
        if (auth()->id() === $user->id) {
            return redirect()
                ->back()
                ->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        // Bisa kamu tambahkan cek lain: misal jangan hapus owner tertentu, dll
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User berhasil dihapus.');
    }
}


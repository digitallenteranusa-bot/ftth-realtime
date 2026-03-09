<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RolePermissionController extends Controller
{
    public function index()
    {
        $permissions = RolePermission::getAllGrouped();
        $labels = RolePermission::permissionLabels();

        // Group by category
        $groups = [];
        foreach ($labels as $perm => $info) {
            $groups[$info['group']][$perm] = [
                'label' => $info['label'],
                'desc' => $info['desc'],
                'admin' => $permissions['admin'][$perm] ?? true,
                'operator' => $permissions['operator'][$perm] ?? false,
                'teknisi' => $permissions['teknisi'][$perm] ?? false,
            ];
        }

        return Inertia::render('RolePermission/Index', [
            'groups' => $groups,
            'roles' => ['admin', 'operator', 'teknisi'],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.role' => 'required|in:admin,operator,teknisi',
            'permissions.*.permission' => 'required|string|max:100',
            'permissions.*.allowed' => 'required|boolean',
        ]);

        foreach ($validated['permissions'] as $item) {
            // Admin always has all permissions, skip
            if ($item['role'] === 'admin') continue;

            RolePermission::updateOrCreate(
                ['role' => $item['role'], 'permission' => $item['permission']],
                ['allowed' => $item['allowed']]
            );
        }

        RolePermission::clearCache();

        return back()->with('success', 'Pengaturan role berhasil disimpan.');
    }
}

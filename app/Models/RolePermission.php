<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission', 'allowed'];

    protected function casts(): array
    {
        return ['allowed' => 'boolean'];
    }

    /**
     * Check if a role has a specific permission
     */
    public static function hasPermission(string $role, string $permission): bool
    {
        // Admin always has all permissions
        if ($role === 'admin') return true;

        $permissions = self::getPermissionsForRole($role);
        return $permissions[$permission] ?? false;
    }

    /**
     * Get all permissions for a role (cached)
     */
    public static function getPermissionsForRole(string $role): array
    {
        return Cache::remember("role_permissions.{$role}", 300, function () use ($role) {
            return self::where('role', $role)
                ->pluck('allowed', 'permission')
                ->toArray();
        });
    }

    /**
     * Get all permissions grouped by role
     */
    public static function getAllGrouped(): array
    {
        $all = self::all();
        $grouped = [];

        foreach ($all as $rp) {
            $grouped[$rp->role][$rp->permission] = $rp->allowed;
        }

        return $grouped;
    }

    /**
     * Clear cached permissions
     */
    public static function clearCache(): void
    {
        foreach (['admin', 'operator', 'teknisi'] as $role) {
            Cache::forget("role_permissions.{$role}");
        }
    }

    /**
     * Permission labels in Indonesian
     */
    public static function permissionLabels(): array
    {
        return [
            'dashboard.view' => ['label' => 'Dashboard', 'group' => 'Umum', 'desc' => 'Lihat halaman dashboard'],
            'map.view' => ['label' => 'Peta', 'group' => 'Umum', 'desc' => 'Lihat peta jaringan'],
            'nearby.view' => ['label' => 'Nearby Search', 'group' => 'Umum', 'desc' => 'Cari ODP & pelanggan terdekat (GPS)'],
            'export.use' => ['label' => 'Export Data', 'group' => 'Umum', 'desc' => 'Export CSV/PDF'],

            'mikrotik.view' => ['label' => 'Lihat Mikrotik', 'group' => 'Mikrotik', 'desc' => 'Lihat daftar & detail Mikrotik'],
            'mikrotik.manage' => ['label' => 'Kelola Mikrotik', 'group' => 'Mikrotik', 'desc' => 'Tambah, edit, hapus Mikrotik'],

            'olt.view' => ['label' => 'Lihat OLT', 'group' => 'OLT', 'desc' => 'Lihat daftar & detail OLT'],
            'olt.manage' => ['label' => 'Kelola OLT', 'group' => 'OLT', 'desc' => 'Tambah, edit, hapus OLT & PON Port'],
            'olt.sync_signal' => ['label' => 'Sync Signal ONU', 'group' => 'OLT', 'desc' => 'Query & sync signal Rx/Tx dari OLT'],

            'odc.view' => ['label' => 'Lihat ODC', 'group' => 'Infrastruktur', 'desc' => 'Lihat daftar & detail ODC'],
            'odc.manage' => ['label' => 'Kelola ODC', 'group' => 'Infrastruktur', 'desc' => 'Tambah, edit, hapus ODC'],
            'odp.view' => ['label' => 'Lihat ODP', 'group' => 'Infrastruktur', 'desc' => 'Lihat daftar & detail ODP'],
            'odp.manage' => ['label' => 'Kelola ODP', 'group' => 'Infrastruktur', 'desc' => 'Tambah, edit, hapus ODP'],

            'ont.view' => ['label' => 'Lihat ONT', 'group' => 'Pelanggan', 'desc' => 'Lihat daftar & detail ONT'],
            'ont.manage' => ['label' => 'Kelola ONT', 'group' => 'Pelanggan', 'desc' => 'Tambah, edit, hapus ONT'],
            'customer.view' => ['label' => 'Lihat Pelanggan', 'group' => 'Pelanggan', 'desc' => 'Lihat daftar & detail pelanggan'],
            'customer.manage' => ['label' => 'Kelola Pelanggan', 'group' => 'Pelanggan', 'desc' => 'Tambah, edit, hapus pelanggan'],

            'alarm.view' => ['label' => 'Lihat Alarm', 'group' => 'Operasional', 'desc' => 'Lihat daftar alarm'],
            'alarm.resolve' => ['label' => 'Resolve Alarm', 'group' => 'Operasional', 'desc' => 'Selesaikan alarm'],
            'ticket.view' => ['label' => 'Lihat Tiket', 'group' => 'Operasional', 'desc' => 'Lihat daftar tiket gangguan'],
            'ticket.manage' => ['label' => 'Kelola Tiket', 'group' => 'Operasional', 'desc' => 'Buat, update, hapus tiket'],
            'bandwidth.view' => ['label' => 'Lihat Paket', 'group' => 'Operasional', 'desc' => 'Lihat daftar paket bandwidth'],
            'bandwidth.manage' => ['label' => 'Kelola Paket', 'group' => 'Operasional', 'desc' => 'Tambah, edit, hapus paket bandwidth'],

            'user.manage' => ['label' => 'Kelola User', 'group' => 'Admin', 'desc' => 'Tambah, edit, hapus user & role'],
            'audit_log.view' => ['label' => 'Audit Log', 'group' => 'Admin', 'desc' => 'Lihat log aktivitas sistem'],
        ];
    }
}

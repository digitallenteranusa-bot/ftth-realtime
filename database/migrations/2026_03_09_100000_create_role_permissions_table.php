<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 20);
            $table->string('permission', 100);
            $table->boolean('allowed')->default(false);
            $table->timestamps();

            $table->unique(['role', 'permission']);
            $table->index('role');
        });

        // Seed default permissions
        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }

    private function seedDefaults(): void
    {
        $permissions = [
            // [permission, description, admin, operator, teknisi]
            ['dashboard.view', true, true, true],
            ['map.view', true, true, true],
            ['mikrotik.view', true, true, false],
            ['mikrotik.manage', true, true, false],
            ['olt.view', true, true, true],
            ['olt.manage', true, true, false],
            ['olt.sync_signal', true, true, true],
            ['odc.view', true, true, false],
            ['odc.manage', true, true, false],
            ['odp.view', true, true, true],
            ['odp.manage', true, true, false],
            ['ont.view', true, true, true],
            ['ont.manage', true, true, true],
            ['customer.view', true, true, true],
            ['customer.manage', true, true, true],
            ['alarm.view', true, true, true],
            ['alarm.resolve', true, true, true],
            ['ticket.view', true, true, true],
            ['ticket.manage', true, true, true],
            ['nearby.view', true, true, true],
            ['bandwidth.view', true, true, false],
            ['bandwidth.manage', true, true, false],
            ['export.use', true, true, true],
            ['user.manage', true, false, false],
            ['audit_log.view', true, false, false],
        ];

        $roles = ['admin', 'operator', 'teknisi'];
        $rows = [];
        $now = now();

        foreach ($permissions as $perm) {
            $permName = $perm[0];
            foreach ($roles as $i => $role) {
                $rows[] = [
                    'role' => $role,
                    'permission' => $permName,
                    'allowed' => $perm[$i + 1],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('role_permissions')->insert($rows);
    }
};

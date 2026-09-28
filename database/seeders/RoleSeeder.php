<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder {
    public function run() {
        // Limpiar roles (cuidado si hay relaciones foráneas)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('permission_role')->truncate();
        DB::table('roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $roles = [
            ['id' => 1, 'code' => 'global_admin', 'name' => 'Administrador global', 'description' => 'Control total del sistema'],
            ['id' => 2, 'code' => 'propietario', 'name' => 'Propietario', 'description' => 'Dueño de fincas'],
            ['id' => 3, 'code' => 'gestion_animales', 'name' => 'Gestor de animales', 'description' => 'Acceso al módulo de animales y genética'],
            ['id' => 4, 'code' => 'gestion_sanidad', 'name' => 'Gestor de sanidad', 'description' => 'Acceso total al módulo de sanidad y vacunas'],
            ['id' => 5, 'code' => 'gestion_reproduccion', 'name' => 'Gestor de reproducción', 'description' => 'Control de palpaciones, celos y lactancia'],
            ['id' => 6, 'code' => 'gestion_personal', 'name' => 'Gestor de personal', 'description' => 'Administración de empleados'],
            ['id' => 7, 'code' => 'gestion_inventario', 'name' => 'Gestor de inventario', 'description' => 'Manejo de inventarios y casas comerciales'],
            ['id' => 8, 'code' => 'gestion_produccion', 'name' => 'Gestor de producción', 'description' => 'Registro de leche y producción'],
            ['id' => 9, 'code' => 'gerente_finca', 'name' => 'Gerente de finca', 'description' => 'Administra la estructura de la finca (terrenos, etc.)'],
            ['id' => 10, 'code' => 'analista_datos', 'name' => 'Analista de datos', 'description' => 'Acceso exclusivo a reportes'],
            ['id' => 11, 'code' => 'gestion_rebano', 'name' => 'Gestor de rebaños', 'description' => 'Manejo de grupos de animales y sus traslados'],
            ['id' => 12, 'code' => 'observador', 'name' => 'Observador', 'description' => 'Acceso de solo lectura'],
        ];

        DB::table('roles')->insert(collect($roles)->map(fn($item) => array_merge($item, ['created_at' => now(), 'updated_at' => now()]))->toArray());

        // Asignar permisos a roles
        $permissions = DB::table('permissions')->get();
        $rolePermissions = [];

        foreach ($permissions as $perm) {
            // 1. Admin Global tiene absolutamente todos los permisos (incluyendo módulo admin)
            $rolePermissions[] = ['role_id' => 1, 'permission_id' => $perm->id];

            // 2. Propietario tiene todos los permisos EXCEPTO los del módulo admin (usuarios, dueños globales, config), pero sí lectura de catálogo de tipos de trabajador
            if ($perm->module !== 'admin' || $perm->code === 'tipo_trabajador.read') {
                $rolePermissions[] = ['role_id' => 2, 'permission_id' => $perm->id];
            }

            // 3. Gestor de Animales
            if ($perm->module === 'animal' || $perm->code === 'finca.read' || $perm->code === 'reportes.read') {
                $rolePermissions[] = ['role_id' => 3, 'permission_id' => $perm->id];
            }

            // 4. Gestor de Sanidad
            if ($perm->module === 'sanidad' || $perm->code === 'animal.read' || $perm->code === 'finca.read' || $perm->code === 'reportes.read') {
                $rolePermissions[] = ['role_id' => 4, 'permission_id' => $perm->id];
            }

            // 5. Gestor de Reproducción
            if ($perm->module === 'reproduccion' || $perm->code === 'animal.read' || $perm->code === 'finca.read' || $perm->code === 'reportes.read') {
                $rolePermissions[] = ['role_id' => 5, 'permission_id' => $perm->id];
            }
            
            // 6. Gestor de Personal
            if ($perm->module === 'personal' || $perm->code === 'finca.read' || $perm->code === 'reportes.read' || $perm->code === 'tipo_trabajador.read') {
                $rolePermissions[] = ['role_id' => 6, 'permission_id' => $perm->id];
            }

            // 7. Gestor de Inventario
            if ($perm->module === 'inventario' || $perm->code === 'finca.read' || $perm->code === 'reportes.read') {
                $rolePermissions[] = ['role_id' => 7, 'permission_id' => $perm->id];
            }

            // 8. Gestor de Producción
            if ($perm->module === 'produccion' || $perm->code === 'animal.read' || $perm->code === 'finca.read' || $perm->code === 'reportes.read') {
                $rolePermissions[] = ['role_id' => 8, 'permission_id' => $perm->id];
            }

            // 9. Gerente de Finca
            if ($perm->module === 'finca' || $perm->code === 'reportes.read') {
                $rolePermissions[] = ['role_id' => 9, 'permission_id' => $perm->id];
            }

            // 10. Analista de Datos
            if ($perm->module === 'reportes' || $perm->code === 'finca.read') {
                $rolePermissions[] = ['role_id' => 10, 'permission_id' => $perm->id];
            }

            // 11. Gestor de Rebaños
            if ($perm->module === 'rebano' || $perm->code === 'animal.read' || $perm->code === 'finca.read' || $perm->code === 'reportes.read') {
                $rolePermissions[] = ['role_id' => 11, 'permission_id' => $perm->id];
            }

            // 12. Observador (solo lectura general)
            if ($perm->action === 'read') {
                $rolePermissions[] = ['role_id' => 12, 'permission_id' => $perm->id];
            }
        }

        DB::table('permission_role')->insert($rolePermissions);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Limpiamos los permisos
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Mapa de Módulo => Controladores
        $modules = [
            'finca' => ['finca', 'terreno'],
            'personal' => ['personal_finca'],
            'admin' => ['configuracion', 'propietario', 'usuario', 'finca_user', 'persona', 'tipo_trabajador'],
            'reportes' => ['reportes'],
            'animal' => ['animal', 'arbol_gen', 'cambios_animal', 'composicion_raza', 'tipo_animal', 'etapa', 'medidas_corporales', 'peso_corporal'],
            'rebano' => ['rebano', 'movimiento_rebano'],
            'sanidad' => ['tratamiento', 'vacuna', 'vacunacion', 'diagnostico', 'estado_salud', 'estado_animal'],
            'reproduccion' => ['palpacion', 'registro_celo', 'reproduccion_animal', 'semen_toro', 'servicio_animal', 'dia_palpacion', 'foliculo', 'ovario', 'cuerno'],
            'inventario' => ['casa_comercial', 'inventario_bufalo', 'inventario_general', 'inventario_vacuno'],
            'produccion' => ['leche', 'lactancia']
        ];

        $defaultActions = ['read', 'create', 'update', 'delete'];
        
        // Controladores que no usan el CRUD estándar completo
        $customActions = [
            'reportes' => ['read'], // No es una tabla, solo se leen/generan
            'configuracion' => ['read', 'update'], // No es tabla, pero se puede leer y actualizar la config
            'personal_finca' => ['read', 'create', 'update', 'delete', 'assign'], // Se le suma la acción assign
            'persona' => ['read', 'create', 'update', 'delete']
        ];

        $permissions = [];

        foreach ($modules as $moduleName => $controllers) {
            foreach ($controllers as $controllerCode) {
                $actions = $customActions[$controllerCode] ?? $defaultActions;
                
                foreach ($actions as $action) {
                    $permissions[] = [
                        'code' => $controllerCode . '.' . $action,
                        'module' => $moduleName,
                        'action' => $action
                    ];
                }
            }
        }

        $now = now();
        $formattedPermissions = array_map(function ($perm) use ($now) {
            return array_merge($perm, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $permissions);

        DB::table('permissions')->insert($formattedPermissions);
    }
}

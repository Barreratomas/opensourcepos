<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixAppointmentsAndServicesModule extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Asegurar que los módulos existan en la tabla 'ospos_modules'
        $modules = [
            [
                'module_id' => 'appointments',
                'name_lang_key' => 'module_appointments',
                'desc_lang_key' => 'module_appointments_desc',
                'sort' => 25
            ],
            [
                'module_id' => 'appointment_services',
                'name_lang_key' => 'module_appointment_services',
                'desc_lang_key' => 'module_appointment_services_desc',
                'sort' => 26
            ]
        ];

        foreach ($modules as $module) {
            $exists = $db->table('modules')->getWhere(['module_id' => $module['module_id']])->getRow();
            if (empty($exists)) {
                $db->table('modules')->insert($module);
            }
        }

        // 2. Asegurar que los permisos existan y estén vinculados a sus propios módulos
        $permissions = [
            ['permission_id' => 'appointments', 'module_id' => 'appointments'],
            ['permission_id' => 'appointment_services', 'module_id' => 'appointment_services']
        ];

        foreach ($permissions as $p) {
            $exists = $db->table('permissions')->getWhere(['permission_id' => $p['permission_id']])->getRow();
            if (empty($exists)) {
                $db->table('permissions')->insert($p);
            } else {
                // Fix Pro: Si ya existía pero apuntaba al módulo equivocado (como pasaba antes)
                $db->table('permissions')
                   ->where('permission_id', $p['permission_id'])
                   ->update(['module_id' => $p['module_id']]);
            }
        }

        // 3. Conceder permisos automáticamente al administrador (person_id = 1)
        foreach ($permissions as $p) {
            $existsGrant = $db->table('grants')
                              ->getWhere(['permission_id' => $p['permission_id'], 'person_id' => 1])
                              ->getRow();
            if (empty($existsGrant)) {
                $db->table('grants')->insert([
                    'permission_id' => $p['permission_id'],
                    'person_id' => 1
                ]);
            }
        }
    }

    public function down()
    {
        // No solemos borrar módulos en el down para evitar pérdida de datos accidental,
        // pero aquí está la lógica si fuera necesaria:
        /*
        $db = \Config\Database::connect();
        $db->table('grants')->whereIn('permission_id', ['appointments', 'appointment_services'])->delete();
        $db->table('permissions')->whereIn('permission_id', ['appointments', 'appointment_services'])->delete();
        $db->table('modules')->whereIn('module_id', ['appointments', 'appointment_services'])->delete();
        */
    }
}

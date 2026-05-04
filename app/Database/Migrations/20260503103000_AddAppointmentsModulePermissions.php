<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAppointmentsModulePermissions extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Add module if missing
        $module = $db->table('modules')->getWhere(['module_id' => 'appointments'])->getRow();
        if (empty($module)) {
            $db->table('modules')->insert([
                'module_id' => 'appointments',
                'name_lang_key' => 'module_appointments',
                'desc_lang_key' => 'module_appointments_desc',
                'sort' => 25
            ]);
        }

        // Add permissions if missing
        $permissions = [
            ['permission_id' => 'appointments', 'module_id' => 'appointments'],
            ['permission_id' => 'appointment_services', 'module_id' => 'appointments']
        ];

        foreach ($permissions as $p) {
            $exists = $db->table('permissions')->getWhere(['permission_id' => $p['permission_id']])->getRow();
            if (empty($exists)) {
                $db->table('permissions')->insert($p);
            }
        }

        // Grant permissions to admin (person_id = 1) if not present
        foreach ($permissions as $p) {
            $existsGrant = $db->table('grants')->getWhere(['permission_id' => $p['permission_id'], 'person_id' => 1])->getRow();
            if (empty($existsGrant)) {
                $db->table('grants')->insert(['permission_id' => $p['permission_id'], 'person_id' => 1]);
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        // Remove grants
        $db->table('grants')->where('permission_id', 'appointments')->where('person_id', 1)->delete();
        $db->table('grants')->where('permission_id', 'appointment_services')->where('person_id', 1)->delete();

        // Remove permissions
        $db->table('permissions')->where('permission_id', 'appointments')->delete();
        $db->table('permissions')->where('permission_id', 'appointment_services')->delete();

        // Remove module
        $db->table('modules')->where('module_id', 'appointments')->delete();
    }
}

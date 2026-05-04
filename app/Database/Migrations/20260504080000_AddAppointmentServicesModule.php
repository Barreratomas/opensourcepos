<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAppointmentServicesModule extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Add appointment_services module if missing
        $module = $db->table('modules')->getWhere(['module_id' => 'appointment_services'])->getRow();
        if (empty($module)) {
            $db->table('modules')->insert([
                'module_id' => 'appointment_services',
                'name_lang_key' => 'appointment_services',
                'desc_lang_key' => 'appointment_services_desc',
                'sort' => 26
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $db->table('modules')->where('module_id', 'appointment_services')->delete();
    }
}

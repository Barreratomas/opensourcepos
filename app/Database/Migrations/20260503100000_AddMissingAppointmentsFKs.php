<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMissingAppointmentsFKs extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $prefix = $db->DBPrefix;

        $addIfMissing = function ($constraintName, $table, $column, $refTable, $refColumn, $onDelete = 'CASCADE', $onUpdate = 'CASCADE') use ($db, $prefix) {
            $fullTable = $prefix . $table;
            $refFull = $prefix . $refTable;

            // Ensure referenced table exists
            $existsRef = $db->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $refFull . "'")->getRow();
            if (empty($existsRef) || (int)$existsRef->cnt === 0) {
                return;
            }

            // Check constraint exists
            $check = $db->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='" . $fullTable . "' AND CONSTRAINT_NAME='" . $constraintName . "'")->getRow();
            if (!empty($check) && (int)$check->cnt > 0) {
                return;
            }

            $sql = sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s ON UPDATE %s',
                $fullTable,
                $constraintName,
                $column,
                $refFull,
                $refColumn,
                $onDelete,
                $onUpdate
            );

            $db->query($sql);
        };

        $addIfMissing($prefix . 'appointments_customer_id_foreign', 'appointments', 'customer_id', 'customers', 'person_id');
        $addIfMissing($prefix . 'appointments_employee_id_foreign', 'appointments', 'employee_id', 'employees', 'person_id');
        $addIfMissing($prefix . 'appointments_service_id_foreign', 'appointments', 'service_id', 'appointment_services', 'id');
        $addIfMissing($prefix . 'appointments_sale_id_foreign', 'appointments', 'sale_id', 'sales', 'sale_id', 'SET NULL');
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $prefix = $db->DBPrefix;

        $dropIfExists = function ($constraintName, $table) use ($db, $prefix) {
            $fullTable = $prefix . $table;
            $check = $db->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='" . $fullTable . "' AND CONSTRAINT_NAME='" . $constraintName . "'")->getRow();
            if (!empty($check) && (int)$check->cnt > 0) {
                $db->query(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $fullTable, $constraintName));
            }
        };

        $dropIfExists($prefix . 'appointments_customer_id_foreign', 'appointments');
        $dropIfExists($prefix . 'appointments_employee_id_foreign', 'appointments');
        $dropIfExists($prefix . 'appointments_service_id_foreign', 'appointments');
        $dropIfExists($prefix . 'appointments_sale_id_foreign', 'appointments');
    }
}

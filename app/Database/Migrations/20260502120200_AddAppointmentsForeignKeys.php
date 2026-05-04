<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAppointmentsForeignKeys extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $prefix = $db->DBPrefix;

        // Helper to add constraint if referenced table and constraint name do not exist
        $addFk = function ($table, $constraintName, $column, $refTable, $refColumn, $onDelete = 'CASCADE', $onUpdate = 'CASCADE') use ($db, $prefix) {
            // Check referenced table exists
            $tbl = $prefix . $refTable;
            $exists = $db->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $db->escapeLikeString($tbl) . "'")->getRow();
            if (empty($exists) || (int)$exists->cnt === 0) {
                // referenced table missing; skip
                return;
            }

            // Check constraint doesn't already exist
            $fullTable = $prefix . $table;
            $check = $db->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='" . $db->escapeLikeString($fullTable) . "' AND CONSTRAINT_NAME='" . $db->escapeLikeString($constraintName) . "'")->getRow();
            if (!empty($check) && (int)$check->cnt > 0) {
                return;
            }

            $sql = sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s ON UPDATE %s',
                $fullTable,
                $constraintName,
                $column,
                $prefix . $refTable,
                $refColumn,
                $onDelete,
                $onUpdate
            );

            $db->query($sql);
        };

        // customer -> customers.person_id
        $addFk('appointments', $prefix . 'appointments_customer_id_foreign', 'customer_id', 'customers', 'person_id', 'CASCADE', 'CASCADE');

        // employee -> employees.person_id
        $addFk('appointments', $prefix . 'appointments_employee_id_foreign', 'employee_id', 'employees', 'person_id', 'CASCADE', 'CASCADE');

        // service -> appointment_services.id
        $addFk('appointments', $prefix . 'appointments_service_id_foreign', 'service_id', 'appointment_services', 'id', 'CASCADE', 'CASCADE');

        // sale -> sales.sale_id (nullable)
        $addFk('appointments', $prefix . 'appointments_sale_id_foreign', 'sale_id', 'sales', 'sale_id', 'SET NULL', 'CASCADE');
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $prefix = $db->DBPrefix;

        $dropIfExists = function ($table, $constraintName) use ($db, $prefix) {
            $fullTable = $prefix . $table;
            $check = $db->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='" . $db->escapeLikeString($fullTable) . "' AND CONSTRAINT_NAME='" . $db->escapeLikeString($constraintName) . "'")->getRow();
            if (!empty($check) && (int)$check->cnt > 0) {
                $db->query(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $fullTable, $constraintName));
            }
        };

        $dropIfExists('appointments', $prefix . 'appointments_customer_id_foreign');
        $dropIfExists('appointments', $prefix . 'appointments_employee_id_foreign');
        $dropIfExists('appointments', $prefix . 'appointments_service_id_foreign');
        $dropIfExists('appointments', $prefix . 'appointments_sale_id_foreign');
    }
}

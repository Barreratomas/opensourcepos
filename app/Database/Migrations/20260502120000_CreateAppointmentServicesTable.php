<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppointmentServicesTable extends Migration
{
    public function up()
    {
        // Create appointment_services table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'duration_minutes' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'price' => [
                'type' => 'DECIMAL',
                'constraint' => [10, 2],
                'null' => false,
                'default' => 0.00,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'active' => [
                'type' => 'BOOLEAN',
                'default' => true,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'on_update' => 'CURRENT_TIMESTAMP',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('active');
        $this->forge->addKey('name');

        $this->forge->createTable('appointment_services', true);
    }

    public function down()
    {
        $this->forge->dropTable('appointment_services', true);
    }
}

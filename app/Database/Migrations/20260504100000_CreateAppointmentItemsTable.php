<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppointmentItemsTable extends Migration
{
    public function up()
    {
        // Create appointment_items table for multiple services per appointment
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'appointment_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'service_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('appointment_id');
        $this->forge->addKey('service_id');

        // Add foreign keys
        $this->forge->addForeignKey('appointment_id', 'appointments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('service_id', 'appointment_services', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('appointment_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('appointment_items', true);
    }
}

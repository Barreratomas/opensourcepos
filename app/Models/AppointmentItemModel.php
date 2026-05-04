<?php

namespace App\Models;

use CodeIgniter\Model;

class AppointmentItemModel extends Model
{
    protected $table = 'appointment_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'appointment_id', 'service_id'
    ];

    public function getByAppointment(int $appointmentId)
    {
        return $this->where('appointment_id', $appointmentId)
                    ->findAll();
    }

    public function deleteByAppointment(int $appointmentId)
    {
        return $this->where('appointment_id', $appointmentId)
                    ->delete();
    }
}

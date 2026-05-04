<?php

namespace App\Models;

use CodeIgniter\Model;

class AppointmentModel extends Model
{
    protected $table = 'appointments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'customer_id', 'employee_id', 'service_id', 'start_time', 'end_time', 'status', 'notes', 'sale_id', 'google_event_id', 'created_at', 'updated_at'
    ];

    /**
     * Get appointments overlapping a given date range.
     * Returns appointments where start_time < $end AND end_time > $start
     */
    public function getByDateRange(string $start, string $end)
    {
        return $this->where('start_time <', $end)
                    ->where('end_time >', $start)
                    ->orderBy('start_time', 'ASC')
                    ->findAll();
    }

    public function getByCustomer(int $customerId)
    {
        return $this->where('customer_id', $customerId)
                    ->orderBy('start_time', 'DESC')
                    ->findAll();
    }

    public function getUpcoming(int $limit = 10)
    {
        $now = date('Y-m-d H:i:s');
        return $this->where('start_time >=', $now)
                    ->orderBy('start_time', 'ASC')
                    ->findAll($limit);
    }

    public function getByStatus($status)
    {
        if (is_array($status)) {
            return $this->whereIn('status', $status)->orderBy('start_time', 'ASC')->findAll();
        }
        return $this->where('status', $status)->orderBy('start_time', 'ASC')->findAll();
    }

    /**
     * Check if an employee has an overlapping appointment.
     * Returns true if overlap exists.
     */
    public function checkOverlap(int $employeeId, string $start, string $end, ?int $excludeId = null): bool
    {
        $builder = $this->where('employee_id', $employeeId)
                        ->where('start_time <', $end)
                        ->where('end_time >', $start);

        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Get services for a specific appointment
     */
    public function getServices(int $appointmentId)
    {
        $itemModel = new AppointmentItemModel();
        return $itemModel->where('appointment_id', $appointmentId)->findAll();
    }
}

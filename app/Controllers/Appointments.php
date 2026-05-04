<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\AppointmentServiceModel;
use App\Libraries\Google_calendar_lib;
use App\Models\Customer;

class Appointments extends Secure_Controller
{
    protected $appointmentModel;
    protected $serviceModel;
    protected $googleLib;

    public function __construct()
    {
        parent::__construct('appointments');
        $this->appointmentModel = new AppointmentModel();
        $this->serviceModel = new AppointmentServiceModel();
        $this->googleLib = new Google_calendar_lib();
    }

    public function index()
    {
        // Placeholder view; integrate with frontend later
        return view('appointments/index');
    }

    public function get_events()
    {
        $start = $this->request->getGet('start');
        $end   = $this->request->getGet('end');

        if (empty($start) || empty($end)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'start and end are required']);
        }

        $appts = $this->appointmentModel->getByDateRange($start, $end);
        $services = [];
        foreach ($this->serviceModel->findAll() as $s) {
            $services[$s['id']] = $s;
        }

        $events = [];
        foreach ($appts as $a) {
            $title = isset($services[$a['service_id']]) ? $services[$a['service_id']]['name'] : 'Appointment';
            $events[] = [
                'id' => $a['id'],
                'title' => $title,
                'start' => $a['start_time'],
                'end' => $a['end_time'],
                'extendedProps' => [
                    'status' => $a['status'],
                    'customer_id' => $a['customer_id'],
                    'employee_id' => $a['employee_id'],
                    'service_id' => $a['service_id'],
                ],
            ];
        }

        return $this->response->setJSON($events);
    }

    public function create()
    {
        $data = $this->request->getPost();

        $required = ['customer_id', 'employee_id', 'service_id', 'start_time', 'end_time'];
        foreach ($required as $r) {
            if (empty($data[$r])) {
                return $this->response->setStatusCode(400)->setJSON(['error' => "$r is required"]);
            }
        }

        // Check overlap for employee
        if ($this->appointmentModel->checkOverlap((int)$data['employee_id'], $data['start_time'], $data['end_time'])) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'Employee has an overlapping appointment']);
        }

        $insertId = $this->appointmentModel->insert([
            'customer_id' => $data['customer_id'],
            'employee_id' => $data['employee_id'],
            'service_id' => $data['service_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null,
            'sale_id' => $data['sale_id'] ?? null,
            'google_event_id' => $data['google_event_id'] ?? null,
        ]);

        if ($insertId) {
            $this->sync_google_calendar($insertId);
        }

        return $this->response->setJSON(['id' => $insertId]);
    }

    public function update($id = null)
    {
        if (empty($id)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'id required']);
        }

        $data = $this->request->getPost();

        // Check overlap excluding this appointment
        if (!empty($data['employee_id']) && !empty($data['start_time']) && !empty($data['end_time'])) {
            if ($this->appointmentModel->checkOverlap((int)$data['employee_id'], $data['start_time'], $data['end_time'], (int)$id)) {
                return $this->response->setStatusCode(409)->setJSON(['error' => 'Employee has an overlapping appointment']);
            }
        }

        $this->appointmentModel->update($id, $data);
        $this->sync_google_calendar($id);
        
        return $this->response->setJSON(['id' => $id]);
    }

    public function delete($id = null)
    {
        if (empty($id)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'id required']);
        }

        // Logical cancel
        $this->sync_google_calendar($id, 'delete');
        $this->appointmentModel->update($id, ['status' => 'cancelled']);
        
        return $this->response->setJSON(['id' => $id]);
    }

    public function complete($id = null)
    {
        if (empty($id)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'id required']);
        }

        $saleId = $this->request->getPost('sale_id');

        $update = ['status' => 'completed'];
        if (!empty($saleId)) {
            $update['sale_id'] = $saleId;
        }

        $this->appointmentModel->update($id, $update);
        return $this->response->setJSON(['id' => $id]);
    }

    public function get_form($id = null)
    {
        $result = ['services' => $this->serviceModel->findAll()];
        if (!empty($id)) {
            $appointment = $this->appointmentModel->find($id);
            if ($appointment) {
                $customerModel = model(\App\Models\Customer::class);
                $customer = $customerModel->get_info($appointment['customer_id']);
                $appointment['customer_name'] = $customer->first_name . ' ' . $customer->last_name;
                $result['appointment'] = $appointment;
            }
        }

        return $this->response->setJSON($result);
    }

    public function getCustomer_appointments($customer_id)
    {
        $appts = $this->appointmentModel->where('customer_id', $customer_id)
                                      ->orderBy('start_time', 'DESC')
                                      ->findAll();
        
        $services = [];
        foreach ($this->serviceModel->findAll() as $s) {
            $services[$s['id']] = $s['name'];
        }

        foreach ($appts as &$a) {
            $a['service_name'] = $services[$a['service_id']] ?? 'Unknown';
        }

        return $this->response->setJSON($appts);
    }

    public function google_authorize()
    {
        return redirect()->to($this->googleLib->get_auth_url());
    }

    public function google_callback()
    {
        $code = $this->request->getGet('code');
        if ($code) {
            if ($this->googleLib->exchange_code($code)) {
                return redirect()->to(site_url('config#integrations_tab'))->with('success', 'Google Calendar conectado con éxito');
            }
        }
        return redirect()->to(site_url('config#integrations_tab'))->with('error', 'Error al conectar con Google Calendar');
    }

    private function sync_google_calendar($appointment_id, $action = 'save')
    {
        $appt = $this->appointmentModel->find($appointment_id);
        if (!$appt) return;

        if ($action == 'delete' || $appt['status'] == 'cancelled') {
            if (!empty($appt['google_event_id'])) {
                $this->googleLib->delete_event($appt['google_event_id']);
                $this->appointmentModel->update($appointment_id, ['google_event_id' => null]);
            }
            return;
        }

        $customerModel = model(Customer::class);
        $customer = $customerModel->get_info($appt['customer_id']);
        $service = $this->serviceModel->find($appt['service_id']);

        $event_data = [
            'summary' => ($service['name'] ?? 'Cita') . ' - ' . ($customer->first_name . ' ' . $customer->last_name),
            'description' => $appt['notes'] ?? '',
            'start' => [
                'dateTime' => date('c', strtotime($appt['start_time'])),
                'timeZone' => config('App')->appTimezone ?? 'UTC',
            ],
            'end' => [
                'dateTime' => date('c', strtotime($appt['end_time'])),
                'timeZone' => config('App')->appTimezone ?? 'UTC',
            ],
        ];

        if (empty($appt['google_event_id'])) {
            $result = $this->googleLib->create_event($event_data);
            if (isset($result['id'])) {
                $this->appointmentModel->update($appointment_id, ['google_event_id' => $result['id']]);
            }
        } else {
            $this->googleLib->update_event($appt['google_event_id'], $event_data);
        }
    }
}

<?php

namespace App\Controllers;

use App\Models\AppointmentServiceModel;

class Appointment_services extends Secure_Controller
{
    protected $serviceModel;

    public function __construct()
    {
        parent::__construct('appointments');
        $this->serviceModel = new AppointmentServiceModel();
    }

    public function index()
    {
        return view('appointment_services/index');
    }

    public function list()
    {
        return $this->response->setJSON($this->serviceModel->findAll());
    }

    public function create()
    {
        $data = $this->request->getPost();
        $required = ['name', 'duration_minutes', 'price'];
        foreach ($required as $r) {
            if (!isset($data[$r])) {
                return $this->response->setStatusCode(400)->setJSON(['error' => "$r is required"]);
            }
        }

        $id = $this->serviceModel->insert([
            'name' => $data['name'],
            'duration_minutes' => $data['duration_minutes'],
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
            'active' => $data['active'] ?? 1,
        ]);

        return $this->response->setJSON(['id' => $id]);
    }

    public function update($id = null)
    {
        if (empty($id)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'id required']);
        }

        $data = $this->request->getPost();
        $this->serviceModel->update($id, $data);
        return $this->response->setJSON(['id' => $id]);
    }

    public function delete($id = null)
    {
        if (empty($id)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'id required']);
        }

        $this->serviceModel->delete($id);
        return $this->response->setJSON(['id' => $id]);
    }

    public function get_form($id = null)
    {
        $result = [];
        if (!empty($id)) {
            $result['service'] = $this->serviceModel->find($id);
        }
        return $this->response->setJSON($result);
    }
}

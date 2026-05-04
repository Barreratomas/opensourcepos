<?php

namespace App\Models;

use CodeIgniter\Model;

class AppointmentServiceModel extends Model
{
    protected $table = 'appointment_services';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name', 'duration_minutes', 'price', 'description', 'active', 'created_at', 'updated_at'
    ];

    public function getAllActive()
    {
        return $this->where('active', 1)
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    public function getById(int $id)
    {
        return $this->find($id);
    }

    public function getSearchSuggestions(string $search, int $limit = 25): array
    {
        $builder = $this->db->table($this->table);
        $builder->select('id, name, price');
        $builder->like('name', $search);
        $builder->where('active', 1);
        $builder->limit($limit);
        
        $suggestions = [];
        foreach ($builder->get()->getResult() as $row) {
            $suggestions[] = [
                'value' => 'SERV ' . $row->id,
                'label' => $row->name . ' (Servicio) - ' . to_currency($row->price)
            ];
        }
        
        return $suggestions;
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class ModuleModel extends Model
{
    protected $table = 'modules';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'module_name', 
        'module_code', 
        'application_id'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'module_name' => 'required|min_length[3]',
        'module_code' => 'required',
        'application_id' => 'required|integer'
    ];
    
    /**
     * Mendapatkan modules berdasarkan application ID
     */
    public function getModulesByApplication($applicationId)
    {
        return $this->where('application_id', $applicationId)->findAll();
    }
    
    /**
     * Mendapatkan module dengan informasi aplikasi
     */
    public function getModulesWithApplication()
    {
        return $this->select('modules.*, applications.app_name')
                    ->join('applications', 'applications.id = modules.application_id', 'left')
                    ->findAll();
    }
}
<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ModuleModel;
use CodeIgniter\API\ResponseTrait;

class ModuleController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new ModuleModel();
    }
    
    // GET /api/modules
    public function index()
    {
        $modules = $this->model->findAll();
        return $this->respond($modules);
    }
    
    // POST /api/modules
    public function create()
    {
        $rules = [
            'module_name' => 'required|min_length[3]',
            'module_code' => 'required',
            'application_id' => 'required|integer'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        $id = $this->model->insert($data);
        
        if (!$id) {
            return $this->fail('Failed to create module');
        }
        
        return $this->respondCreated([
            'message' => 'Module created successfully',
            'id' => $id
        ]);
    }
    
    // GET /api/modules/{id}
    public function show($id = null)
    {
        $module = $this->model->find($id);
        
        if (!$module) {
            return $this->failNotFound('Module not found');
        }
        
        return $this->respond($module);
    }
    
    // PUT /api/modules/{id}
    public function update($id = null)
    {
        $module = $this->model->find($id);
        
        if (!$module) {
            return $this->failNotFound('Module not found');
        }
        
        $data = $this->request->getJSON(true);
        $this->model->update($id, $data);
        
        return $this->respond([
            'message' => 'Module updated successfully'
        ]);
    }
    
    // DELETE /api/modules/{id}
    public function delete($id = null)
    {
        $module = $this->model->find($id);
        
        if (!$module) {
            return $this->failNotFound('Module not found');
        }
        
        $this->model->delete($id);
        
        return $this->respond([
            'message' => 'Module deleted successfully'
        ]);
    }
    
    // GET /api/applications/{applicationId}/modules
    public function getByApplication($applicationId = null)
    {
        if (!$applicationId) {
            return $this->fail('Application ID is required');
        }
        
        $modules = $this->model->where('application_id', $applicationId)->findAll();
        
        return $this->respond($modules);
    }
}
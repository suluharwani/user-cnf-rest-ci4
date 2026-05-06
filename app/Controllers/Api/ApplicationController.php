<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ApplicationModel;
use CodeIgniter\API\ResponseTrait;

class ApplicationController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new ApplicationModel();
        // HAPUS panggilan checkAuthentication()
        // Autentikasi sudah di-handle oleh Filter Auth di routes
    }
    
    public function index()
    {
        $applications = $this->model->findAll();
        return $this->respond($applications);
    }
    
    public function create()
    {
        $rules = [
            'app_name' => 'required|min_length[3]',
            'app_code' => 'required|is_unique[applications.app_code]',
            'description' => 'permit_empty'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        $id = $this->model->insert($data);
        
        if (!$id) {
            return $this->fail('Failed to create application');
        }
        
        return $this->respondCreated([
            'message' => 'Application created successfully',
            'id' => $id
        ]);
    }
    
    public function show($id = null)
    {
        $application = $this->model->find($id);
        
        if (!$application) {
            return $this->failNotFound('Application not found');
        }
        
        return $this->respond($application);
    }
    
    public function update($id = null)
    {
        $application = $this->model->find($id);
        
        if (!$application) {
            return $this->failNotFound('Application not found');
        }
        
        $data = $this->request->getJSON(true);
        $this->model->update($id, $data);
        
        return $this->respond([
            'message' => 'Application updated successfully'
        ]);
    }
    
    public function delete($id = null)
    {
        $application = $this->model->find($id);
        
        if (!$application) {
            return $this->failNotFound('Application not found');
        }
        
        $this->model->delete($id);
        
        return $this->respond([
            'message' => 'Application deleted successfully'
        ]);
    }
}
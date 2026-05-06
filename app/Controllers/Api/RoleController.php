<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\RoleModel;
use CodeIgniter\API\ResponseTrait;

class RoleController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new RoleModel();
    }
    
    public function index()
    {
        $roles = $this->model->findAll();
        return $this->respond($roles);
    }
    
    public function create()
    {
        $rules = [
            'role_name' => 'required|min_length[3]',
            'role_code' => 'required|is_unique[roles.role_code]',
            'description' => 'permit_empty'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        $id = $this->model->insert($data);
        
        return $this->respondCreated([
            'message' => 'Role created successfully',
            'id' => $id
        ]);
    }
    
    public function show($id = null)
    {
        $role = $this->model->find($id);
        
        if (!$role) {
            return $this->failNotFound('Role not found');
        }
        
        return $this->respond($role);
    }
    
    public function update($id = null)
    {
        $role = $this->model->find($id);
        
        if (!$role) {
            return $this->failNotFound('Role not found');
        }
        
        $data = $this->request->getJSON(true);
        $this->model->update($id, $data);
        
        return $this->respond([
            'message' => 'Role updated successfully'
        ]);
    }
    
    public function delete($id = null)
    {
        $role = $this->model->find($id);
        
        if (!$role) {
            return $this->failNotFound('Role not found');
        }
        
        $this->model->delete($id);
        
        return $this->respond([
            'message' => 'Role deleted successfully'
        ]);
    }
}
<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

class UserManagementController extends BaseController
{
    use ResponseTrait;
    
    protected $userModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    
    public function index()
    {
        $users = $this->userModel->getUsersWithRoles();
        return $this->respond($users);
    }
    
    public function show($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        
        $db = \Config\Database::connect();
        $builder = $db->table('user_roles');
        $builder->select('roles.*');
        $builder->join('roles', 'roles.id = user_roles.role_id');
        $builder->where('user_roles.user_id', $id);
        $roles = $builder->get()->getResultArray();
        
        $user['roles'] = $roles;
        unset($user['password']);
        
        return $this->respond($user);
    }
    
    public function update($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        
        $data = $this->request->getJSON(true);
        
        if (empty($data['password'])) {
            unset($data['password']);
        }
        
        $this->userModel->update($id, $data);
        
        return $this->respond([
            'message' => 'User updated successfully'
        ]);
    }
    
    public function delete($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        
        $this->userModel->delete($id);
        
        return $this->respond([
            'message' => 'User deleted successfully'
        ]);
    }
    
    public function assignRole()
    {
        $rules = [
            'user_id' => 'required|integer',
            'role_id' => 'required|integer'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        
        $db = \Config\Database::connect();
        
        $exists = $db->table('user_roles')
                     ->where('user_id', $data['user_id'])
                     ->where('role_id', $data['role_id'])
                     ->countAllResults();
        
        if ($exists) {
            return $this->fail('Role already assigned to this user');
        }
        
        $db->table('user_roles')->insert([
            'user_id' => $data['user_id'],
            'role_id' => $data['role_id']
        ]);
        
        return $this->respond([
            'message' => 'Role assigned successfully'
        ]);
    }
    
    public function removeRole()
    {
        $rules = [
            'user_id' => 'required|integer',
            'role_id' => 'required|integer'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        
        $db = \Config\Database::connect();
        $db->table('user_roles')
           ->where('user_id', $data['user_id'])
           ->where('role_id', $data['role_id'])
           ->delete();
        
        return $this->respond([
            'message' => 'Role removed successfully'
        ]);
    }
}
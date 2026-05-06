<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;

class AuthController extends BaseController
{
    use ResponseTrait;
    
    protected $userModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    
    public function register()
    {
        $rules = [
            'username' => 'required|min_length[3]|is_unique[users.username]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'fullname' => 'required'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        // Cek apakah sudah ada user
        $hasUsers = $this->userModel->hasAnyUser();
        
        if ($hasUsers) {
            // Jika sudah ada user, harus pakai kredensial admin
            $adminCredentials = $this->request->getJSON(true);
            
            if (!isset($adminCredentials['admin_username']) || !isset($adminCredentials['admin_password'])) {
                return $this->failUnauthorized('Admin credentials required for registration');
            }
            
            $admin = $this->userModel->where('username', $adminCredentials['admin_username'])->first();
            
            if (!$admin || !password_verify($adminCredentials['admin_password'], $admin['password'])) {
                return $this->failUnauthorized('Invalid admin credentials');
            }
            
            // Verifikasi admin role
            $db = \Config\Database::connect();
            $builder = $db->table('user_roles');
            $builder->select('roles.role_code');
            $builder->join('roles', 'roles.id = user_roles.role_id');
            $builder->where('user_roles.user_id', $admin['id']);
            $roles = $builder->get()->getResultArray();
            
            $isAdmin = false;
            foreach ($roles as $role) {
                if ($role['role_code'] === 'admin') {
                    $isAdmin = true;
                    break;
                }
            }
            
            if (!$isAdmin) {
                return $this->failUnauthorized('Only admin can register new users');
            }
        }
        
        $data = $this->request->getJSON(true);
        $userId = $this->userModel->insert($data);
        
        if (!$userId) {
            return $this->fail('Registration failed');
        }
        
        // Jika ini user pertama, beri role admin
        if (!$hasUsers) {
            $this->assignDefaultAdminRole($userId);
        }
        
        return $this->respondCreated([
            'message' => 'User registered successfully',
            'user_id' => $userId
        ]);
    }
    
    public function login()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');
        
        $user = $this->userModel->where('username', $username)->first();
        
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Invalid credentials');
        }
        
        if (!$user['is_active']) {
            return $this->fail('Account is disabled');
        }
        
        // Generate JWT token
        $key = getenv('jwt.secret_key');
        $issuedAt = time();
        $expirationTime = $issuedAt + intval(getenv('jwt.time_to_live'));
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ];
        
        $token = JWT::encode($payload, $key, 'HS256');
        
        return $this->respond([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'fullname' => $user['fullname']
            ]
        ]);
    }
    
    private function assignDefaultAdminRole($userId)
    {
        $db = \Config\Database::connect();
        
        // Pastikan role admin exists
        $roleModel = new \App\Models\RoleModel();
        $adminRole = $roleModel->where('role_code', 'admin')->first();
        
        if (!$adminRole) {
            $roleId = $roleModel->insert([
                'role_name' => 'Administrator',
                'role_code' => 'admin',
                'description' => 'Super admin with full access'
            ]);
        } else {
            $roleId = $adminRole['id'];
        }
        
        // Assign role to user
        $db->table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId
        ]);
    }
}
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
    
    /**
     * Register user baru
     * POST /api/auth/register
     */
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
        
        $hasUsers = $this->userModel->hasAnyUser();
        
        if ($hasUsers) {
            $adminCredentials = $this->request->getJSON(true);
            
            if (!isset($adminCredentials['admin_username']) || !isset($adminCredentials['admin_password'])) {
                return $this->failUnauthorized('Admin credentials required for registration');
            }
            
            $admin = $this->userModel->where('username', $adminCredentials['admin_username'])->first();
            
            if (!$admin || !password_verify($adminCredentials['admin_password'], $admin['password'])) {
                return $this->failUnauthorized('Invalid admin credentials');
            }
            
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
        
        // ===== SET DEFAULT AVATAR =====
        $data['avatar_url'] = base_url('assets/images/default-avatar.png');
        // Atau bisa juga generate avatar dari inisial nama:
        // $data['avatar_url'] = $this->generateDefaultAvatar($data['fullname']);
        
        $userId = $this->userModel->insert($data);
        
        if (!$userId) {
            return $this->fail('Registration failed');
        }
        
        if (!$hasUsers) {
            $this->assignDefaultAdminRole($userId);
        }
        
        // Ambil user yang baru dibuat dengan avatar info
        $user = $this->userModel->getUserWithAvatar($userId);
        $formattedUser = $this->formatUserResponse($user);
        unset($formattedUser['password']);
        
        return $this->respondCreated([
            'message' => 'User registered successfully',
            'user' => $formattedUser
        ]);
    }
    
    /**
     * Login
     * POST /api/auth/login
     */
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
        
        $user = $this->userModel->getUserWithAvatar(
            $this->userModel->where('username', $username)->first()['id'] ?? null
        );
        
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Invalid credentials');
        }
        
        if (!$user['is_active']) {
            return $this->fail('Account is disabled');
        }
        
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
        
        // Format user response dengan avatar
        $formattedUser = $this->formatUserResponse($user);
        unset($formattedUser['password']);
        
        return $this->respond([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $formattedUser
        ]);
    }
    
    /**
     * Get current user profile
     * GET /api/auth/profile
     */
    public function profile()
    {
        $userId = $this->request->user_id ?? null;
        
        if (!$userId) {
            return $this->failUnauthorized('Not authenticated');
        }
        
        $user = $this->userModel->getUserWithAvatar($userId);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        
        $db = \Config\Database::connect();
        $builder = $db->table('user_roles');
        $builder->select('roles.*');
        $builder->join('roles', 'roles.id = user_roles.role_id');
        $builder->where('user_roles.user_id', $userId);
        $roles = $builder->get()->getResultArray();
        
        $user['roles'] = $roles;
        $formattedUser = $this->formatUserResponse($user);
        unset($formattedUser['password']);
        
        return $this->respond([
            'user' => $formattedUser
        ]);
    }
    
    /**
     * Generate default avatar URL (inisial nama)
     */
    private function generateDefaultAvatar($fullname)
    {
        $initials = '';
        $words = explode(' ', $fullname);
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        
        $initials = substr($initials, 0, 2);
        $bgColor = substr(md5($fullname), 0, 6);
        $textColor = 'ffffff';
        
        return "https://ui-avatars.com/api/?name={$initials}&background={$bgColor}&color={$textColor}&size=200";
    }
    
    /**
     * Format user response
     */
    private function formatUserResponse($user)
    {
        if (!isset($user['avatar_id']) || !$user['avatar_id']) {
            $user['avatar'] = [
                'url' => $user['avatar_url'] ?? base_url('assets/images/default-avatar.png'),
                'view_url' => null,
                'download_url' => null
            ];
        } else {
            $user['avatar'] = [
                'id' => $user['avatar_id'],
                'url' => $user['avatar_url'] ?? null,
                'view_url' => $user['avatar_id'] ? base_url('api/cdn/view/' . $user['avatar_id']) : null,
                'download_url' => $user['avatar_id'] ? base_url('api/cdn/download/' . $user['avatar_id']) : null
            ];
        }
        
        unset($user['avatar_path']);
        unset($user['avatar_filename']);
        unset($user['avatar_size']);
        unset($user['avatar_mime']);
        
        return $user;
    }
    
    private function assignDefaultAdminRole($userId)
    {
        $db = \Config\Database::connect();
        
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
        
        $db->table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId
        ]);
    }
}
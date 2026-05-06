<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'username', 'email', 'password', 'fullname', 'is_active'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
        'email'    => 'required|valid_email|is_unique[users.email]',
        'password' => 'required|min_length[8]',
    ];
    
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];
    
    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        
        return $data;
    }
    
    public function getUsersWithRoles()
    {
        return $this->select('users.*, GROUP_CONCAT(roles.role_name) as roles')
                    ->join('user_roles', 'user_roles.user_id = users.id', 'left')
                    ->join('roles', 'roles.id = user_roles.role_id', 'left')
                    ->groupBy('users.id')
                    ->findAll();
    }
    
    public function hasAnyUser(): bool
    {
        return $this->countAll() > 0;
    }
}
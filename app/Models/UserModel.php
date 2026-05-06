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
        'username', 
        'email', 
        'password', 
        'fullname', 
        'is_active',
        'avatar_id',    // Tambahkan
        'avatar_url'    // Tambahkan
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
    
    /**
     * Get users with roles and avatar
     */
    public function getUsersWithRoles()
    {
        return $this->select('users.*, GROUP_CONCAT(roles.role_name) as roles, cdn_files.file_path as avatar_path, cdn_files.file_name as avatar_filename')
                    ->join('user_roles', 'user_roles.user_id = users.id', 'left')
                    ->join('roles', 'roles.id = user_roles.role_id', 'left')
                    ->join('cdn_files', 'cdn_files.id = users.avatar_id', 'left')
                    ->groupBy('users.id')
                    ->findAll();
    }
    
    /**
     * Get user with avatar detail
     */
    public function getUserWithAvatar($userId)
    {
        return $this->select('users.*, cdn_files.file_path as avatar_path, cdn_files.file_name as avatar_filename, cdn_files.file_size as avatar_size, cdn_files.mime_type as avatar_mime')
                    ->join('cdn_files', 'cdn_files.id = users.avatar_id', 'left')
                    ->where('users.id', $userId)
                    ->first();
    }
    
    /**
     * Update user avatar
     */
    public function updateAvatar($userId, $avatarId, $avatarUrl)
    {
        return $this->update($userId, [
            'avatar_id' => $avatarId,
            'avatar_url' => $avatarUrl
        ]);
    }
    
    /**
     * Remove user avatar
     */
    public function removeAvatar($userId)
    {
        return $this->update($userId, [
            'avatar_id' => null,
            'avatar_url' => null
        ]);
    }
    
    public function hasAnyUser(): bool
    {
        return $this->countAll() > 0;
    }
}
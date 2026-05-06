<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\CdnFileModel;
use CodeIgniter\API\ResponseTrait;

class UserManagementController extends BaseController
{
    use ResponseTrait;
    
    protected $userModel;
    protected $cdnModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->cdnModel = new CdnFileModel();
    }
    
    /**
     * Get all users
     * GET /api/users
     */
    public function index()
    {
        $users = $this->userModel->getUsersWithRoles();
        
        // Format avatar URL untuk setiap user
        $formattedUsers = array_map(function($user) {
            return $this->formatUserResponse($user);
        }, $users);
        
        return $this->respond($formattedUsers);
    }
    
    /**
     * Get user by ID
     * GET /api/users/{id}
     */
    public function show($id = null)
    {
        $user = $this->userModel->getUserWithAvatar($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        
        // Get user roles
        $db = \Config\Database::connect();
        $builder = $db->table('user_roles');
        $builder->select('roles.*');
        $builder->join('roles', 'roles.id = user_roles.role_id');
        $builder->where('user_roles.user_id', $id);
        $roles = $builder->get()->getResultArray();
        
        $user['roles'] = $roles;
        
        // Format avatar
        $user = $this->formatUserResponse($user);
        
        // Hapus password dari response
        unset($user['password']);
        
        return $this->respond($user);
    }
    
    /**
     * Update user
     * PUT /api/users/{id}
     */
    public function update($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        
        $data = $this->request->getJSON(true);
        
        // Jika password kosong, hapus dari data
        if (empty($data['password'])) {
            unset($data['password']);
        }
        
        // Jangan izinkan update avatar_id dan avatar_url langsung
        unset($data['avatar_id']);
        unset($data['avatar_url']);
        
        $this->userModel->update($id, $data);
        
        // Ambil data terbaru
        $updatedUser = $this->userModel->getUserWithAvatar($id);
        $updatedUser = $this->formatUserResponse($updatedUser);
        unset($updatedUser['password']);
        
        return $this->respond([
            'message' => 'User updated successfully',
            'user' => $updatedUser
        ]);
    }
    
    /**
     * Upload/Update avatar user
     * POST /api/users/{id}/avatar
     */
    public function uploadAvatar($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        
        $file = $this->request->getFile('avatar');
        
        if (!$file || !$file->isValid()) {
            return $this->failValidationErrors([
                'avatar' => $file ? $file->getErrorString() : 'No file uploaded'
            ]);
        }
        
        // Validasi tipe file (hanya gambar)
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $mimeType = $file->getClientMimeType();
        
        if (!in_array($mimeType, $allowedTypes)) {
            return $this->fail('Invalid file type. Allowed: JPEG, PNG, GIF, WebP', 400);
        }
        
        // Validasi ukuran (maksimal 2 MB untuk avatar)
        $maxSize = 2 * 1024 * 1024; // 2 MB
        if ($file->getSize() > $maxSize) {
            return $this->fail('Avatar size exceeds maximum limit of 2 MB', 400);
        }
        
        $uploadPath = WRITEPATH . 'cdn/avatars/';
        
        // Buat direktori jika belum ada
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // Generate unique filename
        $originalName = $file->getClientName();
        $extension = $file->getClientExtension() ?: 'jpg';
        $fileName = 'avatar_' . $id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        
        try {
            // Move file
            $file->move($uploadPath, $fileName);
            $filePath = $uploadPath . $fileName;
            $relativePath = 'cdn/avatars/' . $fileName;
            
            // Generate checksum
            $checksum = md5_file($filePath);
            
            // Simpan metadata ke tabel cdn_files
            $cdnData = [
                'file_name' => $fileName,
                'original_name' => $originalName,
                'file_path' => $relativePath,
                'file_type' => $mimeType,
                'file_extension' => strtolower($extension),
                'file_size' => $file->getSize(),
                'mime_type' => $mimeType,
                'checksum' => $checksum,
                'folder' => '/avatars',
                'is_public' => 1,
                'uploaded_by' => $id,
                'download_count' => 0
            ];
            
            $cdnId = $this->cdnModel->insert($cdnData);
            
            if (!$cdnId) {
                unlink($filePath);
                return $this->fail('Failed to save avatar to CDN');
            }
            
            // Hapus avatar lama jika ada
            if ($user['avatar_id']) {
                $oldAvatar = $this->cdnModel->find($user['avatar_id']);
                if ($oldAvatar) {
                    $oldFilePath = WRITEPATH . $oldAvatar['file_path'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                    $this->cdnModel->delete($user['avatar_id']);
                }
            }
            
            // Update user dengan avatar baru
            $avatarUrl = base_url('api/cdn/view/' . $cdnId);
            $this->userModel->updateAvatar($id, $cdnId, $avatarUrl);
            
            // Ambil data terbaru
            $updatedUser = $this->userModel->getUserWithAvatar($id);
            $updatedUser = $this->formatUserResponse($updatedUser);
            unset($updatedUser['password']);
            
            return $this->respond([
                'message' => 'Avatar uploaded successfully',
                'user' => $updatedUser,
                'avatar' => [
                    'id' => $cdnId,
                    'url' => $avatarUrl,
                    'view_url' => base_url('api/cdn/view/' . $cdnId),
                    'download_url' => base_url('api/cdn/download/' . $cdnId),
                    'file_name' => $fileName,
                    'file_size' => $file->getSize()
                ]
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Avatar Upload Error: ' . $e->getMessage());
            return $this->fail('Upload failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Remove avatar user
     * DELETE /api/users/{id}/avatar
     */
    public function removeAvatar($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        
        if (!$user['avatar_id']) {
            return $this->fail('User has no avatar', 400);
        }
        
        // Hapus file dari CDN
        $avatar = $this->cdnModel->find($user['avatar_id']);
        if ($avatar) {
            $filePath = WRITEPATH . $avatar['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $this->cdnModel->delete($user['avatar_id']);
        }
        
        // Hapus referensi avatar dari user
        $this->userModel->removeAvatar($id);
        
        return $this->respond([
            'message' => 'Avatar removed successfully'
        ]);
    }
    
    /**
     * Delete user
     * DELETE /api/users/{id}
     */
    public function delete($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        
        // Hapus avatar jika ada
        if ($user['avatar_id']) {
            $avatar = $this->cdnModel->find($user['avatar_id']);
            if ($avatar) {
                $filePath = WRITEPATH . $avatar['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $this->cdnModel->delete($user['avatar_id']);
            }
        }
        
        $this->userModel->delete($id);
        
        return $this->respond([
            'message' => 'User deleted successfully'
        ]);
    }
    
    /**
     * Assign role to user
     * POST /api/users/assign-role
     */
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
    
    /**
     * Remove role from user
     * POST /api/users/remove-role
     */
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
    
    /**
     * Format user response dengan avatar info
     */
    private function formatUserResponse($user)
    {
        if (!isset($user['avatar_id']) || !$user['avatar_id']) {
            $user['avatar'] = null;
        } else {
            $user['avatar'] = [
                'id' => $user['avatar_id'],
                'url' => $user['avatar_url'] ?? null,
                'view_url' => $user['avatar_id'] ? base_url('api/cdn/view/' . $user['avatar_id']) : null,
                'download_url' => $user['avatar_id'] ? base_url('api/cdn/download/' . $user['avatar_id']) : null,
                'filename' => $user['avatar_filename'] ?? null,
                'path' => $user['avatar_path'] ?? null,
                'size' => $user['avatar_size'] ?? null,
                'mime_type' => $user['avatar_mime'] ?? null
            ];
        }
        
        // Hapus field mentah
        unset($user['avatar_path']);
        unset($user['avatar_filename']);
        unset($user['avatar_size']);
        unset($user['avatar_mime']);
        
        return $user;
    }
}
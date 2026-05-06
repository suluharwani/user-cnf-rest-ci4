<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\RolePermissionModel;
use CodeIgniter\API\ResponseTrait;

class RolePermissionController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new RolePermissionModel();
    }
    
    // Get permissions for specific role
    public function getRolePermissions($roleId)
    {
        $permissions = $this->model->getPermissionsByRole($roleId);
        return $this->respond($permissions);
    }
    
    // Set/Create permission for role
    public function setPermissions()
    {
        $rules = [
            'role_id' => 'required|integer',
            'permissions' => 'required'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        $roleId = $data['role_id'];
        $permissions = $data['permissions']; // Array of permissions
        
        // Hapus permission lama
        $db = \Config\Database::connect();
        $db->table('role_permissions')->where('role_id', $roleId)->delete();
        
        // Insert permissions baru
        foreach ($permissions as $perm) {
            $this->model->insert([
                'role_id' => $roleId,
                'application_id' => $perm['application_id'],
                'module_id' => $perm['module_id'],
                'can_create' => $perm['can_create'] ?? 0,
                'can_read' => $perm['can_read'] ?? 0,
                'can_update' => $perm['can_update'] ?? 0,
                'can_delete' => $perm['can_delete'] ?? 0
            ]);
        }
        
        return $this->respond(['message' => 'Permissions updated successfully']);
    }
    
    // Check user access
    public function checkAccess($userId, $moduleCode, $action)
    {
        $db = \Config\Database::connect();
        
        $builder = $db->table('role_permissions');
        $builder->select('role_permissions.*');
        $builder->join('user_roles', 'user_roles.role_id = role_permissions.role_id');
        $builder->join('modules', 'modules.id = role_permissions.module_id');
        $builder->where('user_roles.user_id', $userId);
        $builder->where('modules.module_code', $moduleCode);
        
        $permissions = $builder->get()->getResultArray();
        
        $hasAccess = false;
        $columnMap = [
            'create' => 'can_create',
            'read' => 'can_read',
            'update' => 'can_update',
            'delete' => 'can_delete'
        ];
        
        if (isset($columnMap[$action])) {
            foreach ($permissions as $permission) {
                if ($permission[$columnMap[$action]] == 1) {
                    $hasAccess = true;
                    break;
                }
            }
        }
        
        return $this->respond([
            'has_access' => $hasAccess,
            'user_id' => $userId,
            'module' => $moduleCode,
            'action' => $action
        ]);
    }
}
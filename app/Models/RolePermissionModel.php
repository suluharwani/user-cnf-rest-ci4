<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table = 'role_permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'role_id', 'application_id', 'module_id',
        'can_create', 'can_read', 'can_update', 'can_delete'
    ];
    
    public function getPermissionsByRole($roleId)
    {
        return $this->select('role_permissions.*, applications.app_name, modules.module_name')
                    ->join('applications', 'applications.id = role_permissions.application_id')
                    ->join('modules', 'modules.id = role_permissions.module_id')
                    ->where('role_permissions.role_id', $roleId)
                    ->findAll();
    }
}
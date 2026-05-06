<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = ['role_name', 'role_code', 'description'];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
}
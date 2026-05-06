<?php

namespace App\Models;

use CodeIgniter\Model;

class ApplicationModel extends Model
{
    protected $table = 'applications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = ['app_name', 'app_code', 'description'];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
}
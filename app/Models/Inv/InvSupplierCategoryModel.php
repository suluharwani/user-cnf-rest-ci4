<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvSupplierCategoryModel extends Model
{
    protected $table = 'inv_supplier_categories';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'category_code', 'category_name', 'description', 'is_active'
    ];
    
    protected $useTimestamps = true;
}
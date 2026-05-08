<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvSupplierContactModel extends Model
{
    protected $table = 'inv_supplier_contacts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'supplier_id', 'contact_name', 'position', 'department',
        'phone', 'mobile', 'email', 'is_primary', 'is_active', 'notes'
    ];
    
    protected $useTimestamps = true;
    
    public function getPrimaryContact($supplierId)
    {
        return $this->where('supplier_id', $supplierId)
                    ->where('is_primary', 1)
                    ->first();
    }
}
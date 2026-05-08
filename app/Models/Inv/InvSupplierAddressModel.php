<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvSupplierAddressModel extends Model
{
    protected $table = 'inv_supplier_addresses';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'supplier_id', 'address_type', 'address', 'city', 'province',
        'country', 'postal_code', 'is_default', 'is_active', 'notes'
    ];
    
    protected $useTimestamps = true;
}
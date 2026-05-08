<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvCustomerAddressModel extends Model
{
    protected $table = 'inv_customer_addresses';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'customer_id', 'address_type', 'address', 'city', 'province',
        'country', 'postal_code', 'is_default', 'is_active', 'notes'
    ];
    
    protected $useTimestamps = true;
    
    public function getDefaultAddress($customerId, $type = null)
    {
        $builder = $this->where('customer_id', $customerId)
                        ->where('is_default', 1);
        
        if ($type) $builder->where('address_type', $type);
        
        return $builder->first();
    }
    
    public function setDefault($id, $customerId)
    {
        $this->where('customer_id', $customerId)->set('is_default', 0)->update();
        return $this->update($id, ['is_default' => 1]);
    }
}
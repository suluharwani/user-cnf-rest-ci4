<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvCustomerContactModel extends Model
{
    protected $table = 'inv_customer_contacts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'customer_id', 'contact_name', 'position', 'department',
        'phone', 'mobile', 'email', 'is_primary', 'is_active', 'notes'
    ];
    
    protected $useTimestamps = true;
    
    public function getPrimaryContact($customerId)
    {
        return $this->where('customer_id', $customerId)
                    ->where('is_primary', 1)
                    ->first();
    }
    
    public function setPrimary($id, $customerId)
    {
        $this->where('customer_id', $customerId)->set('is_primary', 0)->update();
        return $this->update($id, ['is_primary' => 1]);
    }
}
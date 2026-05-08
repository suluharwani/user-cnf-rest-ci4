<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvCustomerModel extends Model
{
    protected $table = 'inv_customers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'customer_code', 'customer_name', 'customer_type', 'group_id',
        'tax_id', 'identity_number', 'address', 'city', 'province', 'country', 'postal_code',
        'phone', 'mobile', 'email', 'website',
        'contact_person', 'contact_phone', 'contact_email',
        'bank_name', 'bank_branch', 'bank_account_number', 'bank_account_name',
        'currency_id', 'payment_terms', 'credit_limit',
        'current_balance', 'total_purchases', 'last_transaction_date',
        'status', 'blocked_reason', 'notes', 'documents', 'created_by'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $casts = ['documents' => 'json'];
    
    /**
     * Generate customer code
     */
    public function generateCode()
    {
        $count = $this->countAll() + 1;
        return 'CUST-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get customer with details
     */
    public function getWithDetails($id = null)
    {
        $builder = $this->select('inv_customers.*, 
                                  inv_customer_groups.group_name, inv_customer_groups.discount_percent,
                                  inv_currencies.code as currency_code, inv_currencies.symbol as currency_symbol')
                        ->join('inv_customer_groups', 'inv_customer_groups.id = inv_customers.group_id', 'left')
                        ->join('inv_currencies', 'inv_currencies.id = inv_customers.currency_id', 'left')
                        ->where('inv_customers.status !=', 'deleted');
        
        if ($id) {
            $customer = $builder->where('inv_customers.id', $id)->first();
            if ($customer) {
                $customer['contacts'] = $this->getContacts($id);
                $customer['addresses'] = $this->getAddresses($id);
                $customer['documents'] = $this->getDocuments($id);
            }
            return $customer;
        }
        
        return $builder->findAll();
    }
    
    /**
     * Get customer contacts
     */
    public function getContacts($customerId)
    {
        $contactModel = new InvCustomerContactModel();
        return $contactModel->where('customer_id', $customerId)->findAll();
    }
    
    /**
     * Get customer addresses
     */
    public function getAddresses($customerId)
    {
        $addressModel = new InvCustomerAddressModel();
        return $addressModel->where('customer_id', $customerId)->findAll();
    }
    
    /**
     * Get customer documents
     */
    public function getDocuments($customerId)
    {
        $docModel = new InvBusinessDocumentModel();
        return $docModel->where('reference_type', 'customer')
                        ->where('reference_id', $customerId)
                        ->findAll();
    }
    
    /**
     * Get active customers
     */
    public function getActive()
    {
        return $this->where('status', 'active')->findAll();
    }
    
    /**
     * Get customers by group
     */
    public function getByGroup($groupId)
    {
        return $this->where('group_id', $groupId)->where('status', 'active')->findAll();
    }
    
    /**
     * Update customer balance
     */
    public function updateBalance($id, $amount, $isIncrement = true)
    {
        $operator = $isIncrement ? '+' : '-';
        return $this->set('current_balance', "current_balance $operator $amount", false)
                    ->set('total_purchases', "total_purchases $operator $amount", false)
                    ->set('last_transaction_date', date('Y-m-d'))
                    ->where('id', $id)
                    ->update();
    }
    
    /**
     * Block customer
     */
    public function block($id, $reason)
    {
        return $this->update($id, [
            'status' => 'blocked',
            'blocked_reason' => $reason
        ]);
    }
}
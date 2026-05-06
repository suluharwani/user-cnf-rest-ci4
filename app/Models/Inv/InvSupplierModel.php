<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvSupplierModel extends Model
{
    protected $table = 'inv_suppliers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'supplier_code', 'supplier_name', 'supplier_type', 'country', 'city',
        'address', 'phone', 'email', 'contact_person', 'tax_id',
        'bank_name', 'bank_account', 'bank_swift', 'currency_id',
        'payment_terms', 'lead_time_days', 'min_order_qty', 'rating',
        'notes', 'is_active'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get international suppliers
     */
    public function getInternational()
    {
        return $this->where('supplier_type', 'international')
                    ->where('is_active', 1)
                    ->findAll();
    }
    
    /**
     * Get local suppliers
     */
    public function getLocal()
    {
        return $this->where('supplier_type', 'local')
                    ->where('is_active', 1)
                    ->findAll();
    }
    
    /**
     * Get supplier with currency
     */
    public function getWithCurrency($id = null)
    {
        $builder = $this->select('inv_suppliers.*, inv_currencies.code as currency_code, inv_currencies.symbol as currency_symbol')
                        ->join('inv_currencies', 'inv_currencies.id = inv_suppliers.currency_id', 'left');
        
        if ($id) {
            return $builder->where('inv_suppliers.id', $id)->first();
        }
        
        return $builder->findAll();
    }
}
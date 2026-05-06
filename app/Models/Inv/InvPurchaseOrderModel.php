<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvPurchaseOrderModel extends Model
{
    protected $table = 'inv_purchase_orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'po_number', 'supplier_id', 'po_date', 'expected_date',
        'currency_id', 'exchange_rate', 'total_amount', 'status',
        'shipping_method', 'shipping_terms', 'port_of_loading', 'port_of_discharge',
        'incoterms', 'payment_terms', 'notes', 'approved_by', 'created_by'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Generate PO number
     */
    public function generatePONumber()
    {
        $yearMonth = date('Ym');
        $count = $this->where('po_number LIKE', 'PO-' . $yearMonth . '%')->countAllResults();
        return 'PO-' . $yearMonth . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get PO with supplier and currency
     */
    public function getWithDetails($id = null)
    {
        $builder = $this->select('inv_purchase_orders.*, 
                                  inv_suppliers.supplier_name, inv_suppliers.supplier_code,
                                  inv_currencies.code as currency_code, inv_currencies.symbol as currency_symbol,
                                  creator.fullname as created_by_name, approver.fullname as approved_by_name')
                        ->join('inv_suppliers', 'inv_suppliers.id = inv_purchase_orders.supplier_id')
                        ->join('inv_currencies', 'inv_currencies.id = inv_purchase_orders.currency_id')
                        ->join('users as creator', 'creator.id = inv_purchase_orders.created_by', 'left')
                        ->join('users as approver', 'approver.id = inv_purchase_orders.approved_by', 'left');
        
        if ($id) {
            return $builder->where('inv_purchase_orders.id', $id)->first();
        }
        
        return $builder->orderBy('inv_purchase_orders.po_date', 'DESC')->findAll();
    }
    
    /**
     * Get PO items
     */
    public function getItems($poId)
    {
        $itemModel = new InvPoItemModel();
        return $itemModel->getByPO($poId);
    }
}
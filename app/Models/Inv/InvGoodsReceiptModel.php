<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvGoodsReceiptModel extends Model
{
    protected $table = 'inv_goods_receipts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'gr_number', 'po_id', 'receipt_date', 'supplier_id', 'warehouse_id',
        'status', 'notes', 'received_by', 'inspected_by'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Generate GR number
     */
    public function generateGRNumber()
    {
        $yearMonth = date('Ym');
        $count = $this->where('gr_number LIKE', 'GR-' . $yearMonth . '%')->countAllResults();
        return 'GR-' . $yearMonth . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get GR with details
     */
    public function getWithDetails($id = null)
    {
        $builder = $this->select('inv_goods_receipts.*, 
                                  inv_suppliers.supplier_name, inv_suppliers.supplier_code,
                                  inv_warehouses.warehouse_name,
                                  inv_purchase_orders.po_number,
                                  receiver.fullname as received_by_name,
                                  inspector.fullname as inspected_by_name')
                        ->join('inv_suppliers', 'inv_suppliers.id = inv_goods_receipts.supplier_id')
                        ->join('inv_warehouses', 'inv_warehouses.id = inv_goods_receipts.warehouse_id')
                        ->join('inv_purchase_orders', 'inv_purchase_orders.id = inv_goods_receipts.po_id', 'left')
                        ->join('users as receiver', 'receiver.id = inv_goods_receipts.received_by', 'left')
                        ->join('users as inspector', 'inspector.id = inv_goods_receipts.inspected_by', 'left');
        
        if ($id) {
            return $builder->where('inv_goods_receipts.id', $id)->first();
        }
        
        return $builder->orderBy('inv_goods_receipts.receipt_date', 'DESC')->findAll();
    }
}
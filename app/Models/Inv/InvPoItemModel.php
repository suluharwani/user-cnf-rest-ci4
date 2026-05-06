<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvPoItemModel extends Model
{
    protected $table = 'inv_po_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'po_id', 'material_id', 'quantity', 'uom_id',
        'unit_price', 'total_price', 'received_qty', 'notes'
    ];
    
    /**
     * Get items by PO ID
     */
    public function getByPO($poId)
    {
        return $this->select('inv_po_items.*, inv_materials.material_name, inv_materials.material_code,
                             inv_materials.material_type, inv_materials.density,
                             inv_uom.uom_code, inv_uom.uom_name')
                    ->join('inv_materials', 'inv_materials.id = inv_po_items.material_id')
                    ->join('inv_uom', 'inv_uom.id = inv_po_items.uom_id')
                    ->where('inv_po_items.po_id', $poId)
                    ->findAll();
    }
    
    /**
     * Get items with remaining quantity to receive
     */
    public function getPendingReceipt($poId)
    {
        return $this->select('inv_po_items.*, 
                             (inv_po_items.quantity - inv_po_items.received_qty) as remaining_qty,
                             inv_materials.material_name, inv_materials.material_code')
                    ->join('inv_materials', 'inv_materials.id = inv_po_items.material_id')
                    ->where('inv_po_items.po_id', $poId)
                    ->where('inv_po_items.received_qty < inv_po_items.quantity')
                    ->findAll();
    }
    
    /**
     * Update received quantity
     */
    public function updateReceivedQty($itemId, $receivedQty)
    {
        $item = $this->find($itemId);
        if (!$item) return false;
        
        $newReceivedQty = $item['received_qty'] + $receivedQty;
        
        return $this->update($itemId, [
            'received_qty' => $newReceivedQty
        ]);
    }
    
    /**
     * Check if all items in PO are fully received
     */
    public function isPOFullyReceived($poId)
    {
        $pendingCount = $this->where('po_id', $poId)
                             ->where('received_qty < quantity')
                             ->countAllResults();
        
        return $pendingCount === 0;
    }
    
    /**
     * Get total received value
     */
    public function getTotalReceived($poId)
    {
        $result = $this->selectSum('total_price')
                       ->where('po_id', $poId)
                       ->first();
        
        return $result['total_price'] ?? 0;
    }
}
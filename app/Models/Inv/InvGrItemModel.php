<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvGrItemModel extends Model
{
    protected $table = 'inv_gr_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'gr_id', 'po_item_id', 'material_id', 'quantity_received',
        'quantity_accepted', 'quantity_rejected', 'rack_id', 'batch_number',
        'production_date', 'expiry_date', 'quality_grade',
        'moisture_content', 'notes'
    ];
    
    /**
     * Get items by GR
     */
    public function getByGR($grId)
    {
        return $this->select('inv_gr_items.*, inv_materials.material_name, inv_materials.material_code,
                             inv_materials.material_type, inv_uom.uom_code, inv_racks.rack_name')
                    ->join('inv_materials', 'inv_materials.id = inv_gr_items.material_id')
                    ->join('inv_uom', 'inv_uom.id = inv_materials.uom_id')
                    ->join('inv_racks', 'inv_racks.id = inv_gr_items.rack_id', 'left')
                    ->where('inv_gr_items.gr_id', $grId)
                    ->findAll();
    }
    
    /**
     * Get items pending inspection
     */
    public function getPendingInspection($warehouseId = null)
    {
        $builder = $this->select('inv_gr_items.*, inv_goods_receipts.gr_number, inv_goods_receipts.receipt_date,
                                  inv_materials.material_name, inv_materials.material_code')
                        ->join('inv_goods_receipts', 'inv_goods_receipts.id = inv_gr_items.gr_id')
                        ->join('inv_materials', 'inv_materials.id = inv_gr_items.material_id')
                        ->where('inv_goods_receipts.status', 'pending_inspection');
        
        if ($warehouseId) {
            $builder->where('inv_goods_receipts.warehouse_id', $warehouseId);
        }
        
        return $builder->findAll();
    }
}
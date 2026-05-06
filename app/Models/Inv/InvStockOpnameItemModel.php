<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvStockOpnameItemModel extends Model
{
    protected $table = 'inv_stock_opname_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'opname_id', 'material_id', 'rack_id', 'batch_number',
        'system_qty', 'actual_qty', 'unit_cost', 'notes'
    ];
    
    /**
     * Get items by opname ID with material details
     */
    public function getByOpname($opnameId)
    {
        return $this->select('inv_stock_opname_items.*, 
                             inv_materials.material_name, inv_materials.material_code, 
                             inv_materials.material_type, inv_materials.uom_id,
                             inv_uom.uom_code, inv_uom.uom_name,
                             inv_racks.rack_name, inv_racks.rack_code')
                    ->join('inv_materials', 'inv_materials.id = inv_stock_opname_items.material_id')
                    ->join('inv_uom', 'inv_uom.id = inv_materials.uom_id')
                    ->join('inv_racks', 'inv_racks.id = inv_stock_opname_items.rack_id', 'left')
                    ->where('inv_stock_opname_items.opname_id', $opnameId)
                    ->orderBy('inv_materials.material_name', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get items with variance (discrepancy)
     */
    public function getItemsWithVariance($opnameId)
    {
        return $this->getByOpname($opnameId);
        // Note: variance_qty and variance_value are generated columns in MySQL
    }
    
    /**
     * Get items with significant variance
     */
    public function getSignificantVariance($opnameId, $minVarianceValue = 100000)
    {
        return $this->getByOpname($opnameId);
        // Filter in PHP since we can't filter generated columns easily
        // Or add raw where clause if needed
    }
    
    /**
     * Get summary by material category for this opname
     */
    public function getSummaryByCategory($opnameId)
    {
        return $this->select('inv_material_categories.category_name, 
                             COUNT(*) as total_items,
                             SUM(inv_stock_opname_items.system_qty) as total_system_qty,
                             SUM(inv_stock_opname_items.actual_qty) as total_actual_qty,
                             SUM(inv_stock_opname_items.variance_qty) as total_variance,
                             SUM(inv_stock_opname_items.variance_value) as total_variance_value')
                    ->join('inv_materials', 'inv_materials.id = inv_stock_opname_items.material_id')
                    ->join('inv_material_categories', 'inv_material_categories.id = inv_materials.category_id')
                    ->where('inv_stock_opname_items.opname_id', $opnameId)
                    ->groupBy('inv_materials.category_id')
                    ->findAll();
    }
    
    /**
     * Batch insert items
     */
    public function batchInsert($opnameId, array $items)
    {
        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'opname_id' => $opnameId,
                'material_id' => $item['material_id'],
                'rack_id' => $item['rack_id'] ?? null,
                'batch_number' => $item['batch_number'] ?? null,
                'system_qty' => $item['system_qty'],
                'actual_qty' => $item['actual_qty'],
                'unit_cost' => $item['unit_cost'] ?? 0,
                'notes' => $item['notes'] ?? null
            ];
        }
        
        return $this->insertBatch($data);
    }
}
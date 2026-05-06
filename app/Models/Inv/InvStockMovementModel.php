<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvStockMovementModel extends Model
{
    protected $table = 'inv_stock_movements';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'movement_type', 'material_id', 'from_warehouse_id', 'to_warehouse_id',
        'from_rack_id', 'to_rack_id', 'batch_number', 'quantity',
        'volume_m3', 'weight_kg', 'reference_type', 'reference_id',
        'reference_number', 'unit_cost', 'total_cost', 'currency_id',
        'notes', 'created_by'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null; // No updated_at needed for movements
    
    /**
     * Get movements by material
     */
    public function getByMaterial($materialId, $limit = 100)
    {
        return $this->select('inv_stock_movements.*, 
                             inv_materials.material_name, inv_materials.material_code, inv_materials.material_type,
                             fw.warehouse_name as from_warehouse, fw.warehouse_code as from_wh_code,
                             tw.warehouse_name as to_warehouse, tw.warehouse_code as to_wh_code,
                             creator.fullname as created_by_name')
                    ->join('inv_materials', 'inv_materials.id = inv_stock_movements.material_id')
                    ->join('inv_warehouses fw', 'fw.id = inv_stock_movements.from_warehouse_id', 'left')
                    ->join('inv_warehouses tw', 'tw.id = inv_stock_movements.to_warehouse_id', 'left')
                    ->join('users as creator', 'creator.id = inv_stock_movements.created_by', 'left')
                    ->where('inv_stock_movements.material_id', $materialId)
                    ->orderBy('inv_stock_movements.created_at', 'DESC')
                    ->findAll($limit);
    }
    
    /**
     * Get movements by date range
     */
    public function getByDateRange($startDate, $endDate, $movementType = null, $warehouseId = null)
    {
        $builder = $this->select('inv_stock_movements.*, 
                                 inv_materials.material_name, inv_materials.material_code,
                                 fw.warehouse_name as from_warehouse, tw.warehouse_name as to_warehouse')
                        ->join('inv_materials', 'inv_materials.id = inv_stock_movements.material_id')
                        ->join('inv_warehouses fw', 'fw.id = inv_stock_movements.from_warehouse_id', 'left')
                        ->join('inv_warehouses tw', 'tw.id = inv_stock_movements.to_warehouse_id', 'left')
                        ->where('DATE(inv_stock_movements.created_at) >=', $startDate)
                        ->where('DATE(inv_stock_movements.created_at) <=', $endDate);
        
        if ($movementType) {
            $builder->where('inv_stock_movements.movement_type', $movementType);
        }
        
        if ($warehouseId) {
            $builder->groupStart()
                    ->where('inv_stock_movements.from_warehouse_id', $warehouseId)
                    ->orWhere('inv_stock_movements.to_warehouse_id', $warehouseId)
                    ->groupEnd();
        }
        
        return $builder->orderBy('inv_stock_movements.created_at', 'DESC')->findAll();
    }
    
    /**
     * Get stock card (movement history for specific stock location)
     */
    public function getStockCard($materialId, $warehouseId, $batchNumber = null, $rackId = null)
    {
        $builder = $this->select('inv_stock_movements.*,
                                 fw.warehouse_name as from_warehouse, tw.warehouse_name as to_warehouse,
                                 fr.rack_name as from_rack, tr.rack_name as to_rack')
                        ->join('inv_warehouses fw', 'fw.id = inv_stock_movements.from_warehouse_id', 'left')
                        ->join('inv_warehouses tw', 'tw.id = inv_stock_movements.to_warehouse_id', 'left')
                        ->join('inv_racks fr', 'fr.id = inv_stock_movements.from_rack_id', 'left')
                        ->join('inv_racks tr', 'tr.id = inv_stock_movements.to_rack_id', 'left')
                        ->where('inv_stock_movements.material_id', $materialId)
                        ->groupStart()
                            ->where('inv_stock_movements.from_warehouse_id', $warehouseId)
                            ->orWhere('inv_stock_movements.to_warehouse_id', $warehouseId)
                        ->groupEnd();
        
        if ($batchNumber) {
            $builder->where('inv_stock_movements.batch_number', $batchNumber);
        }
        
        if ($rackId) {
            $builder->groupStart()
                        ->where('inv_stock_movements.from_rack_id', $rackId)
                        ->orWhere('inv_stock_movements.to_rack_id', $rackId)
                    ->groupEnd();
        }
        
        return $builder->orderBy('inv_stock_movements.created_at', 'ASC')->findAll();
    }
    
    /**
     * Get inventory valuation report
     */
    public function getInventoryValuation($warehouseId = null, $currencyId = null)
    {
        $builder = $this->select('inv_materials.id, inv_materials.material_code, inv_materials.material_name,
                                  inv_materials.material_type, inv_material_categories.category_name,
                                  SUM(inv_stock_movements.quantity) as total_qty_in,
                                  AVG(CASE WHEN inv_stock_movements.quantity > 0 THEN inv_stock_movements.unit_cost END) as avg_cost,
                                  SUM(CASE WHEN inv_stock_movements.quantity > 0 THEN inv_stock_movements.total_cost END) as total_value')
                        ->join('inv_materials', 'inv_materials.id = inv_stock_movements.material_id')
                        ->join('inv_material_categories', 'inv_material_categories.id = inv_materials.category_id', 'left')
                        ->whereIn('inv_stock_movements.movement_type', ['receipt', 'stock_opname_in']);
        
        if ($warehouseId) {
            $builder->where('inv_stock_movements.to_warehouse_id', $warehouseId);
        }
        
        if ($currencyId) {
            $builder->where('inv_stock_movements.currency_id', $currencyId);
        }
        
        return $builder->groupBy('inv_materials.id')
                       ->having('total_qty_in > 0')
                       ->findAll();
    }
    
    /**
     * Get movement summary by type
     */
    public function getMovementSummary($startDate, $endDate)
    {
        return $this->select('movement_type, COUNT(*) as total_movements, 
                             SUM(ABS(quantity)) as total_quantity,
                             SUM(ABS(volume_m3)) as total_volume,
                             SUM(ABS(total_cost)) as total_value')
                    ->where('DATE(created_at) >=', $startDate)
                    ->where('DATE(created_at) <=', $endDate)
                    ->groupBy('movement_type')
                    ->findAll();
    }
    
    /**
     * Get recent movements
     */
    public function getRecentMovements($limit = 50)
    {
        return $this->select('inv_stock_movements.*, 
                             inv_materials.material_name, inv_materials.material_code,
                             fw.warehouse_name as from_warehouse, tw.warehouse_name as to_warehouse,
                             creator.fullname as created_by_name')
                    ->join('inv_materials', 'inv_materials.id = inv_stock_movements.material_id')
                    ->join('inv_warehouses fw', 'fw.id = inv_stock_movements.from_warehouse_id', 'left')
                    ->join('inv_warehouses tw', 'tw.id = inv_stock_movements.to_warehouse_id', 'left')
                    ->join('users as creator', 'creator.id = inv_stock_movements.created_by', 'left')
                    ->orderBy('inv_stock_movements.created_at', 'DESC')
                    ->findAll($limit);
    }
}
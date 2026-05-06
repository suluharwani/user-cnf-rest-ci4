<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvStockModel extends Model
{
    protected $table = 'inv_stock';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'material_id', 'warehouse_id', 'rack_id', 'batch_number',
        'quantity', 'volume_m3', 'weight_kg', 'reserved_qty', 'last_counted_at'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get stock by material
     */
    public function getStockByMaterial($materialId)
    {
        return $this->select('inv_stock.*, inv_warehouses.warehouse_name, inv_racks.rack_name')
                    ->join('inv_warehouses', 'inv_warehouses.id = inv_stock.warehouse_id')
                    ->join('inv_racks', 'inv_racks.id = inv_stock.rack_id', 'left')
                    ->where('inv_stock.material_id', $materialId)
                    ->findAll();
    }
    
    /**
     * Get stock by warehouse
     */
    public function getStockByWarehouse($warehouseId)
    {
        return $this->select('inv_stock.*, inv_materials.material_name, inv_materials.material_code')
                    ->join('inv_materials', 'inv_materials.id = inv_stock.material_id')
                    ->where('inv_stock.warehouse_id', $warehouseId)
                    ->findAll();
    }
    
    /**
     * Update stock quantity
     */
    public function updateStock($materialId, $warehouseId, $rackId, $batchNumber, $qtyChange, $volumeChange = 0)
    {
        $existing = $this->where([
            'material_id' => $materialId,
            'warehouse_id' => $warehouseId,
            'rack_id' => $rackId,
            'batch_number' => $batchNumber
        ])->first();
        
        if ($existing) {
            $newQty = $existing['quantity'] + $qtyChange;
            $newVolume = $existing['volume_m3'] + $volumeChange;
            
            return $this->update($existing['id'], [
                'quantity' => max(0, $newQty),
                'volume_m3' => max(0, $newVolume)
            ]);
        }
        
        if ($qtyChange > 0) {
            return $this->insert([
                'material_id' => $materialId,
                'warehouse_id' => $warehouseId,
                'rack_id' => $rackId,
                'batch_number' => $batchNumber,
                'quantity' => max(0, $qtyChange),
                'volume_m3' => max(0, $volumeChange)
            ]);
        }
        
        return false;
    }
    
    /**
     * Get total stock value
     */
    public function getStockValue($warehouseId = null)
    {
        $builder = $this->select('SUM(inv_stock.quantity * COALESCE(inv_stock_movements.unit_cost, 0)) as total_value')
                        ->join('inv_stock_movements', 'inv_stock_movements.material_id = inv_stock.material_id', 'left')
                        ->where('inv_stock.quantity >', 0);
        
        if ($warehouseId) {
            $builder->where('inv_stock.warehouse_id', $warehouseId);
        }
        
        return $builder->first()['total_value'] ?? 0;
    }
}
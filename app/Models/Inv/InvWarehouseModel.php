<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvWarehouseModel extends Model
{
    protected $table = 'inv_warehouses';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'warehouse_code', 'warehouse_name', 'warehouse_type', 'location',
        'address', 'capacity_volume', 'capacity_weight', 'manager_id', 'is_active'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get warehouse with manager info
     */
    public function getWithManager()
    {
        return $this->select('inv_warehouses.*, users.fullname as manager_name')
                    ->join('users', 'users.id = inv_warehouses.manager_id', 'left')
                    ->where('inv_warehouses.is_active', 1)
                    ->findAll();
    }
    
    /**
     * Get warehouse utilization
     */
    public function getUtilization($warehouseId)
    {
        $stockModel = new InvStockModel();
        
        $warehouse = $this->find($warehouseId);
        if (!$warehouse) return null;
        
        $totalVolume = $stockModel->selectSum('volume_m3')
                                  ->where('warehouse_id', $warehouseId)
                                  ->first()['volume_m3'] ?? 0;
        
        $totalWeight = $stockModel->selectSum('weight_kg')
                                  ->where('warehouse_id', $warehouseId)
                                  ->first()['weight_kg'] ?? 0;
        
        return [
            'warehouse' => $warehouse,
            'volume_used' => $totalVolume,
            'volume_capacity' => $warehouse['capacity_volume'],
            'volume_utilization_pct' => $warehouse['capacity_volume'] ? round(($totalVolume / $warehouse['capacity_volume']) * 100, 2) : 0,
            'weight_used' => $totalWeight,
            'weight_capacity' => $warehouse['capacity_weight'],
            'weight_utilization_pct' => $warehouse['capacity_weight'] ? round(($totalWeight / $warehouse['capacity_weight']) * 100, 2) : 0
        ];
    }
}
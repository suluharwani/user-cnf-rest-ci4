<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvRackModel extends Model
{
    protected $table = 'inv_racks';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'rack_code', 'rack_name', 'warehouse_id', 'zone',
        'aisle', 'bay', 'level', 'capacity_volume', 'capacity_weight', 'is_active'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get racks by warehouse
     */
    public function getByWarehouse($warehouseId)
    {
        return $this->where('warehouse_id', $warehouseId)
                    ->where('is_active', 1)
                    ->orderBy('zone, aisle, bay, level')
                    ->findAll();
    }
    
    /**
     * Get available racks (with capacity info)
     */
    public function getAvailableRacks($warehouseId)
    {
        $stockModel = new InvStockModel();
        
        $racks = $this->getByWarehouse($warehouseId);
        $result = [];
        
        foreach ($racks as $rack) {
            $usedVolume = $stockModel->selectSum('volume_m3')
                                     ->where('rack_id', $rack['id'])
                                     ->first()['volume_m3'] ?? 0;
            
            $rack['used_volume'] = $usedVolume;
            $rack['available_volume'] = $rack['capacity_volume'] - $usedVolume;
            $rack['is_full'] = $rack['capacity_volume'] ? ($usedVolume >= $rack['capacity_volume']) : false;
            
            $result[] = $rack;
        }
        
        return $result;
    }
    
    /**
     * Generate rack code
     */
    public function generateCode($warehouseId, $zone, $aisle, $bay, $level)
    {
        return strtoupper("{$zone}-{$aisle}{$bay}-{$level}");
    }
}
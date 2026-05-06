<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvMaterialModel extends Model
{
    protected $table = 'inv_materials';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'material_code', 'material_name', 'category_id', 'material_type',
        'uom_id', 'density', 'moisture_content', 'wood_grade', 'wood_species',
        'fabric_width', 'fabric_pattern', 'color_code', 'color_name',
        'min_stock', 'max_stock', 'lead_time_days',
        'is_batch_tracked', 'requires_inspection', 'shelf_life_days',
        'storage_condition', 'handling_instruction', 'safety_data',
        'notes', 'is_active'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get materials with category, UOM, and current stock
     */
    public function getMaterialsWithStock($warehouseId = null)
    {
        $builder = $this->select('inv_materials.*, inv_material_categories.category_name, inv_uom.uom_code, inv_uom.uom_name, SUM(inv_stock.available_qty) as total_stock, SUM(inv_stock.volume_m3) as total_volume_m3')
                        ->join('inv_material_categories', 'inv_material_categories.id = inv_materials.category_id', 'left')
                        ->join('inv_uom', 'inv_uom.id = inv_materials.uom_id', 'left')
                        ->join('inv_stock', 'inv_stock.material_id = inv_materials.id', 'left')
                        ->groupBy('inv_materials.id');
        
        if ($warehouseId) {
            $builder->where('inv_stock.warehouse_id', $warehouseId);
        }
        
        return $builder->findAll();
    }
    
    /**
     * Get materials by category
     */
    public function getByCategory($categoryId)
    {
        return $this->where('category_id', $categoryId)->findAll();
    }
    
    /**
     * Get materials by type
     */
    public function getByType($materialType)
    {
        return $this->where('material_type', $materialType)->findAll();
    }
    
    /**
     * Get low stock materials
     */
    public function getLowStock()
    {
        return $this->select('inv_materials.*, SUM(inv_stock.available_qty) as total_stock')
                    ->join('inv_stock', 'inv_stock.material_id = inv_materials.id', 'left')
                    ->groupBy('inv_materials.id')
                    ->having('total_stock <= inv_materials.min_stock OR total_stock IS NULL')
                    ->findAll();
    }
    
    /**
     * Generate material code
     */
    public function generateCode($categoryCode, $materialType)
    {
        $prefix = strtoupper(substr($categoryCode, 0, 3));
        $typePrefix = strtoupper(substr($materialType, 0, 2));
        
        $lastMaterial = $this->where('material_code LIKE', $prefix . '-' . $typePrefix . '%')
                             ->orderBy('id', 'DESC')
                             ->first();
        
        if ($lastMaterial) {
            $lastNumber = intval(substr($lastMaterial['material_code'], -5));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . '-' . $typePrefix . '-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }
}
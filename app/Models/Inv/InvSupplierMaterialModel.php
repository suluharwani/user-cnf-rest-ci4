<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvSupplierMaterialModel extends Model
{
    protected $table = 'inv_supplier_materials';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'supplier_id', 'material_id', 'supplier_material_code', 'supplier_material_name',
        'unit_price', 'currency_id', 'min_order_qty', 'lead_time_days',
        'is_preferred', 'last_purchase_date', 'notes'
    ];
    
    protected $useTimestamps = true;
    
    /**
     * Get materials by supplier
     */
    public function getBySupplier($supplierId)
    {
        return $this->select('inv_supplier_materials.*, inv_materials.material_name, inv_materials.material_code, inv_currencies.code as currency_code')
                    ->join('inv_materials', 'inv_materials.id = inv_supplier_materials.material_id')
                    ->join('inv_currencies', 'inv_currencies.id = inv_supplier_materials.currency_id')
                    ->where('inv_supplier_materials.supplier_id', $supplierId)
                    ->findAll();
    }
    
    /**
     * Get suppliers for a material (for procurement)
     */
    public function getSuppliersForMaterial($materialId)
    {
        return $this->select('inv_supplier_materials.*, inv_suppliers.supplier_name, inv_suppliers.supplier_code')
                    ->join('inv_suppliers', 'inv_suppliers.id = inv_supplier_materials.supplier_id')
                    ->where('inv_supplier_materials.material_id', $materialId)
                    ->where('inv_suppliers.status', 'active')
                    ->orderBy('inv_supplier_materials.is_preferred', 'DESC')
                    ->orderBy('inv_supplier_materials.unit_price', 'ASC')
                    ->findAll();
    }
}
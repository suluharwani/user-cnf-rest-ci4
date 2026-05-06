<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvUomModel extends Model
{
    protected $table = 'inv_uom';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'uom_code', 'uom_name', 'uom_type', 'description'
    ];
    
    /**
     * Get UOM by type
     */
    public function getByType($type)
    {
        return $this->where('uom_type', $type)->findAll();
    }
    
    /**
     * Get UOM for wood materials (volume-based)
     */
    public function getVolumeUoms()
    {
        return $this->whereIn('uom_type', ['volume'])->findAll();
    }
    
    /**
     * Get UOM for fabric materials
     */
    public function getFabricUoms()
    {
        return $this->whereIn('uom_type', ['area', 'roll', 'meter'])->findAll();
    }
}
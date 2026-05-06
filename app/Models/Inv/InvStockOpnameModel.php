<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvStockOpnameModel extends Model
{
    protected $table = 'inv_stock_opname';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'opname_number', 'opname_date', 'warehouse_id', 'opname_type',
        'status', 'total_items', 'items_matched', 'items_discrepancy',
        'total_variance_value', 'notes',
        'started_by', 'completed_by', 'approved_by',
        'started_at', 'completed_at'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Generate opname number
     */
    public function generateOpnameNumber()
    {
        $yearMonth = date('Ym');
        $count = $this->where('opname_number LIKE', 'SO-' . $yearMonth . '%')->countAllResults();
        return 'SO-' . $yearMonth . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
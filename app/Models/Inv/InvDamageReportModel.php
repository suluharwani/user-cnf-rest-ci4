<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvDamageReportModel extends Model
{
    protected $table = 'inv_damage_reports';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'report_number', 'report_date', 'report_type', 'warehouse_id',
        'status', 'total_loss_value', 'currency_id', 'cause',
        'investigation_notes', 'disposal_method', 'disposal_date',
        'disposal_witness', 'notes', 'reported_by', 'approved_by', 'documents'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $casts = [
        'documents' => 'json'
    ];
    
    /**
     * Generate damage report number
     */
    public function generateReportNumber()
    {
        $yearMonth = date('Ym');
        $count = $this->where('report_number LIKE', 'DMG-' . $yearMonth . '%')->countAllResults();
        return 'DMG-' . $yearMonth . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
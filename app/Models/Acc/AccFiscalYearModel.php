<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccFiscalYearModel extends Model
{
    protected $table = 'acc_fiscal_years';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'year_name', 'start_date', 'end_date', 'is_closed',
        'closed_by', 'closed_at', 'notes'
    ];
    
    /**
     * Get current fiscal year
     */
    public function getCurrentFiscalYear()
    {
        $today = date('Y-m-d');
        return $this->where('start_date <=', $today)
                    ->where('end_date >=', $today)
                    ->first();
    }
    
    /**
     * Close fiscal year
     */
    public function closeFiscalYear($id, $userId)
    {
        // Close all periods first
        $periodModel = new AccPeriodModel();
        $periods = $periodModel->where('fiscal_year_id', $id)->findAll();
        
        foreach ($periods as $period) {
            if (!$period['is_closed']) {
                $periodModel->closePeriod($period['id'], $userId);
            }
        }
        
        return $this->update($id, [
            'is_closed' => 1,
            'closed_by' => $userId,
            'closed_at' => date('Y-m-d H:i:s')
        ]);
    }
}
<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccPeriodModel extends Model
{
    protected $table = 'acc_periods';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'fiscal_year_id', 'period_name', 'month', 'year',
        'start_date', 'end_date', 'is_closed', 'closed_by', 'closed_at'
    ];
    
    /**
     * Get current period
     */
    public function getCurrentPeriod()
    {
        $today = date('Y-m-d');
        return $this->where('start_date <=', $today)
                    ->where('end_date >=', $today)
                    ->first();
    }
    
    /**
     * Get periods by fiscal year
     */
    public function getByFiscalYear($fiscalYearId)
    {
        return $this->where('fiscal_year_id', $fiscalYearId)
                    ->orderBy('month', 'ASC')
                    ->findAll();
    }
    
    /**
     * Close period
     */
    public function closePeriod($id, $userId)
    {
        return $this->update($id, [
            'is_closed' => 1,
            'closed_by' => $userId,
            'closed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Check if period is closed
     */
    public function isPeriodClosed($date)
    {
        $period = $this->where('start_date <=', $date)
                       ->where('end_date >=', $date)
                       ->first();
        
        return $period ? (bool)$period['is_closed'] : false;
    }
    
    /**
     * Get closed periods
     */
    public function getClosedPeriods()
    {
        return $this->where('is_closed', 1)
                    ->orderBy('year', 'DESC')
                    ->orderBy('month', 'DESC')
                    ->findAll();
    }
}
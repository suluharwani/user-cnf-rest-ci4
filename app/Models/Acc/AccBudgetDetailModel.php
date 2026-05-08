<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccBudgetDetailModel extends Model
{
    protected $table = 'acc_budget_details';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'budget_id', 'account_id', 'period_id', 'amount', 'actual_amount', 'notes'
    ];
    
    /**
     * Get details by budget ID
     */
    public function getByBudget($budgetId)
    {
        return $this->select('acc_budget_details.*, 
                             acc_coa.account_code, acc_coa.account_name,
                             acc_periods.period_name')
                    ->join('acc_coa', 'acc_coa.id = acc_budget_details.account_id')
                    ->join('acc_periods', 'acc_periods.id = acc_budget_details.period_id', 'left')
                    ->where('acc_budget_details.budget_id', $budgetId)
                    ->orderBy('acc_coa.account_code')
                    ->orderBy('acc_periods.month')
                    ->findAll();
    }
    
    /**
     * Get budget vs actual comparison
     */
    public function getBudgetVsActual($budgetId)
    {
        $details = $this->getByBudget($budgetId);
        
        $summary = [];
        foreach ($details as $detail) {
            $key = $detail['account_code'] . ' - ' . $detail['account_name'];
            if (!isset($summary[$key])) {
                $summary[$key] = [
                    'account_code' => $detail['account_code'],
                    'account_name' => $detail['account_name'],
                    'budget_amount' => 0,
                    'actual_amount' => 0,
                    'variance' => 0,
                    'variance_pct' => 0
                ];
            }
            $summary[$key]['budget_amount'] += $detail['amount'];
            $summary[$key]['actual_amount'] += $detail['actual_amount'];
        }
        
        foreach ($summary as &$row) {
            $row['variance'] = $row['actual_amount'] - $row['budget_amount'];
            $row['variance_pct'] = $row['budget_amount'] > 0 
                ? round(($row['variance'] / $row['budget_amount']) * 100, 2) 
                : 0;
        }
        
        return array_values($summary);
    }
    
    /**
     * Update actual amount
     */
    public function updateActual($budgetId, $accountId, $periodId, $actualAmount)
    {
        $detail = $this->where([
            'budget_id' => $budgetId,
            'account_id' => $accountId,
            'period_id' => $periodId
        ])->first();
        
        if ($detail) {
            return $this->update($detail['id'], ['actual_amount' => $actualAmount]);
        }
        
        return false;
    }
}
<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccBudgetModel extends Model
{
    protected $table = 'acc_budgets';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'budget_code', 'budget_name', 'fiscal_year_id', 'budget_type',
        'total_amount', 'currency_id', 'status',
        'approved_by', 'approved_at', 'notes', 'created_by'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Generate budget code
     */
    public function generateCode()
    {
        $year = date('Y');
        $count = $this->where('budget_code LIKE', "BUD-$year%")->countAllResults() + 1;
        return sprintf('BUD-%s-%04d', $year, $count);
    }
    
    /**
     * Get budget with details
     */
    public function getWithDetails($id)
    {
        $budget = $this->select('acc_budgets.*, acc_fiscal_years.year_name, inv_currencies.code as currency_code')
                       ->join('acc_fiscal_years', 'acc_fiscal_years.id = acc_budgets.fiscal_year_id')
                       ->join('inv_currencies', 'inv_currencies.id = acc_budgets.currency_id')
                       ->where('acc_budgets.id', $id)
                       ->first();
        
        if ($budget) {
            $detailModel = new AccBudgetDetailModel();
            $budget['details'] = $detailModel->getByBudget($id);
        }
        
        return $budget;
    }
    
    /**
     * Get budget vs actual
     */
    public function getBudgetVsActual($budgetId)
    {
        $detailModel = new AccBudgetDetailModel();
        return $detailModel->getBudgetVsActual($budgetId);
    }
    
    /**
     * Approve budget
     */
    public function approve($id, $userId)
    {
        return $this->update($id, [
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }
}
<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccBankReconciliationItemModel extends Model
{
    protected $table = 'acc_bank_reconciliation_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'reconciliation_id', 'transaction_id', 'transaction_type',
        'transaction_date', 'description', 'amount', 'is_cleared', 'cleared_date', 'notes'
    ];
    
    /**
     * Get items by reconciliation ID
     */
    public function getByReconciliation($reconciliationId)
    {
        return $this->where('reconciliation_id', $reconciliationId)
                    ->orderBy('transaction_date', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get cleared items
     */
    public function getClearedItems($reconciliationId)
    {
        return $this->where('reconciliation_id', $reconciliationId)
                    ->where('is_cleared', 1)
                    ->findAll();
    }
    
    /**
     * Get outstanding items
     */
    public function getOutstandingItems($reconciliationId)
    {
        return $this->where('reconciliation_id', $reconciliationId)
                    ->where('is_cleared', 0)
                    ->findAll();
    }
    
    /**
     * Clear item
     */
    public function clearItem($id, $clearedDate = null)
    {
        return $this->update($id, [
            'is_cleared' => 1,
            'cleared_date' => $clearedDate ?? date('Y-m-d')
        ]);
    }
    
    /**
     * Add outstanding check
     */
    public function addOutstandingCheck($reconciliationId, $checkNumber, $amount, $date, $description = null)
    {
        return $this->insert([
            'reconciliation_id' => $reconciliationId,
            'transaction_type' => 'outstanding_check',
            'transaction_date' => $date,
            'description' => $description ?? "Check #$checkNumber",
            'amount' => $amount,
            'is_cleared' => 0
        ]);
    }
    
    /**
     * Add deposit in transit
     */
    public function addDepositInTransit($reconciliationId, $amount, $date, $description = null)
    {
        return $this->insert([
            'reconciliation_id' => $reconciliationId,
            'transaction_type' => 'deposit_in_transit',
            'transaction_date' => $date,
            'description' => $description ?? 'Deposit in transit',
            'amount' => $amount,
            'is_cleared' => 0
        ]);
    }
}
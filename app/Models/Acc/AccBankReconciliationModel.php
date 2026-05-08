<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccBankReconciliationModel extends Model
{
    protected $table = 'acc_bank_reconciliations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'reconciliation_number', 'cash_bank_id', 'statement_date',
        'statement_balance', 'book_balance', 'status',
        'completed_by', 'completed_at', 'approved_by', 'approved_at', 'notes'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Generate reconciliation number
     */
    public function generateNumber()
    {
        $ym = date('Ym');
        $count = $this->where('reconciliation_number LIKE', "REC-$ym%")->countAllResults() + 1;
        return sprintf('REC-%s-%05d', $ym, $count);
    }
    
    /**
     * Get reconciliation with items
     */
    public function getWithItems($id)
    {
        $recon = $this->select('acc_bank_reconciliations.*, 
                                acc_cash_banks.account_name as bank_name,
                                acc_cash_banks.bank_account_number,
                                acc_cash_banks.current_balance')
                      ->join('acc_cash_banks', 'acc_cash_banks.id = acc_bank_reconciliations.cash_bank_id')
                      ->where('acc_bank_reconciliations.id', $id)
                      ->first();
        
        if ($recon) {
            $itemModel = new AccBankReconciliationItemModel();
            $recon['items'] = $itemModel->getByReconciliation($id);
            $recon['adjusted_balance'] = $this->calculateAdjustedBalance($id);
        }
        
        return $recon;
    }
    
    /**
     * Calculate adjusted balance
     */
    public function calculateAdjustedBalance($id)
    {
        $itemModel = new AccBankReconciliationItemModel();
        $items = $itemModel->where('reconciliation_id', $id)->where('is_cleared', 0)->findAll();
        
        $recon = $this->find($id);
        $adjustedBalance = $recon['book_balance'];
        
        foreach ($items as $item) {
            if ($item['transaction_type'] == 'outstanding_check') {
                $adjustedBalance -= $item['amount'];
            } elseif ($item['transaction_type'] == 'deposit_in_transit') {
                $adjustedBalance += $item['amount'];
            } elseif ($item['transaction_type'] == 'bank_charge') {
                $adjustedBalance -= $item['amount'];
            } elseif ($item['transaction_type'] == 'bank_interest') {
                $adjustedBalance += $item['amount'];
            }
        }
        
        return $adjustedBalance;
    }
    
    /**
     * Complete reconciliation
     */
    public function complete($id, $userId)
    {
        return $this->update($id, [
            'status' => 'completed',
            'completed_by' => $userId,
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }
}
<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccCashTransactionModel extends Model
{
    protected $table = 'acc_cash_transactions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'transaction_number', 'cash_bank_id', 'transaction_date', 'transaction_type',
        'journal_id', 'amount', 'currency_id', 'exchange_rate', 'base_amount',
        'reference_type', 'reference_id', 'reference_number',
        'payee_payer', 'check_number', 'check_date', 'bank_reference',
        'description', 'reconciliation_status', 'reconciled_by', 'reconciled_at',
        'created_by'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null;
    
    /**
     * Generate transaction number
     */
    public function generateNumber()
    {
        $ym = date('Ym');
        $count = $this->where('transaction_number LIKE', "CT-$ym%")->countAllResults() + 1;
        return sprintf('CT-%s-%05d', $ym, $count);
    }
    
    /**
     * Get transactions by cash/bank account
     */
    public function getByCashBank($cashBankId, $limit = 100)
    {
        return $this->select('acc_cash_transactions.*, inv_currencies.code as currency_code')
                    ->join('inv_currencies', 'inv_currencies.id = acc_cash_transactions.currency_id')
                    ->where('cash_bank_id', $cashBankId)
                    ->orderBy('transaction_date', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->findAll($limit);
    }
    
    /**
     * Get unreconciled transactions
     */
    public function getUnreconciled($cashBankId)
    {
        return $this->where('cash_bank_id', $cashBankId)
                    ->where('reconciliation_status', 'unreconciled')
                    ->orderBy('transaction_date', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get transactions by date range
     */
    public function getByDateRange($cashBankId, $startDate, $endDate)
    {
        return $this->where('cash_bank_id', $cashBankId)
                    ->where('transaction_date >=', $startDate)
                    ->where('transaction_date <=', $endDate)
                    ->orderBy('transaction_date', 'ASC')
                    ->findAll();
    }
    
    /**
     * Mark as reconciled
     */
    public function reconcile($id, $userId)
    {
        return $this->update($id, [
            'reconciliation_status' => 'reconciled',
            'reconciled_by' => $userId,
            'reconciled_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get bank statement summary
     */
    public function getBankSummary($cashBankId, $startDate, $endDate)
    {
        return $this->select('transaction_type, COUNT(*) as total_count, SUM(amount) as total_amount')
                    ->where('cash_bank_id', $cashBankId)
                    ->where('transaction_date >=', $startDate)
                    ->where('transaction_date <=', $endDate)
                    ->where('reconciliation_status !=', 'void')
                    ->groupBy('transaction_type')
                    ->findAll();
    }
    
    /**
     * Get running balance
     */
    public function getRunningBalance($cashBankId, $upToDate = null)
    {
        if (!$upToDate) $upToDate = date('Y-m-d');
        
        $result = $this->selectSum('amount')
                       ->where('cash_bank_id', $cashBankId)
                       ->where('transaction_date <=', $upToDate)
                       ->where('reconciliation_status !=', 'void')
                       ->first();
        
        return $result['amount'] ?? 0;
    }
}
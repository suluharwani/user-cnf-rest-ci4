<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccJournalLineModel extends Model
{
    protected $table = 'acc_journal_lines';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'journal_id', 'line_number', 'account_id', 'debit', 'credit',
        'currency_id', 'exchange_rate', 'base_debit', 'base_credit',
        'department_id', 'project_id', 'cost_center',
        'reference_type', 'reference_id', 'description'
    ];
    
    public function getByJournal($journalId)
    {
        return $this->select('acc_journal_lines.*, acc_coa.account_code, acc_coa.account_name')
                    ->join('acc_coa', 'acc_coa.id = acc_journal_lines.account_id')
                    ->where('journal_id', $journalId)
                    ->orderBy('line_number')
                    ->findAll();
    }
    
    public function getAccountBalance($accountId, $upToDate)
    {
        return $this->selectSum('debit')->selectSum('credit')
                    ->join('acc_journal_entries', 'acc_journal_entries.id = acc_journal_lines.journal_id')
                    ->where('account_id', $accountId)
                    ->where('acc_journal_entries.journal_date <=', $upToDate)
                    ->where('acc_journal_entries.status', 'posted')
                    ->first();
    }
    
    public function getTrialBalance($upToDate)
    {
        return $this->select('acc_coa.account_code, acc_coa.account_name, acc_coa.normal_balance,
                             SUM(acc_journal_lines.debit) as total_debit,
                             SUM(acc_journal_lines.credit) as total_credit')
                    ->join('acc_journal_entries', 'acc_journal_entries.id = acc_journal_lines.journal_id')
                    ->join('acc_coa', 'acc_coa.id = acc_journal_lines.account_id')
                    ->where('acc_journal_entries.journal_date <=', $upToDate)
                    ->where('acc_journal_entries.status', 'posted')
                    ->where('acc_coa.is_header', 0)
                    ->groupBy('acc_journal_lines.account_id')
                    ->orderBy('acc_coa.account_code')
                    ->findAll();
    }
}
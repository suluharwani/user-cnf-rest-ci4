<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccJournalModel extends Model
{
    protected $table = 'acc_journal_entries';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'journal_number', 'journal_date', 'period_id', 'journal_type',
        'reference_type', 'reference_id', 'reference_number', 'description',
        'total_debit', 'total_credit', 'currency_id', 'exchange_rate',
        'status', 'posted_by', 'posted_at', 'reversed_from', 'notes', 'created_by'
    ];
    
    protected $useTimestamps = true;
    
    public function generateNumber($type, $date)
    {
        $prefix = [
            'general' => 'JV', 'cash_receipt' => 'CR', 'cash_payment' => 'CP',
            'bank_receipt' => 'BR', 'bank_payment' => 'BP', 'purchase' => 'PJ',
            'sales' => 'SJ', 'inventory' => 'IJ', 'adjustment' => 'AJ'
        ];
        
        $p = $prefix[$type] ?? 'JV';
        $ym = date('Ym', strtotime($date));
        $count = $this->where('journal_number LIKE', "$p-$ym%")->countAllResults() + 1;
        
        return sprintf('%s-%s-%05d', $p, $ym, $count);
    }
    
    public function getWithLines($id)
    {
        $journal = $this->find($id);
        if (!$journal) return null;
        
        $lineModel = new AccJournalLineModel();
        $journal['lines'] = $lineModel->getByJournal($id);
        
        return $journal;
    }
    
    public function post($id, $userId)
    {
        // Validate debit = credit
        $lineModel = new AccJournalLineModel();
        $lines = $lineModel->where('journal_id', $id)->findAll();
        
        $totalDebit = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));
        
        if (abs($totalDebit - $totalCredit) > 0.01) {
            return ['success' => false, 'message' => 'Debit and credit must be equal'];
        }
        
        $this->update($id, [
            'status' => 'posted',
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'posted_by' => $userId,
            'posted_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['success' => true, 'message' => 'Journal posted successfully'];
    }
    
    public function reverse($id, $userId, $date)
    {
        $original = $this->getWithLines($id);
        if (!$original || $original['status'] !== 'posted') {
            return false;
        }
        
        $db = \Config\Database::connect();
        $db->transStart();
        
        $newJournalId = $this->insert([
            'journal_number' => $this->generateNumber('general', $date),
            'journal_date' => $date,
            'period_id' => $original['period_id'],
            'journal_type' => 'general',
            'description' => 'REVERSAL of ' . $original['journal_number'] . ': ' . $original['description'],
            'status' => 'draft',
            'reversed_from' => $id,
            'created_by' => $userId
        ]);
        
        $lineModel = new AccJournalLineModel();
        $lineNo = 1;
        foreach ($original['lines'] as $line) {
            $lineModel->insert([
                'journal_id' => $newJournalId,
                'line_number' => $lineNo++,
                'account_id' => $line['account_id'],
                'debit' => $line['credit'],
                'credit' => $line['debit'],
                'description' => 'REVERSAL: ' . $line['description']
            ]);
        }
        
        $this->update($id, ['status' => 'reversed']);
        $db->transComplete();
        
        return $newJournalId;
    }
}
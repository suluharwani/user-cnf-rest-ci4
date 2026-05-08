<?php

namespace App\Controllers\Api\Acc;

use App\Controllers\BaseController;
use App\Models\Acc\AccCashBankModel;
use App\Models\Acc\AccCashTransactionModel;
use App\Models\Acc\AccJournalModel;
use App\Models\Acc\AccJournalLineModel;
use CodeIgniter\API\ResponseTrait;

class AccCashBankController extends BaseController
{
    use ResponseTrait;
    protected $model;
    
    public function __construct() { $this->model = new AccCashBankModel(); }
    
    public function index() {
        return $this->respond(['data' => $this->model->getWithBalance()]);
    }
    
    public function create() {
        $id = $this->model->insert($this->request->getJSON(true));
        return $this->respondCreated(['message' => 'Created', 'id' => $id]);
    }
    
    public function receivePayment() {
        $data = $this->request->getJSON(true);
        
        $journalModel = new AccJournalModel();
        $journalId = $journalModel->insert([
            'journal_number' => $journalModel->generateNumber('bank_receipt', date('Y-m-d')),
            'journal_date' => date('Y-m-d'),
            'period_id' => $data['period_id'],
            'journal_type' => 'bank_receipt',
            'description' => $data['description'] ?? 'Payment received',
            'created_by' => $this->request->user_id ?? null
        ]);
        
        $lineModel = new AccJournalLineModel();
        $cashBank = $this->model->find($data['cash_bank_id']);
        
        $lineModel->insert([
            'journal_id' => $journalId, 'line_number' => 1,
            'account_id' => $cashBank['coa_id'], 'debit' => $data['amount'], 'credit' => 0,
            'description' => 'Payment received'
        ]);
        $lineModel->insert([
            'journal_id' => $journalId, 'line_number' => 2,
            'account_id' => $data['credit_account_id'], 'debit' => 0, 'credit' => $data['amount'],
            'description' => $data['description'] ?? 'Payment'
        ]);
        
        $journalModel->post($journalId, $this->request->user_id ?? null);
        $this->model->updateBalance($data['cash_bank_id'], $data['amount']);
        
        $transModel = new AccCashTransactionModel();
        $transModel->insert([
            'transaction_number' => $transModel->generateNumber(),
            'cash_bank_id' => $data['cash_bank_id'],
            'transaction_date' => date('Y-m-d'),
            'transaction_type' => 'receipt',
            'journal_id' => $journalId,
            'amount' => $data['amount'],
            'currency_id' => $data['currency_id'] ?? 1,
            'description' => $data['description'] ?? '',
            'created_by' => $this->request->user_id ?? null
        ]);
        
        return $this->respondCreated(['message' => 'Payment received', 'journal_id' => $journalId]);
    }
    
    public function makePayment() {
        $data = $this->request->getJSON(true);
        
        $journalModel = new AccJournalModel();
        $journalId = $journalModel->insert([
            'journal_number' => $journalModel->generateNumber('bank_payment', date('Y-m-d')),
            'journal_date' => date('Y-m-d'),
            'period_id' => $data['period_id'],
            'journal_type' => 'bank_payment',
            'description' => $data['description'] ?? 'Payment made',
            'created_by' => $this->request->user_id ?? null
        ]);
        
        $lineModel = new AccJournalLineModel();
        $cashBank = $this->model->find($data['cash_bank_id']);
        
        $lineModel->insert([
            'journal_id' => $journalId, 'line_number' => 1,
            'account_id' => $data['debit_account_id'], 'debit' => $data['amount'], 'credit' => 0,
            'description' => $data['description'] ?? 'Payment'
        ]);
        $lineModel->insert([
            'journal_id' => $journalId, 'line_number' => 2,
            'account_id' => $cashBank['coa_id'], 'debit' => 0, 'credit' => $data['amount'],
            'description' => 'Bank payment'
        ]);
        
        $journalModel->post($journalId, $this->request->user_id ?? null);
        $this->model->updateBalance($data['cash_bank_id'], $data['amount'], true);
        
        return $this->respondCreated(['message' => 'Payment made', 'journal_id' => $journalId]);
    }
}
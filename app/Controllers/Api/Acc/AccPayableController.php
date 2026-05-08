<?php

namespace App\Controllers\Api\Acc;

use App\Controllers\BaseController;
use App\Models\Acc\AccPayableModel;
use App\Models\Acc\AccJournalModel;
use App\Models\Acc\AccJournalLineModel;
use App\Models\Acc\AccCashBankModel;
use CodeIgniter\API\ResponseTrait;

class AccPayableController extends BaseController
{
    use ResponseTrait;
    protected $model;
    
    public function __construct() { $this->model = new AccPayableModel(); }
    
    public function index() {
        return $this->respond(['data' => $this->model->orderBy('bill_date', 'DESC')->findAll()]);
    }
    
    public function create() {
        $data = $this->request->getJSON(true);
        $data['bill_number'] = $this->model->generateBillNumber();
        $data['status'] = 'open';
        $data['created_by'] = $this->request->user_id ?? null;
        
        $id = $this->model->insert($data);
        return $this->respondCreated(['message' => 'Bill created', 'id' => $id, 'bill_number' => $data['bill_number']]);
    }
    
    public function show($id = null) {
        $bill = $this->model->find($id);
        if (!$bill) return $this->failNotFound();
        return $this->respond($bill);
    }
    
    public function addPayment($id = null) {
        $data = $this->request->getJSON(true);
        $this->model->addPayment($id, $data['amount']);
        
        if (isset($data['cash_bank_id']) && isset($data['period_id'])) {
            $journalModel = new AccJournalModel();
            $journalId = $journalModel->insert([
                'journal_number' => $journalModel->generateNumber('cash_payment', date('Y-m-d')),
                'journal_date' => date('Y-m-d'),
                'period_id' => $data['period_id'],
                'journal_type' => 'cash_payment',
                'description' => 'AP Payment',
                'created_by' => $this->request->user_id ?? null
            ]);
            
            $lineModel = new AccJournalLineModel();
            $cashBankModel = new AccCashBankModel();
            $cashBank = $cashBankModel->find($data['cash_bank_id']);
            $bill = $this->model->find($id);
            
            $lineModel->insert([
                'journal_id' => $journalId, 'line_number' => 1,
                'account_id' => $bill['coa_id'], 'debit' => $data['amount'], 'credit' => 0,
                'description' => 'Payment for ' . $bill['bill_number']
            ]);
            $lineModel->insert([
                'journal_id' => $journalId, 'line_number' => 2,
                'account_id' => $cashBank['coa_id'], 'debit' => 0, 'credit' => $data['amount'],
                'description' => 'Bank payment'
            ]);
            
            $journalModel->post($journalId, $this->request->user_id ?? null);
            $cashBankModel->updateBalance($data['cash_bank_id'], $data['amount'], true);
        }
        
        return $this->respond(['message' => 'Payment recorded']);
    }
}
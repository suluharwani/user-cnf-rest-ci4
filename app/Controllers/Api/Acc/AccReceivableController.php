<?php

namespace App\Controllers\Api\Acc;

use App\Controllers\BaseController;
use App\Models\Acc\AccReceivableModel;
use App\Models\Acc\AccJournalModel;
use App\Models\Acc\AccJournalLineModel;
use App\Models\Acc\AccCashBankModel;
use CodeIgniter\API\ResponseTrait;

class AccReceivableController extends BaseController
{
    use ResponseTrait;
    protected $model;
    
    public function __construct() { $this->model = new AccReceivableModel(); }
    
    public function index() {
        return $this->respond(['data' => $this->model->orderBy('invoice_date', 'DESC')->findAll()]);
    }
    
    public function create() {
        $data = $this->request->getJSON(true);
        $data['invoice_number'] = $this->model->generateInvoiceNumber();
        $data['status'] = 'open';
        $data['created_by'] = $this->request->user_id ?? null;
        
        $id = $this->model->insert($data);
        return $this->respondCreated(['message' => 'Invoice created', 'id' => $id, 'invoice_number' => $data['invoice_number']]);
    }
    
    public function show($id = null) {
        $invoice = $this->model->find($id);
        if (!$invoice) return $this->failNotFound();
        return $this->respond($invoice);
    }
    
    public function addPayment($id = null) {
        $data = $this->request->getJSON(true);
        $this->model->addPayment($id, $data['amount']);
        
        if (isset($data['cash_bank_id']) && isset($data['period_id'])) {
            $journalModel = new AccJournalModel();
            $journalId = $journalModel->insert([
                'journal_number' => $journalModel->generateNumber('cash_receipt', date('Y-m-d')),
                'journal_date' => date('Y-m-d'),
                'period_id' => $data['period_id'],
                'journal_type' => 'cash_receipt',
                'description' => 'AR Payment received',
                'created_by' => $this->request->user_id ?? null
            ]);
            
            $lineModel = new AccJournalLineModel();
            $cashBankModel = new AccCashBankModel();
            $cashBank = $cashBankModel->find($data['cash_bank_id']);
            $invoice = $this->model->find($id);
            
            $lineModel->insert([
                'journal_id' => $journalId, 'line_number' => 1,
                'account_id' => $cashBank['coa_id'], 'debit' => $data['amount'], 'credit' => 0,
                'description' => 'Payment for ' . $invoice['invoice_number']
            ]);
            $lineModel->insert([
                'journal_id' => $journalId, 'line_number' => 2,
                'account_id' => $invoice['coa_id'], 'debit' => 0, 'credit' => $data['amount'],
                'description' => 'AR Payment'
            ]);
            
            $journalModel->post($journalId, $this->request->user_id ?? null);
            $cashBankModel->updateBalance($data['cash_bank_id'], $data['amount']);
        }
        
        return $this->respond(['message' => 'Payment recorded']);
    }
}
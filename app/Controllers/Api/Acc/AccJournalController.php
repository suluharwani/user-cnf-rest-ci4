<?php

namespace App\Controllers\Api\Acc;

use App\Controllers\BaseController;
use App\Models\Acc\AccJournalModel;
use App\Models\Acc\AccJournalLineModel;
use CodeIgniter\API\ResponseTrait;

class AccJournalController extends BaseController
{
    use ResponseTrait;
    protected $model;
    protected $lineModel;
    
    public function __construct() { 
        $this->model = new AccJournalModel();
        $this->lineModel = new AccJournalLineModel();
    }
    
    public function index() {
        try {
            $page = $this->request->getGet('page') ?? 1;
            $limit = $this->request->getGet('limit') ?? 20;
            $status = $this->request->getGet('status');
            
            $builder = $this->model;
            if ($status) $builder->where('status', $status);
            
            $total = $builder->countAllResults(false);
            $journals = $builder->orderBy('journal_date', 'DESC')->findAll($limit, ($page - 1) * $limit);
            
            return $this->respond(['data' => $journals, 'pagination' => ['page' => (int)$page, 'limit' => (int)$limit, 'total' => $total]]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    public function create() {
        try {
            // ✅ Cek apakah body kosong
            $rawBody = $this->request->getBody();
            if (empty($rawBody)) {
                return $this->fail('Request body is empty', 400);
            }
            
            // ✅ Parse JSON dengan error handling
            $data = json_decode($rawBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->fail('Invalid JSON format: ' . json_last_error_msg(), 400);
            }
            
            // ✅ Validasi data wajib
            if (!isset($data['journal_date']) || !isset($data['period_id'])) {
                return $this->fail('journal_date and period_id are required', 400);
            }
            
            $lines = $data['lines'] ?? [];
            unset($data['lines']);
            
            $data['journal_number'] = $this->model->generateNumber($data['journal_type'] ?? 'general', $data['journal_date']);
            $data['status'] = 'draft';
            $data['created_by'] = $this->request->user_id ?? null;
            
            $db = \Config\Database::connect();
            $db->transStart();
            
            $journalId = $this->model->insert($data);
            
            if (!$journalId) {
                return $this->fail('Failed to create journal', 500);
            }
            
            $lineNo = 1;
            foreach ($lines as $line) {
                $line['journal_id'] = $journalId;
                $line['line_number'] = $lineNo++;
                $this->lineModel->insert($line);
            }
            
            $db->transComplete();
            
            return $this->respondCreated([
                'message' => 'Journal created',
                'id' => $journalId,
                'journal_number' => $data['journal_number']
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Journal Create Error: ' . $e->getMessage());
            return $this->failServerError('Error: ' . $e->getMessage());
        }
    }
    
    public function show($id = null) {
        $journal = $this->model->getWithLines($id);
        if (!$journal) return $this->failNotFound('Journal not found');
        return $this->respond($journal);
    }
    
    public function post($id = null) {
        $journal = $this->model->find($id);
        if (!$journal) return $this->failNotFound('Journal not found');
        
        if ($journal['status'] !== 'draft') {
            return $this->fail('Only draft journals can be posted', 400);
        }
        
        $result = $this->model->post($id, $this->request->user_id ?? null);
        if ($result['success']) return $this->respond($result);
        return $this->fail($result['message'], 400);
    }
    
    public function reverse($id = null) {
        try {
            $rawBody = $this->request->getBody();
            $data = [];
            
            if (!empty($rawBody)) {
                $data = json_decode($rawBody, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $data = [];
                }
            }
            
            $date = $data['date'] ?? date('Y-m-d');
            $newId = $this->model->reverse($id, $this->request->user_id ?? null, $date);
            
            if (!$newId) {
                return $this->fail('Cannot reverse this journal. It may not be posted yet.', 400);
            }
            
            return $this->respond(['message' => 'Journal reversed', 'new_journal_id' => $newId]);
            
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    public function trialBalance() {
        try {
            $date = $this->request->getGet('date') ?? date('Y-m-d');
            $data = $this->lineModel->getTrialBalance($date);
            
            $totalDebit = array_sum(array_column($data, 'total_debit'));
            $totalCredit = array_sum(array_column($data, 'total_credit'));
            
            return $this->respond([
                'report_date' => $date,
                'accounts' => $data,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_balanced' => abs($totalDebit - $totalCredit) < 0.01
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
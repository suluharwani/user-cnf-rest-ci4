<?php

namespace App\Controllers\Api\Acc;

use App\Controllers\BaseController;
use App\Models\Acc\AccBankReconciliationModel;
use App\Models\Acc\AccBankReconciliationItemModel;
use CodeIgniter\API\ResponseTrait;

class AccReconciliationController extends BaseController
{
    use ResponseTrait;
    protected $model;
    
    public function __construct() { $this->model = new AccBankReconciliationModel(); }
    
    public function index() {
        return $this->respond(['data' => $this->model->orderBy('statement_date', 'DESC')->findAll()]);
    }
    
    public function create() {
        $data = $this->request->getJSON(true);
        $items = $data['items'] ?? [];
        unset($data['items']);
        
        $data['reconciliation_number'] = $this->model->generateNumber();
        $data['status'] = 'draft';
        
        $db = \Config\Database::connect();
        $db->transStart();
        
        $id = $this->model->insert($data);
        
        if (!empty($items)) {
            $itemModel = new AccBankReconciliationItemModel();
            foreach ($items as $item) {
                $item['reconciliation_id'] = $id;
                $itemModel->insert($item);
            }
        }
        
        $db->transComplete();
        
        return $this->respondCreated(['message' => 'Reconciliation created', 'id' => $id, 'reconciliation_number' => $data['reconciliation_number']]);
    }
    
    public function show($id = null) {
        $recon = $this->model->getWithItems($id);
        if (!$recon) return $this->failNotFound();
        return $this->respond($recon);
    }
    
    public function complete($id = null) {
        $this->model->complete($id, $this->request->user_id ?? null);
        return $this->respond(['message' => 'Reconciliation completed']);
    }
}
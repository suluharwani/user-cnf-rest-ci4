<?php

namespace App\Controllers\Api\Acc;

use App\Controllers\BaseController;
use App\Models\Acc\AccBudgetModel;
use CodeIgniter\API\ResponseTrait;

class AccBudgetController extends BaseController
{
    use ResponseTrait;
    protected $model;
    
    public function __construct() { $this->model = new AccBudgetModel(); }
    
    public function index() {
        return $this->respond(['data' => $this->model->findAll()]);
    }
    
    public function create() {
        $data = $this->request->getJSON(true);
        $data['budget_code'] = $this->model->generateCode();
        $data['status'] = 'draft';
        $data['created_by'] = $this->request->user_id ?? null;
        
        $id = $this->model->insert($data);
        return $this->respondCreated(['message' => 'Budget created', 'id' => $id, 'budget_code' => $data['budget_code']]);
    }
    
    public function show($id = null) {
        $budget = $this->model->getWithDetails($id);
        if (!$budget) return $this->failNotFound();
        return $this->respond($budget);
    }
    
    public function vsActual($id = null) {
        $data = $this->model->getBudgetVsActual($id);
        return $this->respond(['data' => $data]);
    }
}
<?php

namespace App\Controllers\Api\Acc;

use App\Controllers\BaseController;
use App\Models\Acc\AccCoaModel;
use CodeIgniter\API\ResponseTrait;

class AccCoaController extends BaseController
{
    use ResponseTrait;
    protected $model;
    
    public function __construct() { $this->model = new AccCoaModel(); }
    
    public function index() {
        return $this->respond(['data' => $this->model->getLeafAccounts()]);
    }
    
    public function tree() {
        return $this->respond(['data' => $this->model->getTree()]);
    }
    
    public function create() {
        $data = $this->request->getJSON(true);
        if (!isset($data['account_code']) && isset($data['parent_id'])) {
            $data['account_code'] = $this->model->generateCode($data['parent_id'], $data['account_group']);
        }
        $id = $this->model->insert($data);
        return $this->respondCreated(['message' => 'Account created', 'id' => $id]);
    }
    
    public function show($id = null) {
        $account = $this->model->find($id);
        if (!$account) return $this->failNotFound();
        return $this->respond($account);
    }
}
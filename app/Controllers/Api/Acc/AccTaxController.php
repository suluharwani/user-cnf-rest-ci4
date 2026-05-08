<?php

namespace App\Controllers\Api\Acc;

use App\Controllers\BaseController;
use App\Models\Acc\AccTaxModel;
use CodeIgniter\API\ResponseTrait;

class AccTaxController extends BaseController
{
    use ResponseTrait;
    protected $model;
    
    public function __construct() { $this->model = new AccTaxModel(); }
    
    public function index() {
        return $this->respond(['data' => $this->model->findAll()]);
    }
    
    public function create() {
        $id = $this->model->insert($this->request->getJSON(true));
        return $this->respondCreated(['message' => 'Tax created', 'id' => $id]);
    }
    
    public function show($id = null) {
        $tax = $this->model->find($id);
        if (!$tax) return $this->failNotFound();
        return $this->respond($tax);
    }
}
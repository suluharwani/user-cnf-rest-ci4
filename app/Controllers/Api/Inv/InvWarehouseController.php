<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvWarehouseModel;
use CodeIgniter\API\ResponseTrait;

class InvWarehouseController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new InvWarehouseModel();
    }
    
    public function index()
    {
        return $this->respond(['data' => $this->model->getWithManager()]);
    }
    
    public function create()
    {
        $rules = ['warehouse_code' => 'required|is_unique[inv_warehouses.warehouse_code]', 'warehouse_name' => 'required', 'warehouse_type' => 'required'];
        if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());
        
        $id = $this->model->insert($this->request->getJSON(true));
        return $this->respondCreated(['message' => 'Warehouse created', 'id' => $id]);
    }
}
<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvStockModel;
use CodeIgniter\API\ResponseTrait;

class InvStockController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new InvStockModel();
    }
    
    public function index()
    {
        return $this->respond(['data' => $this->model->findAll()]);
    }
    
    public function byWarehouse($warehouseId = null)
    {
        if (!$warehouseId) return $this->fail('Warehouse ID required');
        return $this->respond(['data' => $this->model->getStockByWarehouse($warehouseId)]);
    }
    
    public function byMaterial($materialId = null)
    {
        if (!$materialId) return $this->fail('Material ID required');
        return $this->respond(['data' => $this->model->getStockByMaterial($materialId)]);
    }
    
    public function stockValue()
    {
        return $this->respond(['value' => $this->model->getStockValue()]);
    }
}
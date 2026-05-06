<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvStockMovementModel;
use CodeIgniter\API\ResponseTrait;

class InvStockMovementController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new InvStockMovementModel();
    }
    
    public function index()
    {
        $movements = $this->model->getRecentMovements(100);
        return $this->respond(['data' => $movements]);
    }
    
    public function byMaterial($materialId = null)
    {
        if (!$materialId) return $this->fail('Material ID required');
        return $this->respond(['data' => $this->model->getByMaterial($materialId)]);
    }
}
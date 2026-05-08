<?php

namespace App\Controllers\Api\Acc;

use App\Controllers\BaseController;
use App\Models\Acc\AccFixedAssetModel;
use CodeIgniter\API\ResponseTrait;

class AccFixedAssetController extends BaseController
{
    use ResponseTrait;
    protected $model;
    
    public function __construct() { $this->model = new AccFixedAssetModel(); }
    
    public function index() {
        return $this->respond(['data' => $this->model->findAll()]);
    }
    
    public function create() {
        $data = $this->request->getJSON(true);
        $data['asset_code'] = 'FA-' . date('Ym') . '-' . str_pad($this->model->countAll() + 1, 4, '0', STR_PAD_LEFT);
        $data['status'] = 'active';
        
        $id = $this->model->insert($data);
        return $this->respondCreated(['message' => 'Asset created', 'id' => $id]);
    }
    
    public function show($id = null) {
        $asset = $this->model->find($id);
        if (!$asset) return $this->failNotFound();
        return $this->respond($asset);
    }
    
    public function depreciation($assetId = null) {
        $periodId = $this->request->getGet('period_id');
        $result = $this->model->runDepreciation($assetId, $periodId, $this->request->user_id ?? null);
        return $this->respond(['message' => 'Depreciation recorded', 'data' => $result]);
    }
    
    public function runAllDepreciation() {
        $data = $this->request->getJSON(true);
        $periodId = $data['period_id'] ?? null;
        $assets = $this->model->where('status', 'active')->findAll();
        $results = [];
        
        foreach ($assets as $asset) {
            $results[] = $this->model->runDepreciation($asset['id'], $periodId, $this->request->user_id ?? null);
        }
        
        return $this->respond(['message' => 'Depreciation completed', 'results' => $results]);
    }
}
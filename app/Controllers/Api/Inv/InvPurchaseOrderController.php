<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvPurchaseOrderModel;
use App\Models\Inv\InvPoItemModel;
use CodeIgniter\API\ResponseTrait;

class InvPurchaseOrderController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    protected $itemModel;
    
    public function __construct()
    {
        $this->model = new InvPurchaseOrderModel();
        $this->itemModel = new InvPoItemModel();
    }
    
    public function index()
    {
        $pos = $this->model->getWithDetails();
        return $this->respond(['data' => $pos]);
    }
    
    public function create()
    {
        $rules = ['supplier_id' => 'required', 'po_date' => 'required', 'currency_id' => 'required'];
        if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());
        
        $data = $this->request->getJSON(true);
        $items = $data['items'] ?? [];
        unset($data['items']);
        
        $data['po_number'] = $this->model->generatePONumber();
        $data['created_by'] = $this->request->user_id ?? null;
        $data['status'] = 'draft';
        
        $db = \Config\Database::connect();
        $db->transStart();
        
        $poId = $this->model->insert($data);
        
        foreach ($items as $item) {
            $item['po_id'] = $poId;
            $item['total_price'] = $item['quantity'] * $item['unit_price'];
            $this->itemModel->insert($item);
        }
        
        $db->transComplete();
        
        return $this->respondCreated(['message' => 'PO created', 'id' => $poId, 'po_number' => $data['po_number']]);
    }
    
    public function show($id = null)
    {
        $po = $this->model->getWithDetails($id);
        if (!$po) return $this->failNotFound('PO not found');
        $po['items'] = $this->model->getItems($id);
        return $this->respond($po);
    }
    
    public function approve($id = null)
    {
        $po = $this->model->find($id);
        if (!$po) return $this->failNotFound('PO not found');
        $this->model->update($id, ['status' => 'approved', 'approved_by' => $this->request->user_id ?? null]);
        return $this->respond(['message' => 'PO approved']);
    }
}
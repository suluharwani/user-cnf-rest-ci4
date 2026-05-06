<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvGoodsReceiptModel;
use App\Models\Inv\InvGrItemModel;
use App\Models\Inv\InvStockModel;
use App\Models\Inv\InvStockMovementModel;
use App\Models\Inv\InvPoItemModel;
use CodeIgniter\API\ResponseTrait;

class InvGoodsReceiptController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    protected $itemModel;
    protected $stockModel;
    protected $movementModel;
    protected $poItemModel;
    
    public function __construct()
    {
        $this->model = new InvGoodsReceiptModel();
        $this->itemModel = new InvGrItemModel();
        $this->stockModel = new InvStockModel();
        $this->movementModel = new InvStockMovementModel();
        $this->poItemModel = new InvPoItemModel();
    }
    
    public function index()
    {
        return $this->respond(['data' => $this->model->getWithDetails()]);
    }
    
    public function create()
    {
        $rules = ['receipt_date' => 'required', 'supplier_id' => 'required', 'warehouse_id' => 'required'];
        if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());
        
        $data = $this->request->getJSON(true);
        $items = $data['items'] ?? [];
        unset($data['items']);
        
        $data['gr_number'] = $this->model->generateGRNumber();
        $data['status'] = 'pending_inspection';
        $data['received_by'] = $this->request->user_id ?? null;
        
        $db = \Config\Database::connect();
        $db->transStart();
        
        $grId = $this->model->insert($data);
        
        foreach ($items as $item) {
            $item['gr_id'] = $grId;
            $this->itemModel->insert($item);
            
            // Update stock
            $this->stockModel->updateStock($item['material_id'], $data['warehouse_id'], $item['rack_id'] ?? null, $item['batch_number'] ?? null, $item['quantity_accepted'] ?? $item['quantity_received']);
            
            // Update PO received qty
            if (!empty($item['po_item_id'])) {
                $this->poItemModel->updateReceivedQty($item['po_item_id'], $item['quantity_received']);
            }
            
            // Record movement
            $this->movementModel->insert([
                'movement_type' => 'receipt',
                'material_id' => $item['material_id'],
                'to_warehouse_id' => $data['warehouse_id'],
                'to_rack_id' => $item['rack_id'] ?? null,
                'batch_number' => $item['batch_number'] ?? null,
                'quantity' => $item['quantity_accepted'] ?? $item['quantity_received'],
                'reference_type' => 'gr',
                'reference_id' => $grId,
                'reference_number' => $data['gr_number'],
                'notes' => 'Goods receipt'
            ]);
        }
        
        $db->transComplete();
        
        return $this->respondCreated(['message' => 'Goods receipt created', 'id' => $grId, 'gr_number' => $data['gr_number']]);
    }
    
    public function show($id = null)
    {
        $gr = $this->model->getWithDetails($id);
        if (!$gr) return $this->failNotFound('GR not found');
        $gr['items'] = $this->itemModel->getByGR($id);
        return $this->respond($gr);
    }
}
<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvDamageReportModel;
use App\Models\Inv\InvStockModel;
use App\Models\Inv\InvStockMovementModel;
use CodeIgniter\API\ResponseTrait;

class InvDamageReportController extends BaseController
{
    use ResponseTrait;
    
    protected $damageModel;
    protected $stockModel;
    protected $movementModel;
    
    public function __construct()
    {
        $this->damageModel = new InvDamageReportModel();
        $this->stockModel = new InvStockModel();
        $this->movementModel = new InvStockMovementModel();
    }
    
    /**
     * List damage reports
     */
    public function index()
    {
        $page = $this->request->getGet('page') ?? 1;
        $limit = $this->request->getGet('limit') ?? 20;
        $status = $this->request->getGet('status');
        
        $builder = $this->damageModel->select('inv_damage_reports.*, inv_warehouses.warehouse_name')
                                     ->join('inv_warehouses', 'inv_warehouses.id = inv_damage_reports.warehouse_id');
        
        if ($status) {
            $builder->where('inv_damage_reports.status', $status);
        }
        
        $total = $builder->countAllResults(false);
        $reports = $builder->orderBy('inv_damage_reports.created_at', 'DESC')
                           ->findAll($limit, ($page - 1) * $limit);
        
        return $this->respond([
            'data' => $reports,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => $total
            ]
        ]);
    }
    
    /**
     * Create damage report
     */
    public function create()
    {
        $rules = [
            'report_date' => 'required|valid_date',
            'report_type' => 'required|in_list[damage,loss,expired,defective,contamination,other]',
            'warehouse_id' => 'required|integer',
            'currency_id' => 'required|integer'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        $data['report_number'] = $this->damageModel->generateReportNumber();
        $data['status'] = 'draft';
        $data['reported_by'] = $this->request->user_id ?? null;
        
        $id = $this->damageModel->insert($data);
        
        return $this->respondCreated([
            'message' => 'Damage report created',
            'id' => $id,
            'report_number' => $data['report_number']
        ]);
    }
    
    /**
     * Add damage item
     */
    public function addItem($reportId = null)
    {
        $report = $this->damageModel->find($reportId);
        
        if (!$report) {
            return $this->failNotFound('Report not found');
        }
        
        $rules = [
            'material_id' => 'required|integer',
            'quantity' => 'required|numeric',
            'condition_description' => 'required'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        $data['damage_report_id'] = $reportId;
        $data['total_cost'] = ($data['unit_cost'] ?? 0) * $data['quantity'];
        
        $itemModel = new \App\Models\Inv\InvDamageItemModel();
        $itemModel->insert($data);
        
        return $this->respond([
            'message' => 'Damage item added'
        ]);
    }
    
    /**
     * Approve and process disposal
     */
    public function approveAndDispose($id = null)
    {
        $report = $this->damageModel->find($id);
        
        if (!$report) {
            return $this->failNotFound('Report not found');
        }
        
        $rules = [
            'disposal_method' => 'required|in_list[burned,buried,recycled,returned_supplier,sold_scrap,other]',
            'disposal_date' => 'required|valid_date',
            'disposal_witness' => 'required'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $approvalData = $this->request->getJSON(true);
        
        $itemModel = new \App\Models\Inv\InvDamageItemModel();
        $items = $itemModel->where('damage_report_id', $id)->findAll();
        
        if (count($items) === 0) {
            return $this->fail('No items in this report', 400);
        }
        
        $db = \Config\Database::connect();
        $db->transStart();
        
        $totalLoss = 0;
        
        foreach ($items as $item) {
            $totalLoss += $item['total_cost'];
            
            // Reduce stock
            $this->stockModel->updateStock(
                $item['material_id'],
                $report['warehouse_id'],
                null,
                $item['batch_number'],
                -$item['quantity'],
                -($item['volume_m3'] ?? 0)
            );
            
            // Record movement
            $this->movementModel->insert([
                'movement_type' => $report['report_type'],
                'material_id' => $item['material_id'],
                'from_warehouse_id' => $report['warehouse_id'],
                'batch_number' => $item['batch_number'],
                'quantity' => -$item['quantity'],
                'volume_m3' => -($item['volume_m3'] ?? 0),
                'unit_cost' => $item['unit_cost'],
                'total_cost' => -$item['total_cost'],
                'reference_type' => 'disposal',
                'reference_id' => $id,
                'reference_number' => $report['report_number'],
                'notes' => "Disposal: {$report['report_type']} - {$item['root_cause']}"
            ]);
        }
        
        $this->damageModel->update($id, [
            'status' => 'disposed',
            'total_loss_value' => $totalLoss,
            'disposal_method' => $approvalData['disposal_method'],
            'disposal_date' => $approvalData['disposal_date'],
            'disposal_witness' => $approvalData['disposal_witness'],
            'approved_by' => $this->request->user_id ?? null
        ]);
        
        $db->transComplete();
        
        return $this->respond([
            'message' => 'Damage report approved and disposed',
            'total_loss' => $totalLoss
        ]);
    }
    
    /**
     * Upload document to damage report
     */
    public function uploadDocument($id = null)
    {
        $report = $this->damageModel->find($id);
        
        if (!$report) {
            return $this->failNotFound('Report not found');
        }
        
        $file = $this->request->getFile('document');
        
        if (!$file || !$file->isValid()) {
            return $this->fail('Invalid file');
        }
        
        // Upload to CDN
        $cdnController = new \App\Controllers\Api\CdnController();
        // Use CDN upload logic here
        
        $cdnFileId = 1; // Replace with actual CDN upload result
        
        $documents = $report['documents'] ? json_decode($report['documents'], true) : [];
        $documents[] = $cdnFileId;
        
        $this->damageModel->update($id, [
            'documents' => json_encode($documents)
        ]);
        
        return $this->respond([
            'message' => 'Document uploaded',
            'cdn_file_id' => $cdnFileId
        ]);
    }
}
<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvStockOpnameModel;
use App\Models\Inv\InvStockModel;
use App\Models\Inv\InvStockMovementModel;
use CodeIgniter\API\ResponseTrait;

class InvStockOpnameController extends BaseController
{
    use ResponseTrait;
    
    protected $opnameModel;
    protected $stockModel;
    protected $movementModel;
    
    public function __construct()
    {
        $this->opnameModel = new InvStockOpnameModel();
        $this->stockModel = new InvStockModel();
        $this->movementModel = new InvStockMovementModel();
    }
    
    /**
     * List stock opname
     */
    public function index()
    {
        $page = $this->request->getGet('page') ?? 1;
        $limit = $this->request->getGet('limit') ?? 20;
        $status = $this->request->getGet('status');
        
        $builder = $this->opnameModel->select('inv_stock_opname.*, inv_warehouses.warehouse_name')
                                     ->join('inv_warehouses', 'inv_warehouses.id = inv_stock_opname.warehouse_id');
        
        if ($status) {
            $builder->where('inv_stock_opname.status', $status);
        }
        
        $total = $builder->countAllResults(false);
        $opnames = $builder->orderBy('inv_stock_opname.created_at', 'DESC')
                           ->findAll($limit, ($page - 1) * $limit);
        
        return $this->respond([
            'data' => $opnames,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => $total
            ]
        ]);
    }
    
    /**
     * Create stock opname
     */
    public function create()
    {
        $rules = [
            'opname_date' => 'required|valid_date',
            'warehouse_id' => 'required|integer',
            'opname_type' => 'required|in_list[full,partial,cycle_count,random]'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        $data['opname_number'] = $this->opnameModel->generateOpnameNumber();
        $data['status'] = 'draft';
        $data['started_by'] = $this->request->user_id ?? null;
        
        $id = $this->opnameModel->insert($data);
        
        return $this->respondCreated([
            'message' => 'Stock opname created',
            'id' => $id,
            'opname_number' => $data['opname_number']
        ]);
    }
    
    /**
     * Start stock opname (change status to in_progress)
     */
    public function start($id = null)
    {
        $opname = $this->opnameModel->find($id);
        
        if (!$opname) {
            return $this->failNotFound('Stock opname not found');
        }
        
        if ($opname['status'] !== 'draft') {
            return $this->fail('Only draft opname can be started', 400);
        }
        
        $this->opnameModel->update($id, [
            'status' => 'in_progress',
            'started_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->respond([
            'message' => 'Stock opname started'
        ]);
    }
    
    /**
     * Add opname item (scan result)
     */
    public function addItem($opnameId = null)
    {
        $opname = $this->opnameModel->find($opnameId);
        
        if (!$opname) {
            return $this->failNotFound('Stock opname not found');
        }
        
        if (!in_array($opname['status'], ['draft', 'in_progress'])) {
            return $this->fail('Cannot add items to this opname', 400);
        }
        
        $rules = [
            'material_id' => 'required|integer',
            'actual_qty' => 'required|numeric'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        
        // Get system quantity
        $stock = $this->stockModel->where([
            'material_id' => $data['material_id'],
            'warehouse_id' => $opname['warehouse_id'],
            'rack_id' => $data['rack_id'] ?? null,
            'batch_number' => $data['batch_number'] ?? null
        ])->first();
        
        $systemQty = $stock ? $stock['quantity'] : 0;
        $unitCost = $data['unit_cost'] ?? 0;
        
        $itemModel = new \App\Models\Inv\InvStockOpnameItemModel();
        $itemModel->insert([
            'opname_id' => $opnameId,
            'material_id' => $data['material_id'],
            'rack_id' => $data['rack_id'] ?? null,
            'batch_number' => $data['batch_number'] ?? null,
            'system_qty' => $systemQty,
            'actual_qty' => $data['actual_qty'],
            'unit_cost' => $unitCost,
            'notes' => $data['notes'] ?? null
        ]);
        
        return $this->respond([
            'message' => 'Item added',
            'system_qty' => $systemQty,
            'actual_qty' => $data['actual_qty'],
            'variance' => $data['actual_qty'] - $systemQty
        ]);
    }
    
    /**
     * Complete stock opname
     */
    public function complete($id = null)
    {
        $opname = $this->opnameModel->find($id);
        
        if (!$opname) {
            return $this->failNotFound('Stock opname not found');
        }
        
        $itemModel = new \App\Models\Inv\InvStockOpnameItemModel();
        $items = $itemModel->where('opname_id', $id)->findAll();
        
        if (count($items) === 0) {
            return $this->fail('No items in this opname', 400);
        }
        
        $totalItems = count($items);
        $itemsMatched = 0;
        $itemsDiscrepancy = 0;
        $totalVariance = 0;
        
        $db = \Config\Database::connect();
        $db->transStart();
        
        foreach ($items as $item) {
            $variance = $item['actual_qty'] - $item['system_qty'];
            $totalVariance += abs($variance * $item['unit_cost']);
            
            if ($variance == 0) {
                $itemsMatched++;
            } else {
                $itemsDiscrepancy++;
                
                // Create stock adjustment
                if ($variance > 0) {
                    $movementType = 'stock_opname_in';
                } else {
                    $movementType = 'stock_opname_out';
                }
                
                // Update stock
                $this->stockModel->updateStock(
                    $item['material_id'],
                    $opname['warehouse_id'],
                    $item['rack_id'],
                    $item['batch_number'],
                    $variance
                );
                
                // Record movement
                $this->movementModel->insert([
                    'movement_type' => $movementType,
                    'material_id' => $item['material_id'],
                    'to_warehouse_id' => $variance > 0 ? $opname['warehouse_id'] : null,
                    'from_warehouse_id' => $variance < 0 ? $opname['warehouse_id'] : null,
                    'to_rack_id' => $item['rack_id'],
                    'batch_number' => $item['batch_number'],
                    'quantity' => abs($variance),
                    'reference_type' => 'opname',
                    'reference_id' => $id,
                    'reference_number' => $opname['opname_number'],
                    'notes' => 'Stock opname adjustment'
                ]);
            }
            
            // Update last counted
            $this->stockModel->where([
                'material_id' => $item['material_id'],
                'warehouse_id' => $opname['warehouse_id']
            ])->set('last_counted_at', date('Y-m-d H:i:s'))->update();
        }
        
        $this->opnameModel->update($id, [
            'status' => 'completed',
            'total_items' => $totalItems,
            'items_matched' => $itemsMatched,
            'items_discrepancy' => $itemsDiscrepancy,
            'total_variance_value' => $totalVariance,
            'completed_by' => $this->request->user_id ?? null,
            'completed_at' => date('Y-m-d H:i:s')
        ]);
        
        $db->transComplete();
        
        return $this->respond([
            'message' => 'Stock opname completed',
            'summary' => [
                'total_items' => $totalItems,
                'matched' => $itemsMatched,
                'discrepancy' => $itemsDiscrepancy,
                'total_variance' => $totalVariance
            ]
        ]);
    }
    
    /**
     * Get opname detail with items
     */
    public function show($id = null)
    {
        $opname = $this->opnameModel->select('inv_stock_opname.*, inv_warehouses.warehouse_name')
                                     ->join('inv_warehouses', 'inv_warehouses.id = inv_stock_opname.warehouse_id')
                                     ->where('inv_stock_opname.id', $id)
                                     ->first();
        
        if (!$opname) {
            return $this->failNotFound('Stock opname not found');
        }
        
        $itemModel = new \App\Models\Inv\InvStockOpnameItemModel();
        $items = $itemModel->select('inv_stock_opname_items.*, inv_materials.material_name, inv_materials.material_code')
                           ->join('inv_materials', 'inv_materials.id = inv_stock_opname_items.material_id')
                           ->where('opname_id', $id)
                           ->findAll();
        
        $opname['items'] = $items;
        
        return $this->respond($opname);
    }
}
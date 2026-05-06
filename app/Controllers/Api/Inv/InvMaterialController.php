<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvMaterialModel;
use CodeIgniter\API\ResponseTrait;

class InvMaterialController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new InvMaterialModel();
    }
    
    public function index()
    {
        $page = $this->request->getGet('page') ?? 1;
        $limit = $this->request->getGet('limit') ?? 50;
        $categoryId = $this->request->getGet('category_id');
        $materialType = $this->request->getGet('material_type');
        $search = $this->request->getGet('search');
        
        $builder = $this->model->select('inv_materials.*, inv_material_categories.category_name, inv_uom.uom_name')
                               ->join('inv_material_categories', 'inv_material_categories.id = inv_materials.category_id')
                               ->join('inv_uom', 'inv_uom.id = inv_materials.uom_id');
        
        if ($categoryId) {
            $builder->where('inv_materials.category_id', $categoryId);
        }
        
        if ($materialType) {
            $builder->where('inv_materials.material_type', $materialType);
        }
        
        if ($search) {
            $builder->groupStart()
                    ->like('inv_materials.material_code', $search)
                    ->orLike('inv_materials.material_name', $search)
                    ->groupEnd();
        }
        
        $total = $builder->countAllResults(false);
        $materials = $builder->orderBy('inv_materials.material_name', 'ASC')
                             ->findAll($limit, ($page - 1) * $limit);
        
        return $this->respond([
            'data' => $materials,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    public function create()
    {
        $rules = [
            'material_name' => 'required|min_length[3]',
            'category_id' => 'required|integer',
            'material_type' => 'required',
            'uom_id' => 'required|integer'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        
        // Generate material code
        $categoryModel = new \App\Models\Inv\InvMaterialCategoryModel();
        $category = $categoryModel->find($data['category_id']);
        $data['material_code'] = $this->model->generateCode($category['category_code'], $data['material_type']);
        
        $id = $this->model->insert($data);
        
        return $this->respondCreated([
            'message' => 'Material created successfully',
            'id' => $id,
            'material_code' => $data['material_code']
        ]);
    }
    
    public function show($id = null)
    {
        $material = $this->model->select('inv_materials.*, inv_material_categories.category_name, inv_uom.uom_name')
                                ->join('inv_material_categories', 'inv_material_categories.id = inv_materials.category_id')
                                ->join('inv_uom', 'inv_uom.id = inv_materials.uom_id')
                                ->where('inv_materials.id', $id)
                                ->first();
        
        if (!$material) {
            return $this->failNotFound('Material not found');
        }
        
        // Get current stock
        $stockModel = new \App\Models\Inv\InvStockModel();
        $material['stock'] = $stockModel->getStockByMaterial($id);
        
        return $this->respond($material);
    }
    
    public function update($id = null)
    {
        $material = $this->model->find($id);
        
        if (!$material) {
            return $this->failNotFound('Material not found');
        }
        
        $data = $this->request->getJSON(true);
        $this->model->update($id, $data);
        
        return $this->respond([
            'message' => 'Material updated successfully'
        ]);
    }
    
    public function delete($id = null)
    {
        $material = $this->model->find($id);
        
        if (!$material) {
            return $this->failNotFound('Material not found');
        }
        
        // Soft delete (set inactive)
        $this->model->update($id, ['is_active' => 0]);
        
        return $this->respond([
            'message' => 'Material deactivated successfully'
        ]);
    }
    
    /**
     * Get low stock materials
     */
    public function lowStock()
    {
        $materials = $this->model->getLowStock();
        return $this->respond($materials);
    }
    
    /**
     * Get materials grouped by type
     */
    public function byType()
    {
        $types = [
            'wood' => ['raw_wood', 'processed_wood', 'veneer', 'plywood', 'mdf', 'particle_board'],
            'finishing' => ['finishing_paint', 'finishing_stain', 'finishing_varnish', 'finishing_thinner'],
            'fabric' => ['fabric', 'foam', 'leather', 'synthetic_leather'],
            'hardware' => ['hardware', 'screw', 'nail', 'glue'],
            'packaging' => ['packaging_box', 'packaging_foam', 'packaging_tape', 'packaging_label']
        ];
        
        $result = [];
        foreach ($types as $group => $typeList) {
            $result[$group] = $this->model->whereIn('material_type', $typeList)->findAll();
        }
        
        return $this->respond($result);
    }
}
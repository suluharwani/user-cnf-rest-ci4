<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvSupplierModel;
use CodeIgniter\API\ResponseTrait;

class InvSupplierController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new InvSupplierModel();
    }
    
    public function index()
    {
        $suppliers = $this->model->getWithCurrency();
        return $this->respond(['data' => $suppliers]);
    }
    
    public function create()
    {
        $rules = [
            'supplier_code' => 'required|is_unique[inv_suppliers.supplier_code]',
            'supplier_name' => 'required',
            'supplier_type' => 'required|in_list[international,local]'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        $id = $this->model->insert($data);
        
        return $this->respondCreated(['message' => 'Supplier created', 'id' => $id]);
    }
    
    public function show($id = null)
    {
        $supplier = $this->model->getWithCurrency($id);
        if (!$supplier) return $this->failNotFound('Supplier not found');
        return $this->respond($supplier);
    }
    
    public function update($id = null)
    {
        $supplier = $this->model->find($id);
        if (!$supplier) return $this->failNotFound('Supplier not found');
        
        $data = $this->request->getJSON(true);
        $this->model->update($id, $data);
        return $this->respond(['message' => 'Supplier updated']);
    }
    
    public function international() {
        return $this->respond(['data' => $this->model->getInternational()]);
    }
    
    public function local() {
        return $this->respond(['data' => $this->model->getLocal()]);
    }
}
<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvCurrencyModel;
use CodeIgniter\API\ResponseTrait;

class InvCurrencyController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new InvCurrencyModel();
    }
    
    public function index()
    {
        return $this->respond(['data' => $this->model->getActiveCurrencies()]);
    }
    
    public function create()
    {
        $rules = ['code' => 'required|is_unique[inv_currencies.code]', 'name' => 'required', 'symbol' => 'required'];
        if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());
        
        $id = $this->model->insert($this->request->getJSON(true));
        return $this->respondCreated(['message' => 'Currency created', 'id' => $id]);
    }
    
    public function update($id = null)
    {
        $currency = $this->model->find($id);
        if (!$currency) return $this->failNotFound('Currency not found');
        $this->model->update($id, $this->request->getJSON(true));
        return $this->respond(['message' => 'Currency updated']);
    }
}
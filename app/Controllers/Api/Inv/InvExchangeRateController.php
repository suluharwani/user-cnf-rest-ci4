<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvExchangeRateModel;
use CodeIgniter\API\ResponseTrait;

class InvExchangeRateController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new InvExchangeRateModel();
    }
    
    public function index()
    {
        return $this->respond(['data' => $this->model->findAll()]);
    }
    
    public function create()
    {
        $rules = ['from_currency_id' => 'required', 'to_currency_id' => 'required', 'rate' => 'required', 'effective_date' => 'required'];
        if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());
        
        $data = $this->request->getJSON(true);
        $data['created_by'] = $this->request->user_id ?? null;
        $id = $this->model->insert($data);
        return $this->respondCreated(['message' => 'Exchange rate created', 'id' => $id]);
    }
    
    public function latest()
    {
        return $this->respond(['data' => $this->model->getLatestRates()]);
    }
}
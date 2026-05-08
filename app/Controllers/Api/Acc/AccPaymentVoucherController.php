<?php

namespace App\Controllers\Api\Acc;

use App\Controllers\BaseController;
use App\Models\Acc\AccPaymentVoucherModel;
use CodeIgniter\API\ResponseTrait;

class AccPaymentVoucherController extends BaseController
{
    use ResponseTrait;
    protected $model;
    
    public function __construct() { $this->model = new AccPaymentVoucherModel(); }
    
    public function index() {
        return $this->respond(['data' => $this->model->getWithDetails()]);
    }
    
    public function create() {
        $data = $this->request->getJSON(true);
        $data['voucher_number'] = $this->model->generateNumber();
        $data['status'] = 'draft';
        $data['created_by'] = $this->request->user_id ?? null;
        
        $id = $this->model->insert($data);
        return $this->respondCreated(['message' => 'Voucher created', 'id' => $id, 'voucher_number' => $data['voucher_number']]);
    }
    
    public function show($id = null) {
        $voucher = $this->model->getWithDetails($id);
        if (!$voucher) return $this->failNotFound();
        return $this->respond($voucher);
    }
    
    public function approve($id = null) {
        $this->model->approve($id, $this->request->user_id ?? null);
        return $this->respond(['message' => 'Voucher approved']);
    }
}
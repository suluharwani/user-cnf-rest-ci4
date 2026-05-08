<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvSupplierEvaluationModel;
use App\Models\Inv\InvSupplierModel;
use CodeIgniter\API\ResponseTrait;

class InvSupplierEvaluationController extends BaseController
{
    use ResponseTrait;
    protected $evalModel;
    protected $supplierModel;
    
    public function __construct()
    {
        $this->evalModel = new InvSupplierEvaluationModel();
        $this->supplierModel = new InvSupplierModel();
    }
    
    // GET /api/inv/supplier-evaluations
    public function index()
    {
        $supplierId = $this->request->getGet('supplier_id');
        $builder = $this->evalModel->select('inv_supplier_evaluations.*, inv_suppliers.supplier_name')
                                   ->join('inv_suppliers', 'inv_suppliers.id = inv_supplier_evaluations.supplier_id');
        
        if ($supplierId) $builder->where('supplier_id', $supplierId);
        
        return $this->respond(['data' => $builder->orderBy('evaluation_date', 'DESC')->findAll()]);
    }
    
    // POST /api/inv/supplier-evaluations
    public function create()
    {
        $data = $this->request->getJSON(true);
        
        $overallScore = $this->evalModel->calculateOverallScore(
            $data['quality_score'] ?? 0,
            $data['delivery_score'] ?? 0,
            $data['price_score'] ?? 0,
            $data['service_score'] ?? 0
        );
        
        $data['overall_score'] = $overallScore;
        $data['rating'] = $this->evalModel->determineRating($overallScore);
        $data['evaluator'] = $this->request->user_id ?? null;
        
        $id = $this->evalModel->insert($data);
        
        // Update supplier rating
        $this->supplierModel->update($data['supplier_id'], [
            'rating' => $overallScore
        ]);
        
        return $this->respondCreated([
            'message' => 'Evaluation created',
            'id' => $id,
            'overall_score' => $overallScore,
            'rating' => $data['rating']
        ]);
    }
    
    // GET /api/inv/supplier-evaluations/{id}
    public function show($id = null)
    {
        $evaluation = $this->evalModel->find($id);
        if (!$evaluation) return $this->failNotFound();
        return $this->respond($evaluation);
    }
    
    // GET /api/inv/suppliers/{id}/evaluations
    public function getSupplierHistory($supplierId = null)
    {
        $history = $this->evalModel->getHistory($supplierId);
        $latest = $this->evalModel->getLatest($supplierId);
        
        return $this->respond([
            'supplier_id' => (int)$supplierId,
            'latest_evaluation' => $latest,
            'history' => $history
        ]);
    }
}
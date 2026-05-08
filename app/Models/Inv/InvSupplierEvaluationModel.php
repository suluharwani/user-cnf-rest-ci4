<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvSupplierEvaluationModel extends Model
{
    protected $table = 'inv_supplier_evaluations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'supplier_id', 'evaluation_date', 'evaluation_period',
        'quality_score', 'delivery_score', 'price_score', 'service_score',
        'overall_score', 'total_orders', 'on_time_deliveries',
        'rejected_items', 'total_purchase_value', 'rating', 'evaluator', 'notes'
    ];
    
    /**
     * Calculate overall score
     */
    public function calculateOverallScore($quality, $delivery, $price, $service)
    {
        return round(($quality * 0.35) + ($delivery * 0.30) + ($price * 0.20) + ($service * 0.15), 2);
    }
    
    /**
     * Determine rating based on score
     */
    public function determineRating($score)
    {
        if ($score >= 90) return 'A';
        if ($score >= 75) return 'B';
        if ($score >= 60) return 'C';
        return 'D';
    }
    
    /**
     * Get evaluation history for supplier
     */
    public function getHistory($supplierId)
    {
        return $this->where('supplier_id', $supplierId)
                    ->orderBy('evaluation_date', 'DESC')
                    ->findAll();
    }
    
    /**
     * Get latest evaluation
     */
    public function getLatest($supplierId)
    {
        return $this->where('supplier_id', $supplierId)
                    ->orderBy('evaluation_date', 'DESC')
                    ->first();
    }
}
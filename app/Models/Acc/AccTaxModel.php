<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccTaxModel extends Model
{
    protected $table = 'acc_taxes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'tax_code', 'tax_name', 'tax_type', 'tax_rate',
        'is_compound', 'input_coa_id', 'output_coa_id',
        'is_active', 'effective_from', 'effective_to'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null;
    
    /**
     * Get active taxes
     */
    public function getActiveTaxes()
    {
        return $this->where('is_active', 1)
                    ->where('effective_from <=', date('Y-m-d'))
                    ->groupStart()
                        ->where('effective_to IS NULL')
                        ->orWhere('effective_to >=', date('Y-m-d'))
                    ->groupEnd()
                    ->findAll();
    }
    
    /**
     * Get tax by type
     */
    public function getByType($type)
    {
        return $this->where('tax_type', $type)
                    ->where('is_active', 1)
                    ->findAll();
    }
    
    /**
     * Calculate tax amount
     */
    public function calculateTax($taxId, $amount)
    {
        $tax = $this->find($taxId);
        if (!$tax) return 0;
        
        return round($amount * ($tax['tax_rate'] / 100), 2);
    }
    
    /**
     * Get VAT tax
     */
    public function getVatTax()
    {
        return $this->where('tax_type', 'vat')
                    ->where('is_active', 1)
                    ->first();
    }
    
    /**
     * Get withholding taxes
     */
    public function getWithholdingTaxes()
    {
        return $this->where('tax_type', 'withholding')
                    ->where('is_active', 1)
                    ->findAll();
    }
}
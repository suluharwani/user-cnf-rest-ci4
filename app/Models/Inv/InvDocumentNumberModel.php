<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvDocumentNumberModel extends Model
{
    protected $table = 'inv_document_numbers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'doc_type_id', 'prefix', 'year', 'month', 'sequence',
        'format_pattern', 'last_number'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Generate document number with auto-increment
     */
    public function generateNumber($docTypeId, $year, $month)
    {
        $existing = $this->where([
            'doc_type_id' => $docTypeId,
            'year' => (int)$year,
            'month' => (int)$month
        ])->first();
        
        $db = \Config\Database::connect();
        $db->transStart();
        
        if ($existing) {
            $newNumber = $existing['last_number'] + 1;
            $this->update($existing['id'], [
                'last_number' => $newNumber,
                'sequence' => $newNumber
            ]);
            $prefix = $existing['prefix'];
        } else {
            $newNumber = 1;
            
            $docTypeModel = new InvDocumentTypeModel();
            $docType = $docTypeModel->find($docTypeId);
            $prefix = $docType ? $docType['doc_code'] : 'DOC';
            
            $this->insert([
                'doc_type_id' => $docTypeId,
                'prefix' => $prefix,
                'year' => (int)$year,
                'month' => (int)$month,
                'sequence' => $newNumber,
                'last_number' => $newNumber
            ]);
        }
        
        $db->transComplete();
        
        return sprintf('%s/%d/%02d/%05d', $prefix, $year, $month, $newNumber);
    }
    
    /**
     * Preview next number without incrementing
     */
    public function previewNumber($docTypeId, $year, $month)
    {
        $existing = $this->where([
            'doc_type_id' => $docTypeId,
            'year' => (int)$year,
            'month' => (int)$month
        ])->first();
        
        $nextNumber = $existing ? $existing['last_number'] + 1 : 1;
        
        if ($existing) {
            $prefix = $existing['prefix'];
        } else {
            $docTypeModel = new InvDocumentTypeModel();
            $docType = $docTypeModel->find($docTypeId);
            $prefix = $docType ? $docType['doc_code'] : 'DOC';
        }
        
        return sprintf('%s/%d/%02d/%05d', $prefix, $year, $month, $nextNumber);
    }
    
    /**
     * Generate customs document number (PIB format)
     */
    public function generateCustomsNumber($docTypeId)
    {
        $today = date('Y-m-d');
        $year = date('Y');
        $month = date('m');
        
        return $this->generateNumber($docTypeId, $year, $month);
    }
    
    /**
     * Generate shipping document number
     */
    public function generateShippingNumber($docTypeId, $shippingDate)
    {
        $year = date('Y', strtotime($shippingDate));
        $month = date('m', strtotime($shippingDate));
        
        return $this->generateNumber($docTypeId, $year, $month);
    }
    
    /**
     * Reset sequence for new year/month
     */
    public function resetSequence($docTypeId, $year, $month)
    {
        $existing = $this->where([
            'doc_type_id' => $docTypeId,
            'year' => (int)$year,
            'month' => (int)$month
        ])->first();
        
        if ($existing) {
            return $this->update($existing['id'], [
                'last_number' => 0,
                'sequence' => 0
            ]);
        }
        
        return false;
    }
    
    /**
     * Get all sequences for a year
     */
    public function getYearlySequence($docTypeId, $year)
    {
        return $this->where('doc_type_id', $docTypeId)
                    ->where('year', (int)$year)
                    ->orderBy('month', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get document type statistics
     */
    public function getDocTypeStats($year = null)
    {
        if (!$year) {
            $year = date('Y');
        }
        
        return $this->select('inv_document_types.doc_name, inv_document_types.doc_code,
                             SUM(inv_document_numbers.last_number) as total_issued')
                    ->join('inv_document_types', 'inv_document_types.id = inv_document_numbers.doc_type_id')
                    ->where('inv_document_numbers.year', (int)$year)
                    ->groupBy('inv_document_numbers.doc_type_id')
                    ->findAll();
    }
}
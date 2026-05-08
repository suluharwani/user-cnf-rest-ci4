<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvBusinessDocumentModel extends Model
{
    protected $table = 'inv_business_documents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'reference_type', 'reference_id', 'doc_name', 'doc_type', 'doc_number',
        'issue_date', 'expiry_date', 'issuing_authority', 'cdn_file_id', 'file_url',
        'is_verified', 'verified_by', 'verified_at', 'notes'
    ];
    
    protected $useTimestamps = true;
    
    /**
     * Get documents by reference
     */
    public function getByReference($type, $id)
    {
        return $this->where('reference_type', $type)
                    ->where('reference_id', $id)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
    
    /**
     * Get expired documents
     */
    public function getExpiredDocuments()
    {
        return $this->where('expiry_date <', date('Y-m-d'))
                    ->where('expiry_date IS NOT NULL')
                    ->findAll();
    }
    
    /**
     * Get documents expiring soon (within 30 days)
     */
    public function getExpiringSoon($days = 30)
    {
        $futureDate = date('Y-m-d', strtotime("+$days days"));
        return $this->where('expiry_date >=', date('Y-m-d'))
                    ->where('expiry_date <=', $futureDate)
                    ->findAll();
    }
    
    /**
     * Verify document
     */
    public function verify($id, $userId)
    {
        return $this->update($id, [
            'is_verified' => 1,
            'verified_by' => $userId,
            'verified_at' => date('Y-m-d H:i:s')
        ]);
    }
}
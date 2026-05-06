<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvDocumentModel extends Model
{
    protected $table = 'inv_documents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'document_number', 'doc_type_id', 'reference_type', 'reference_id',
        'document_date', 'expiry_date', 'issuing_authority',
        'cdn_file_id', 'file_url', 'notes', 'verified_by', 'verified_at'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get documents with CDN file info
     */
    public function getWithFile()
    {
        return $this->select('inv_documents.*, inv_document_types.doc_name, inv_document_types.doc_category,
                             cdn_files.file_name, cdn_files.file_path, cdn_files.file_size, cdn_files.mime_type')
                    ->join('inv_document_types', 'inv_document_types.id = inv_documents.doc_type_id')
                    ->join('cdn_files', 'cdn_files.id = inv_documents.cdn_file_id', 'left')
                    ->orderBy('inv_documents.document_date', 'DESC')
                    ->findAll();
    }
    
    /**
     * Get documents by reference
     */
    public function getByReference($referenceType, $referenceId)
    {
        return $this->getWithFile()
                    ->where('inv_documents.reference_type', $referenceType)
                    ->where('inv_documents.reference_id', $referenceId)
                    ->findAll();
    }
    
    /**
     * Get missing mandatory documents
     */
    public function getMissingMandatory($referenceType, $referenceId)
    {
        $docTypeModel = new InvDocumentTypeModel();
        $mandatory = $docTypeModel->getMandatory();
        
        $existing = $this->select('doc_type_id')
                         ->where('reference_type', $referenceType)
                         ->where('reference_id', $referenceId)
                         ->findAll();
        
        $existingIds = array_column($existing, 'doc_type_id');
        $missing = [];
        
        foreach ($mandatory as $doc) {
            if (!in_array($doc['id'], $existingIds)) {
                $missing[] = $doc;
            }
        }
        
        return $missing;
    }
    
    /**
     * Verify document
     */
    public function verify($id, $userId)
    {
        return $this->update($id, [
            'verified_by' => $userId,
            'verified_at' => date('Y-m-d H:i:s')
        ]);
    }
}
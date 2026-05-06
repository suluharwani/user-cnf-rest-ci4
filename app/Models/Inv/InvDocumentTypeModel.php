<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvDocumentTypeModel extends Model
{
    protected $table = 'inv_document_types';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'doc_code', 'doc_name', 'doc_category', 'is_mandatory', 'description'
    ];
    
    /**
     * Get mandatory documents
     */
    public function getMandatory()
    {
        return $this->where('is_mandatory', 1)->findAll();
    }
    
    /**
     * Get documents by category
     */
    public function getByCategory($category)
    {
        return $this->where('doc_category', $category)->findAll();
    }
    
    /**
     * Get customs documents
     */
    public function getCustomsDocuments()
    {
        return $this->where('doc_category', 'customs')->findAll();
    }
}
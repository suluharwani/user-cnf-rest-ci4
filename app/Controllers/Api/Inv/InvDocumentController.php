<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvDocumentModel;
use CodeIgniter\API\ResponseTrait;

class InvDocumentController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    
    public function __construct()
    {
        $this->model = new InvDocumentModel();
    }
    
    /**
     * List documents
     */
    public function index()
    {
        $referenceType = $this->request->getGet('reference_type');
        $referenceId = $this->request->getGet('reference_id');
        $docTypeId = $this->request->getGet('doc_type_id');
        
        $builder = $this->model->select('inv_documents.*, inv_document_types.doc_name, inv_document_types.doc_category, cdn_files.file_name, cdn_files.file_path')
                               ->join('inv_document_types', 'inv_document_types.id = inv_documents.doc_type_id')
                               ->join('cdn_files', 'cdn_files.id = inv_documents.cdn_file_id', 'left');
        
        if ($referenceType) {
            $builder->where('inv_documents.reference_type', $referenceType);
        }
        
        if ($referenceId) {
            $builder->where('inv_documents.reference_id', $referenceId);
        }
        
        if ($docTypeId) {
            $builder->where('inv_documents.doc_type_id', $docTypeId);
        }
        
        $documents = $builder->orderBy('inv_documents.document_date', 'DESC')->findAll();
        
        return $this->respond($documents);
    }
    
    /**
     * Create document record
     */
    public function create()
    {
        $rules = [
            'document_number' => 'required',
            'doc_type_id' => 'required|integer',
            'document_date' => 'required|valid_date'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        $id = $this->model->insert($data);
        
        return $this->respondCreated([
            'message' => 'Document created',
            'id' => $id
        ]);
    }
    
    /**
     * Upload document file to CDN
     */
    public function uploadFile($id = null)
    {
        $document = $this->model->find($id);
        
        if (!$document) {
            return $this->failNotFound('Document not found');
        }
        
        $file = $this->request->getFile('file');
        
        if (!$file || !$file->isValid()) {
            return $this->fail('Invalid file');
        }
        
        if ($file->getSize() > 10485760) {
            return $this->fail('File size exceeds 10 MB');
        }
        
        $uploadPath = WRITEPATH . 'cdn/documents/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $fileName = 'doc_' . $document['document_number'] . '_' . time() . '.' . $file->getClientExtension();
        $file->move($uploadPath, $fileName);
        
        $cdnModel = new \App\Models\CdnFileModel();
        $cdnId = $cdnModel->insert([
            'file_name' => $fileName,
            'original_name' => $file->getClientName(),
            'file_path' => 'cdn/documents/' . $fileName,
            'file_type' => $file->getClientMimeType(),
            'file_extension' => $file->getClientExtension(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'folder' => '/documents',
            'is_public' => 0,
            'uploaded_by' => $this->request->user_id ?? null
        ]);
        
        $this->model->update($id, [
            'cdn_file_id' => $cdnId,
            'file_url' => base_url('api/cdn/view/' . $cdnId)
        ]);
        
        return $this->respond([
            'message' => 'File uploaded',
            'cdn_file_id' => $cdnId,
            'file_url' => base_url('api/cdn/view/' . $cdnId)
        ]);
    }
    
    /**
     * Generate customs document number (PIB)
     */
    public function generateCustomsNumber()
    {
        $docTypeId = $this->request->getGet('doc_type_id');
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        
        $docNumberModel = new \App\Models\Inv\InvDocumentNumberModel();
        $number = $docNumberModel->generateNumber($docTypeId, $year, $month);
        
        return $this->respond([
            'document_number' => $number,
            'doc_type_id' => $docTypeId,
            'year' => $year,
            'month' => $month
        ]);
    }
}
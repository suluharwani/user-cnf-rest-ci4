<?php

namespace App\Controllers\Api\Inv;

use App\Controllers\BaseController;
use App\Models\Inv\InvCustomerModel;
use App\Models\Inv\InvCustomerContactModel;
use App\Models\Inv\InvCustomerAddressModel;
use App\Models\Inv\InvBusinessDocumentModel;
use CodeIgniter\API\ResponseTrait;

class InvCustomerController extends BaseController
{
    use ResponseTrait;
    protected $model;
    protected $contactModel;
    protected $addressModel;
    protected $docModel;
    
    public function __construct()
    {
        $this->model = new InvCustomerModel();
        $this->contactModel = new InvCustomerContactModel();
        $this->addressModel = new InvCustomerAddressModel();
        $this->docModel = new InvBusinessDocumentModel();
    }
    
    // GET /api/inv/customers
    public function index()
    {
        $page = $this->request->getGet('page') ?? 1;
        $limit = $this->request->getGet('limit') ?? 20;
        $search = $this->request->getGet('search');
        $status = $this->request->getGet('status');
        
        $builder = $this->model->select('inv_customers.*, inv_customer_groups.group_name')
                               ->join('inv_customer_groups', 'inv_customer_groups.id = inv_customers.group_id', 'left');
        
        if ($search) {
            $builder->groupStart()
                    ->like('customer_code', $search)
                    ->orLike('customer_name', $search)
                    ->orLike('email', $search)
                    ->groupEnd();
        }
        
        if ($status) $builder->where('status', $status);
        
        $total = $builder->countAllResults(false);
        $customers = $builder->orderBy('customer_name', 'ASC')
                             ->findAll($limit, ($page - 1) * $limit);
        
        return $this->respond([
            'data' => $customers,
            'pagination' => ['page' => (int)$page, 'limit' => (int)$limit, 'total' => $total]
        ]);
    }
    
    // POST /api/inv/customers
    public function create()
    {
        $rules = ['customer_name' => 'required|min_length[3]'];
        if (!$this->validate($rules)) return $this->failValidationErrors($this->validator->getErrors());
        
        $data = $this->request->getJSON(true);
        $data['customer_code'] = $this->model->generateCode();
        $data['created_by'] = $this->request->user_id ?? null;
        
        $id = $this->model->insert($data);
        return $this->respondCreated(['message' => 'Customer created', 'id' => $id, 'customer_code' => $data['customer_code']]);
    }
    
    // GET /api/inv/customers/{id}
    public function show($id = null)
    {
        $customer = $this->model->getWithDetails($id);
        if (!$customer) return $this->failNotFound('Customer not found');
        return $this->respond($customer);
    }
    
    // PUT /api/inv/customers/{id}
    public function update($id = null)
    {
        $customer = $this->model->find($id);
        if (!$customer) return $this->failNotFound('Customer not found');
        
        $data = $this->request->getJSON(true);
        $this->model->update($id, $data);
        return $this->respond(['message' => 'Customer updated']);
    }
    
    // POST /api/inv/customers/{id}/contacts
    public function addContact($customerId = null)
    {
        $data = $this->request->getJSON(true);
        $data['customer_id'] = $customerId;
        
        $id = $this->contactModel->insert($data);
        return $this->respondCreated(['message' => 'Contact added', 'id' => $id]);
    }
    
    // POST /api/inv/customers/{id}/addresses
    public function addAddress($customerId = null)
    {
        $data = $this->request->getJSON(true);
        $data['customer_id'] = $customerId;
        
        $id = $this->addressModel->insert($data);
        return $this->respondCreated(['message' => 'Address added', 'id' => $id]);
    }
    
    // POST /api/inv/customers/{id}/documents
    public function uploadDocument($customerId = null)
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) return $this->fail('Invalid file');
        
        $uploadPath = WRITEPATH . 'cdn/documents/customers/';
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
        
        $fileName = 'cust_doc_' . $customerId . '_' . time() . '.' . $file->getClientExtension();
        $file->move($uploadPath, $fileName);
        
        $cdnModel = new \App\Models\CdnFileModel();
        $cdnId = $cdnModel->insert([
            'file_name' => $fileName,
            'original_name' => $file->getClientName(),
            'file_path' => 'cdn/documents/customers/' . $fileName,
            'file_type' => $file->getClientMimeType(),
            'file_extension' => $file->getClientExtension(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'folder' => '/documents/customers',
            'is_public' => 0,
            'uploaded_by' => $this->request->user_id ?? null
        ]);
        
        $docData = $this->request->getPost();
        $this->docModel->insert([
            'reference_type' => 'customer',
            'reference_id' => $customerId,
            'doc_name' => $docData['doc_name'] ?? $file->getClientName(),
            'doc_type' => $docData['doc_type'] ?? null,
            'doc_number' => $docData['doc_number'] ?? null,
            'cdn_file_id' => $cdnId,
            'file_url' => base_url('api/cdn/view/' . $cdnId),
            'notes' => $docData['notes'] ?? null
        ]);
        
        return $this->respondCreated(['message' => 'Document uploaded', 'cdn_file_id' => $cdnId]);
    }
    
    // PUT /api/inv/customers/{id}/block
    public function block($id = null)
    {
        $data = $this->request->getJSON(true);
        $this->model->block($id, $data['reason'] ?? 'No reason provided');
        return $this->respond(['message' => 'Customer blocked']);
    }
    
    // PUT /api/inv/customers/{id}/activate
    public function activate($id = null)
    {
        $this->model->update($id, ['status' => 'active', 'blocked_reason' => null]);
        return $this->respond(['message' => 'Customer activated']);
    }
}
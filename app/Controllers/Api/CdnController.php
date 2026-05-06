<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CdnFileModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Files\File;

class CdnController extends BaseController
{
    use ResponseTrait;
    
    protected $model;
    protected $uploadPath;
    protected $maxFileSize = 10485760; // 10 MB dalam bytes
    protected $allowedTypes = '*'; // Semua tipe file diizinkan
    
    public function __construct()
    {
        $this->model = new CdnFileModel();
        $this->uploadPath = WRITEPATH . 'cdn/';
        
        // Buat direktori jika belum ada
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    /**
     * Upload file ke CDN
     * POST /api/cdn/upload
     */
    public function upload()
    {
        $file = $this->request->getFile('file');
        
        if (!$file || !$file->isValid()) {
            return $this->failValidationErrors([
                'file' => $file ? $file->getErrorString() : 'No file uploaded'
            ]);
        }
        
        // Validasi ukuran file (maksimal 10 MB)
        if ($file->getSize() > $this->maxFileSize) {
            return $this->fail('File size exceeds maximum limit of 10 MB', 400);
        }
        
        // Get upload parameters
        $folder = $this->request->getPost('folder') ?? '/';
        $isPublic = $this->request->getPost('is_public') ?? '1';
        $customName = $this->request->getPost('custom_name') ?? null;
        
        // Sanitasi folder path
        $folder = $this->sanitizeFolderPath($folder);
        
        // Generate unique filename
        $originalName = $file->getClientName();
        $extension = $file->getClientExtension() ?: $file->guessExtension();
        $fileName = $customName 
            ? $this->sanitizeFileName($customName) . '.' . $extension
            : $this->generateUniqueFileName($originalName, $extension);
        
        // Buat direktori folder jika belum ada
        $targetDir = $this->uploadPath . ltrim($folder, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Pindahkan file ke direktori tujuan
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        
        try {
            $file->move($targetDir, $fileName);
            
            // Generate checksum
            $checksum = md5_file($filePath);
            
            // Simpan metadata ke database
            $fileData = [
                'file_name' => $fileName,
                'original_name' => $originalName,
                'file_path' => 'cdn/' . ltrim($folder, '/') . '/' . $fileName,
                'file_type' => $file->getClientMimeType(),
                'file_extension' => strtolower($extension),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'checksum' => $checksum,
                'folder' => $folder,
                'is_public' => (int)$isPublic,
                'uploaded_by' => $this->request->user_id ?? null
            ];
            
            $fileId = $this->model->insert($fileData);
            
            if (!$fileId) {
                // Hapus file jika gagal menyimpan metadata
                unlink($filePath);
                return $this->fail('Failed to save file metadata');
            }
            
            // Generate URL
            $fileData['id'] = $fileId;
            $fileData['url'] = base_url('api/cdn/download/' . $fileId);
            $fileData['direct_url'] = base_url($fileData['file_path']);
            
            return $this->respondCreated([
                'message' => 'File uploaded successfully',
                'file' => $this->formatFileResponse($fileData)
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'CDN Upload Error: ' . $e->getMessage());
            return $this->fail('Upload failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Upload multiple files
     * POST /api/cdn/upload-multiple
     */
    public function uploadMultiple()
    {
        $files = $this->request->getFiles();
        
        if (empty($files['files'])) {
            return $this->fail('No files uploaded');
        }
        
        $folder = $this->request->getPost('folder') ?? '/';
        $isPublic = $this->request->getPost('is_public') ?? '1';
        
        $uploadedFiles = [];
        $errors = [];
        
        foreach ($files['files'] as $index => $file) {
            if (!$file->isValid()) {
                $errors[] = [
                    'index' => $index,
                    'filename' => $file->getClientName(),
                    'error' => $file->getErrorString()
                ];
                continue;
            }
            
            // Validasi ukuran
            if ($file->getSize() > $this->maxFileSize) {
                $errors[] = [
                    'index' => $index,
                    'filename' => $file->getClientName(),
                    'error' => 'File size exceeds 10 MB limit'
                ];
                continue;
            }
            
            try {
                $result = $this->processSingleUpload($file, $folder, $isPublic);
                $uploadedFiles[] = $result;
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'filename' => $file->getClientName(),
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $this->respond([
            'message' => count($uploadedFiles) . ' files uploaded',
            'uploaded' => $uploadedFiles,
            'errors' => $errors
        ]);
    }
    
    /**
     * Download file dari CDN
     * GET /api/cdn/download/{id}
     */
    public function download($id = null)
    {
        $file = $this->model->find($id);
        
        if (!$file) {
            return $this->failNotFound('File not found');
        }
        
        $filePath = WRITEPATH . $file['file_path'];
        
        if (!file_exists($filePath)) {
            return $this->failNotFound('File not found on server');
        }
        
        // Increment download count
        $this->model->incrementDownloadCount($id);
        
        // Set headers untuk download
        return $this->response
            ->setHeader('Content-Type', $file['mime_type'])
            ->setHeader('Content-Disposition', 'attachment; filename="' . $file['original_name'] . '"')
            ->setHeader('Content-Length', $file['file_size'])
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setHeader('X-File-Id', $file['id'])
            ->setHeader('X-File-Name', $file['original_name'])
            ->setBody(file_get_contents($filePath));
    }
    
    /**
     * Stream/view file (untuk preview)
     * GET /api/cdn/view/{id}
     */
    public function view($id = null)
    {
        $file = $this->model->find($id);
        
        if (!$file) {
            return $this->failNotFound('File not found');
        }
        
        $filePath = WRITEPATH . $file['file_path'];
        
        if (!file_exists($filePath)) {
            return $this->failNotFound('File not found on server');
        }
        
        // Increment download count
        $this->model->incrementDownloadCount($id);
        
        // Set headers untuk inline viewing
        return $this->response
            ->setHeader('Content-Type', $file['mime_type'])
            ->setHeader('Content-Disposition', 'inline; filename="' . $file['original_name'] . '"')
            ->setHeader('Content-Length', $file['file_size'])
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setBody(file_get_contents($filePath));
    }
    
    /**
     * List semua file di CDN
     * GET /api/cdn/files
     */
    public function listFiles()
    {
        $folder = $this->request->getGet('folder') ?? '/';
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = (int)($this->request->getGet('limit') ?? 50);
        $search = $this->request->getGet('search') ?? null;
        
        if ($search) {
            $files = $this->model->searchFiles($search, $limit);
        } else {
            $files = $this->model->getFilesByFolder($folder, $limit, ($page - 1) * $limit);
        }
        
        // Format response dengan URL
        $formattedFiles = array_map(function($file) {
            return $this->formatFileResponse($file);
        }, $files);
        
        return $this->respond([
            'files' => $formattedFiles,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($formattedFiles)
            ]
        ]);
    }
    
    /**
     * Get file info
     * GET /api/cdn/files/{id}
     */
    public function getFileInfo($id = null)
    {
        $file = $this->model->find($id);
        
        if (!$file) {
            return $this->failNotFound('File not found');
        }
        
        return $this->respond([
            'file' => $this->formatFileResponse($file)
        ]);
    }
    
    /**
     * Update file metadata
     * PUT /api/cdn/files/{id}
     */
    public function updateFile($id = null)
    {
        $file = $this->model->find($id);
        
        if (!$file) {
            return $this->failNotFound('File not found');
        }
        
        $data = $this->request->getJSON(true);
        
        $allowedUpdates = ['original_name', 'folder', 'is_public'];
        $updateData = array_intersect_key($data, array_flip($allowedUpdates));
        
        if (empty($updateData)) {
            return $this->fail('No valid data to update');
        }
        
        $this->model->update($id, $updateData);
        
        return $this->respond([
            'message' => 'File updated successfully',
            'file' => $this->formatFileResponse($this->model->find($id))
        ]);
    }
    
    /**
     * Delete file dari CDN
     * DELETE /api/cdn/files/{id}
     */
    public function deleteFile($id = null)
    {
        $file = $this->model->find($id);
        
        if (!$file) {
            return $this->failNotFound('File not found');
        }
        
        // Hapus file fisik
        $filePath = WRITEPATH . $file['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Soft delete dari database
        $this->model->delete($id);
        
        return $this->respond([
            'message' => 'File deleted successfully'
        ]);
    }
    
    /**
     * Get CDN statistics
     * GET /api/cdn/stats
     */
    public function stats()
    {
        $stats = $this->model->getStats();
        
        // Format bytes ke human readable
        $stats['total_size_formatted'] = $this->formatFileSize($stats['total_size']);
        
        return $this->respond($stats);
    }
    
    /**
     * Create folder
     * POST /api/cdn/folders
     */
    public function createFolder()
    {
        $folderPath = $this->request->getPost('path');
        
        if (!$folderPath) {
            return $this->fail('Folder path is required');
        }
        
        $folderPath = $this->sanitizeFolderPath($folderPath);
        $fullPath = $this->uploadPath . ltrim($folderPath, '/');
        
        if (is_dir($fullPath)) {
            return $this->fail('Folder already exists');
        }
        
        if (mkdir($fullPath, 0755, true)) {
            return $this->respondCreated([
                'message' => 'Folder created successfully',
                'path' => $folderPath
            ]);
        }
        
        return $this->fail('Failed to create folder');
    }
    
    /**
     * List folders
     * GET /api/cdn/folders
     */
    public function listFolders()
    {
        $basePath = $this->request->getGet('path') ?? '/';
        $basePath = $this->sanitizeFolderPath($basePath);
        $fullPath = $this->uploadPath . ltrim($basePath, '/');
        
        if (!is_dir($fullPath)) {
            return $this->failNotFound('Folder not found');
        }
        
        $folders = [];
        $items = scandir($fullPath);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $folders[] = [
                    'name' => $item,
                    'path' => $basePath . '/' . $item,
                    'file_count' => $this->countFilesInFolder($basePath . '/' . $item)
                ];
            }
        }
        
        return $this->respond(['folders' => $folders]);
    }
    
    // ==================== PRIVATE METHODS ====================
    
    /**
     * Process single file upload
     */
    private function processSingleUpload($file, $folder, $isPublic)
    {
        $folder = $this->sanitizeFolderPath($folder);
        $originalName = $file->getClientName();
        $extension = $file->getClientExtension() ?: $file->guessExtension();
        $fileName = $this->generateUniqueFileName($originalName, $extension);
        
        $targetDir = $this->uploadPath . ltrim($folder, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        $file->move($targetDir, $fileName);
        
        $fileData = [
            'file_name' => $fileName,
            'original_name' => $originalName,
            'file_path' => 'cdn/' . ltrim($folder, '/') . '/' . $fileName,
            'file_type' => $file->getClientMimeType(),
            'file_extension' => strtolower($extension),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'checksum' => md5_file($filePath),
            'folder' => $folder,
            'is_public' => (int)$isPublic,
            'uploaded_by' => $this->request->user_id ?? null
        ];
        
        $fileId = $this->model->insert($fileData);
        $fileData['id'] = $fileId;
        
        return $this->formatFileResponse($fileData);
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFileName($originalName, $extension)
    {
        $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
        $sanitizedName = $this->sanitizeFileName($nameWithoutExt);
        $uniqueId = uniqid('', true);
        
        return $sanitizedName . '_' . $uniqueId . '.' . $extension;
    }
    
    /**
     * Sanitize filename
     */
    private function sanitizeFileName($filename)
    {
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
        // Limit length
        return substr($filename, 0, 100);
    }
    
    /**
     * Sanitize folder path
     */
    private function sanitizeFolderPath($path)
    {
        // Remove multiple slashes
        $path = preg_replace('#/+#', '/', $path);
        // Ensure starts with /
        if (empty($path) || $path[0] !== '/') {
            $path = '/' . $path;
        }
        // Remove trailing slash (except root)
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        // Remove directory traversal
        $path = str_replace('..', '', $path);
        
        return $path;
    }
    
    /**
     * Format file response with URLs
     */
    private function formatFileResponse($file)
    {
        $baseUrl = base_url();
        
        return [
            'id' => $file['id'],
            'file_name' => $file['file_name'],
            'original_name' => $file['original_name'],
            'file_type' => $file['file_type'],
            'file_extension' => $file['file_extension'],
            'file_size' => (int)$file['file_size'],
            'file_size_formatted' => $this->formatFileSize($file['file_size']),
            'mime_type' => $file['mime_type'],
            'folder' => $file['folder'],
            'is_public' => (bool)$file['is_public'],
            'download_count' => (int)($file['download_count'] ?? 0),
            'checksum' => $file['checksum'] ?? null,
            'url' => $baseUrl . 'api/cdn/download/' . $file['id'],
            'view_url' => $baseUrl . 'api/cdn/view/' . $file['id'],
            'direct_url' => $baseUrl . $file['file_path'],
            'uploaded_at' => $file['created_at'] ?? null,
            'updated_at' => $file['updated_at'] ?? null
        ];
    }
    
    /**
     * Format file size ke human readable
     */
    private function formatFileSize($bytes)
    {
        if ($bytes === 0) return '0 Bytes';
        
        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log(1024));
        
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
    
    /**
     * Count files in folder
     */
    private function countFilesInFolder($folder)
    {
        return $this->model->where('folder', $folder)->countAllResults();
    }
}
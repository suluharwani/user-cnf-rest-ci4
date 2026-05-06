<?php

namespace App\Models;

use CodeIgniter\Model;

class CdnFileModel extends Model
{
    protected $table = 'cdn_files';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    
    protected $allowedFields = [
        'file_name',
        'original_name',
        'file_path',
        'file_type',
        'file_extension',
        'file_size',
        'mime_type',
        'checksum',
        'folder',
        'is_public',
        'uploaded_by',
        'download_count'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'file_name' => 'required|max_length[255]',
        'original_name' => 'required|max_length[255]',
        'file_path' => 'required|max_length[500]',
        'file_type' => 'required|max_length[100]',
        'file_extension' => 'required|max_length[20]',
        'file_size' => 'required|integer',
        'mime_type' => 'required|max_length[100]',
    ];
    
    /**
     * Get files by folder
     */
    public function getFilesByFolder($folder = '/', $limit = 50, $offset = 0)
    {
        return $this->where('folder', $folder)
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limit, $offset);
    }
    
    /**
     * Search files
     */
    public function searchFiles($keyword, $limit = 50)
    {
        return $this->groupStart()
                        ->like('original_name', $keyword)
                        ->orLike('file_name', $keyword)
                        ->orLike('file_type', $keyword)
                    ->groupEnd()
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limit);
    }
    
    /**
     * Get file statistics
     */
    public function getStats()
    {
        $db = \Config\Database::connect();
        
        return [
            'total_files' => $this->countAll(),
            'total_size' => $this->selectSum('file_size')->first()['file_size'] ?? 0,
            'total_downloads' => $this->selectSum('download_count')->first()['download_count'] ?? 0,
            'by_type' => $db->table('cdn_files')
                            ->select('file_type, COUNT(*) as count, SUM(file_size) as total_size')
                            ->groupBy('file_type')
                            ->get()
                            ->getResultArray(),
            'by_extension' => $db->table('cdn_files')
                                ->select('file_extension, COUNT(*) as count')
                                ->groupBy('file_extension')
                                ->get()
                                ->getResultArray(),
        ];
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloadCount($id)
    {
        return $this->where('id', $id)
                    ->set('download_count', 'download_count + 1', false)
                    ->update();
    }
}
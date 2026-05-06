<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvDamageItemModel extends Model
{
    protected $table = 'inv_damage_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'damage_report_id', 'material_id', 'batch_number', 'quantity',
        'volume_m3', 'unit_cost', 'total_cost', 'condition_description',
        'root_cause', 'photo_cdn_ids', 'disposal_method', 'notes'
    ];
    
    protected $casts = [
        'photo_cdn_ids' => 'json'
    ];
    
    /**
     * Get items by damage report ID
     */
    public function getByReport($reportId)
    {
        return $this->select('inv_damage_items.*, 
                             inv_materials.material_name, inv_materials.material_code,
                             inv_materials.material_type, inv_materials.color_name,
                             inv_uom.uom_code, inv_uom.uom_name')
                    ->join('inv_materials', 'inv_materials.id = inv_damage_items.material_id')
                    ->join('inv_uom', 'inv_uom.id = inv_materials.uom_id')
                    ->where('inv_damage_items.damage_report_id', $reportId)
                    ->findAll();
    }
    
    /**
     * Add photo from CDN to damage item
     */
    public function addPhoto($itemId, $cdnFileId)
    {
        $item = $this->find($itemId);
        if (!$item) return false;
        
        $photos = [];
        if (!empty($item['photo_cdn_ids'])) {
            $photos = is_string($item['photo_cdn_ids']) 
                ? json_decode($item['photo_cdn_ids'], true) 
                : $item['photo_cdn_ids'];
        }
        
        if (!is_array($photos)) {
            $photos = [];
        }
        
        $photos[] = (int)$cdnFileId;
        
        return $this->update($itemId, [
            'photo_cdn_ids' => json_encode(array_unique($photos))
        ]);
    }
    
    /**
     * Remove photo from damage item
     */
    public function removePhoto($itemId, $cdnFileId)
    {
        $item = $this->find($itemId);
        if (!$item) return false;
        
        $photos = [];
        if (!empty($item['photo_cdn_ids'])) {
            $photos = is_string($item['photo_cdn_ids']) 
                ? json_decode($item['photo_cdn_ids'], true) 
                : $item['photo_cdn_ids'];
        }
        
        $photos = array_filter($photos, function($id) use ($cdnFileId) {
            return $id != $cdnFileId;
        });
        
        return $this->update($itemId, [
            'photo_cdn_ids' => json_encode(array_values($photos))
        ]);
    }
    
    /**
     * Get photo URLs for damage item
     */
    public function getPhotoUrls($itemId)
    {
        $item = $this->find($itemId);
        if (!$item || empty($item['photo_cdn_ids'])) return [];
        
        $photoIds = is_string($item['photo_cdn_ids']) 
            ? json_decode($item['photo_cdn_ids'], true) 
            : $item['photo_cdn_ids'];
        
        if (empty($photoIds)) return [];
        
        $cdnModel = new \App\Models\CdnFileModel();
        $photos = $cdnModel->whereIn('id', $photoIds)->findAll();
        
        $urls = [];
        foreach ($photos as $photo) {
            $urls[] = [
                'id' => $photo['id'],
                'url' => base_url('api/cdn/view/' . $photo['id']),
                'download_url' => base_url('api/cdn/download/' . $photo['id']),
                'file_name' => $photo['file_name']
            ];
        }
        
        return $urls;
    }
    
    /**
     * Get total loss by material type
     */
    public function getTotalLossByMaterialType($startDate = null, $endDate = null)
    {
        $builder = $this->select('inv_materials.material_type, 
                                  COUNT(*) as total_items,
                                  SUM(inv_damage_items.quantity) as total_quantity,
                                  SUM(inv_damage_items.total_cost) as total_loss_value')
                        ->join('inv_materials', 'inv_materials.id = inv_damage_items.material_id')
                        ->join('inv_damage_reports', 'inv_damage_reports.id = inv_damage_items.damage_report_id');
        
        if ($startDate) {
            $builder->where('inv_damage_reports.report_date >=', $startDate);
        }
        
        if ($endDate) {
            $builder->where('inv_damage_reports.report_date <=', $endDate);
        }
        
        return $builder->groupBy('inv_materials.material_type')->findAll();
    }
    
    /**
     * Get damage items by root cause
     */
    public function getByRootCause($rootCause)
    {
        return $this->select('inv_damage_items.*, inv_materials.material_name, inv_materials.material_code,
                             inv_damage_reports.report_number, inv_damage_reports.report_date')
                    ->join('inv_materials', 'inv_materials.id = inv_damage_items.material_id')
                    ->join('inv_damage_reports', 'inv_damage_reports.id = inv_damage_items.damage_report_id')
                    ->like('inv_damage_items.root_cause', $rootCause)
                    ->findAll();
    }
}
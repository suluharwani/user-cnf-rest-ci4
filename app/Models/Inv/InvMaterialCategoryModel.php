<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvMaterialCategoryModel extends Model
{
    protected $table = 'inv_material_categories';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'category_code', 'category_name', 'parent_id', 'description', 'is_active'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get categories with parent
     */
    public function getWithParent()
    {
        return $this->select('c1.*, c2.category_name as parent_name')
                    ->from('inv_material_categories c1')
                    ->join('inv_material_categories c2', 'c2.id = c1.parent_id', 'left')
                    ->where('c1.is_active', 1)
                    ->findAll();
    }
    
    /**
     * Get category tree
     */
    public function getTree()
    {
        $categories = $this->where('is_active', 1)->findAll();
        return $this->buildTree($categories);
    }
    
    private function buildTree($categories, $parentId = null)
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTree($categories, $category['id']);
                if (!empty($children)) {
                    $category['children'] = $children;
                }
                $tree[] = $category;
            }
        }
        
        return $tree;
    }
}
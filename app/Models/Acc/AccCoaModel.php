<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccCoaModel extends Model
{
    protected $table = 'acc_coa';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'account_code', 'account_name', 'account_type', 'account_group',
        'parent_id', 'is_header', 'normal_balance', 'is_active',
        'opening_balance', 'opening_balance_date', 'currency_id', 'description'
    ];
    
    protected $useTimestamps = true;
    
    public function getTree()
    {
        $accounts = $this->where('is_active', 1)->orderBy('account_code')->findAll();
        return $this->buildTree($accounts);
    }
    
    private function buildTree($accounts, $parentId = null)
    {
        $tree = [];
        foreach ($accounts as $account) {
            if ($account['parent_id'] == $parentId) {
                $children = $this->buildTree($accounts, $account['id']);
                if (!empty($children)) $account['children'] = $children;
                $tree[] = $account;
            }
        }
        return $tree;
    }
    
    public function getByGroup($group)
    {
        return $this->where('account_group', $group)->where('is_active', 1)->findAll();
    }
    
    public function getLeafAccounts()
    {
        return $this->where('is_header', 0)->where('is_active', 1)->findAll();
    }
    
    public function generateCode($parentId, $group)
    {
        $parent = $this->find($parentId);
        if (!$parent) return $group . '001';
        
        $lastChild = $this->where('parent_id', $parentId)
                         ->orderBy('account_code', 'DESC')
                         ->first();
        
        if ($lastChild) {
            $lastNum = intval(substr($lastChild['account_code'], -3));
            return substr($lastChild['account_code'], 0, -3) . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
        }
        
        return $parent['account_code'] . '001';
    }
}
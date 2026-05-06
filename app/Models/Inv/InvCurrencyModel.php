<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvCurrencyModel extends Model
{
    protected $table = 'inv_currencies';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'code', 'name', 'symbol', 'is_base', 'is_active'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get base currency
     */
    public function getBaseCurrency()
    {
        return $this->where('is_base', 1)->where('is_active', 1)->first();
    }
    
    /**
     * Get active currencies
     */
    public function getActiveCurrencies()
    {
        return $this->where('is_active', 1)->findAll();
    }
}
<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccCashBankModel extends Model
{
    protected $table = 'acc_cash_banks';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'account_code', 'account_name', 'coa_id', 'account_type',
        'bank_name', 'bank_branch', 'bank_account_number', 'bank_swift',
        'currency_id', 'opening_balance', 'current_balance', 'minimum_balance', 'is_active'
    ];
    
    protected $useTimestamps = true;
    
    public function getWithBalance($id = null)
    {
        $builder = $this->select('acc_cash_banks.*, inv_currencies.code as currency_code, acc_coa.account_code as coa_code');
        $builder->join('inv_currencies', 'inv_currencies.id = acc_cash_banks.currency_id');
        $builder->join('acc_coa', 'acc_coa.id = acc_cash_banks.coa_id');
        
        if ($id) return $builder->where('acc_cash_banks.id', $id)->first();
        return $builder->findAll();
    }
    
    public function updateBalance($id, $amount, $isCredit = false)
    {
        $operator = $isCredit ? '-' : '+';
        return $this->set('current_balance', "current_balance $operator $amount", false)
                    ->where('id', $id)
                    ->update();
    }
}
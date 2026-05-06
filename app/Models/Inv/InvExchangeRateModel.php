<?php

namespace App\Models\Inv;

use CodeIgniter\Model;

class InvExchangeRateModel extends Model
{
    protected $table = 'inv_exchange_rates';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'from_currency_id', 'to_currency_id', 'rate', 'effective_date', 'source', 'created_by'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get latest exchange rate
     */
    public function getLatestRate($fromCurrencyId, $toCurrencyId)
    {
        return $this->where('from_currency_id', $fromCurrencyId)
                    ->where('to_currency_id', $toCurrencyId)
                    ->orderBy('effective_date', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->first();
    }
    
    /**
     * Get all latest rates
     */
    public function getLatestRates()
    {
        $subquery = $this->builder()
                         ->selectMax('id')
                         ->groupBy('from_currency_id, to_currency_id')
                         ->getCompiledSelect();
        
        return $this->join("($subquery) as latest", 'latest.id = inv_exchange_rates.id', 'inner')
                    ->select('inv_exchange_rates.*, fc.code as from_code, tc.code as to_code')
                    ->join('inv_currencies as fc', 'fc.id = inv_exchange_rates.from_currency_id')
                    ->join('inv_currencies as tc', 'tc.id = inv_exchange_rates.to_currency_id')
                    ->findAll();
    }
    
    /**
     * Convert amount between currencies
     */
    public function convert($amount, $fromCurrencyId, $toCurrencyId)
    {
        if ($fromCurrencyId == $toCurrencyId) {
            return $amount;
        }
        
        $rate = $this->getLatestRate($fromCurrencyId, $toCurrencyId);
        
        if (!$rate) {
            // Try reverse
            $reverseRate = $this->getLatestRate($toCurrencyId, $fromCurrencyId);
            if ($reverseRate) {
                return $amount / $reverseRate['rate'];
            }
            return null;
        }
        
        return $amount * $rate['rate'];
    }
}
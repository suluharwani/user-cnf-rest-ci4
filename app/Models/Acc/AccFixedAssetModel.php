<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccFixedAssetModel extends Model
{
    protected $table = 'acc_fixed_assets';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'asset_code', 'asset_name', 'asset_category', 'coa_id',
        'accumulated_depreciation_coa_id', 'depreciation_expense_coa_id',
        'acquisition_date', 'acquisition_cost', 'currency_id',
        'residual_value', 'useful_life_months', 'depreciation_method',
        'depreciation_rate', 'monthly_depreciation', 'accumulated_depreciation',
        'last_depreciation_date', 'status', 'location', 'serial_number',
        'warranty_expiry', 'notes'
    ];
    
    protected $useTimestamps = true;
    
    public function calculateDepreciation($assetId = null)
    {
        $assets = $assetId ? [$this->find($assetId)] : $this->where('status', 'active')->findAll();
        $results = [];
        
        foreach ($assets as $asset) {
            if (!$asset || $asset['status'] !== 'active') continue;
            
            $monthlyDep = 0;
            $depreciableAmount = $asset['acquisition_cost'] - $asset['residual_value'];
            
            switch ($asset['depreciation_method']) {
                case 'straight_line':
                    $monthlyDep = $depreciableAmount / $asset['useful_life_months'];
                    break;
                case 'declining_balance':
                    $bookValue = $asset['acquisition_cost'] - $asset['accumulated_depreciation'];
                    $rate = $asset['depreciation_rate'] ? $asset['depreciation_rate'] / 100 : (200 / $asset['useful_life_months'] / 12 * 100);
                    $monthlyDep = $bookValue * ($rate / 100 / 12);
                    break;
            }
            
            $results[] = [
                'asset_id' => $asset['id'],
                'asset_name' => $asset['asset_name'],
                'monthly_depreciation' => round($monthlyDep, 2),
                'current_book_value' => $asset['acquisition_cost'] - $asset['accumulated_depreciation'],
                'accumulated_depreciation' => $asset['accumulated_depreciation']
            ];
        }
        
        return $results;
    }
    
    public function runDepreciation($assetId, $periodId, $userId)
    {
        $asset = $this->find($assetId);
        if (!$asset || $asset['status'] !== 'active') return false;
        
        $calc = $this->calculateDepreciation($assetId)[0];
        $monthlyDep = $calc['monthly_depreciation'];
        
        // Update asset
        $this->update($assetId, [
            'accumulated_depreciation' => $asset['accumulated_depreciation'] + $monthlyDep,
            'last_depreciation_date' => date('Y-m-d')
        ]);
        
        // Check if fully depreciated
        if ($asset['accumulated_depreciation'] + $monthlyDep >= $asset['acquisition_cost'] - $asset['residual_value']) {
            $this->update($assetId, ['status' => 'fully_depreciated']);
        }
        
        // Create journal entry
        $journalModel = new AccJournalModel();
        $journalId = $journalModel->insert([
            'journal_number' => $journalModel->generateNumber('general', date('Y-m-d')),
            'journal_date' => date('Y-m-d'),
            'period_id' => $periodId,
            'journal_type' => 'depreciation',
            'description' => "Monthly depreciation: {$asset['asset_name']}",
            'created_by' => $userId
        ]);
        
        $lineModel = new AccJournalLineModel();
        $lineModel->insertBatch([
            [
                'journal_id' => $journalId,
                'line_number' => 1,
                'account_id' => $asset['depreciation_expense_coa_id'],
                'debit' => $monthlyDep,
                'credit' => 0,
                'description' => "Depreciation expense - {$asset['asset_name']}"
            ],
            [
                'journal_id' => $journalId,
                'line_number' => 2,
                'account_id' => $asset['accumulated_depreciation_coa_id'],
                'debit' => 0,
                'credit' => $monthlyDep,
                'description' => "Accumulated depreciation - {$asset['asset_name']}"
            ]
        ]);
        
        $journalModel->post($journalId, $userId);
        
        return ['journal_id' => $journalId, 'depreciation_amount' => $monthlyDep];
    }
}
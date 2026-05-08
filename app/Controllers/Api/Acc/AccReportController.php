<?php

namespace App\Controllers\Api\Acc;

use App\Controllers\BaseController;
use App\Models\Acc\AccJournalLineModel;
use App\Models\Acc\AccCoaModel;
use App\Models\Acc\AccReceivableModel;
use App\Models\Acc\AccPayableModel;
use App\Models\Acc\AccFixedAssetModel;
use CodeIgniter\API\ResponseTrait;

class AccReportController extends BaseController
{
    use ResponseTrait;
    
    public function trialBalance() {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $lineModel = new AccJournalLineModel();
        $data = $lineModel->getTrialBalance($date);
        $totalDebit = array_sum(array_column($data, 'total_debit'));
        $totalCredit = array_sum(array_column($data, 'total_credit'));
        
        return $this->respond([
            'report_date' => $date,
            'accounts' => $data,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01
        ]);
    }
    
    public function incomeStatement() {
        $startDate = $this->request->getGet('start_date') ?? date('Y-01-01');
        $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');
        
        $lineModel = new AccJournalLineModel();
        $revenue = $this->getAccountTypeBalance('revenue', $endDate) + $this->getAccountTypeBalance('other_income', $endDate);
        $cogs = $this->getGroupBalance('cogs', $endDate);
        $expenses = $this->getAccountTypeBalance('expense', $endDate);
        
        return $this->respond([
            'period' => "$startDate - $endDate",
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $revenue - $cogs,
            'expenses' => $expenses,
            'net_income' => $revenue - $cogs - $expenses
        ]);
    }
    
    public function balanceSheet() {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $lineModel = new AccJournalLineModel();
        $coaModel = new AccCoaModel();
        
        $assetAccounts = $coaModel->whereIn('account_type', ['asset','contra_asset'])->where('is_header', 0)->findAll();
        $liabilityAccounts = $coaModel->whereIn('account_type', ['liability','contra_liability'])->where('is_header', 0)->findAll();
        $equityAccounts = $coaModel->where('account_type', 'equity')->where('is_header', 0)->findAll();
        
        $assets = $this->getBalances($assetAccounts, $date);
        $liabilities = $this->getBalances($liabilityAccounts, $date);
        $equity = $this->getBalances($equityAccounts, $date);
        
        $totalAssets = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity = array_sum(array_column($equity, 'balance'));
        
        return $this->respond([
            'report_date' => $date,
            'assets' => ['accounts' => $assets, 'total' => $totalAssets],
            'liabilities' => ['accounts' => $liabilities, 'total' => $totalLiabilities],
            'equity' => ['accounts' => $equity, 'total' => $totalEquity],
            'total_liabilities_equity' => $totalLiabilities + $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01
        ]);
    }
    
    public function arAging() {
        $model = new AccReceivableModel();
        return $this->respond(['data' => $model->getAgingReport()]);
    }
    
    public function apAging() {
        $model = new AccPayableModel();
        return $this->respond(['data' => $model->getAgingReport()]);
    }
    
    public function fixedAssetSchedule() {
        $model = new AccFixedAssetModel();
        $assets = $model->whereIn('status', ['active', 'fully_depreciated'])->findAll();
        $depreciation = $model->calculateDepreciation();
        return $this->respond(['assets' => $assets, 'depreciation_schedule' => $depreciation]);
    }
    
    public function cashFlow() {
        return $this->respond(['message' => 'Cash flow report - requires detailed implementation']);
    }
    
    private function getAccountTypeBalance($type, $date) {
        $coaModel = new AccCoaModel();
        $lineModel = new AccJournalLineModel();
        $accounts = $coaModel->where('account_type', $type)->where('is_header', 0)->findAll();
        $total = 0;
        
        foreach ($accounts as $account) {
            $balance = $lineModel->getAccountBalance($account['id'], $date);
            $total += ($balance['debit'] ?? 0) - ($balance['credit'] ?? 0);
        }
        
        return $total;
    }
    
    private function getGroupBalance($group, $date) {
        $coaModel = new AccCoaModel();
        $lineModel = new AccJournalLineModel();
        $accounts = $coaModel->where('account_group', $group)->where('is_header', 0)->findAll();
        $total = 0;
        
        foreach ($accounts as $account) {
            $balance = $lineModel->getAccountBalance($account['id'], $date);
            $total += ($balance['debit'] ?? 0) - ($balance['credit'] ?? 0);
        }
        
        return $total;
    }
    
    private function getBalances($accounts, $date) {
        $lineModel = new AccJournalLineModel();
        $result = [];
        
        foreach ($accounts as $account) {
            $balance = $lineModel->getAccountBalance($account['id'], $date);
            $netBalance = ($balance['debit'] ?? 0) - ($balance['credit'] ?? 0);
            
            if ($netBalance != 0) {
                $result[] = [
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'balance' => $netBalance
                ];
            }
        }
        
        return $result;
    }
}
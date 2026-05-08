<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccPayableModel extends Model
{
    protected $table = 'acc_payables';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'bill_number', 'supplier_id', 'supplier_name', 'bill_date', 'due_date',
        'currency_id', 'exchange_rate', 'total_amount', 'tax_amount',
        'discount_amount', 'paid_amount', 'status', 'journal_id',
        'po_id', 'gr_id', 'coa_id', 'notes', 'created_by'
    ];
    
    protected $useTimestamps = true;
    
    public function generateBillNumber()
    {
        $ym = date('Ym');
        $count = $this->where('bill_number LIKE', "BILL-$ym%")->countAllResults() + 1;
        return sprintf('BILL-%s-%05d', $ym, $count);
    }
    
    public function getAgingReport()
    {
        $bills = $this->whereIn('status', ['open', 'partially_paid'])->findAll();
        
        $aging = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0];
        $today = date('Y-m-d');
        
        foreach ($bills as $bill) {
            $daysOverdue = (strtotime($today) - strtotime($bill['due_date'])) / 86400;
            $amount = $bill['total_amount'] - $bill['paid_amount'];
            
            if ($daysOverdue <= 0) $aging['current'] += $amount;
            elseif ($daysOverdue <= 30) $aging['1_30'] += $amount;
            elseif ($daysOverdue <= 60) $aging['31_60'] += $amount;
            elseif ($daysOverdue <= 90) $aging['61_90'] += $amount;
            else $aging['over_90'] += $amount;
        }
        
        return $aging;
    }
    
    public function addPayment($id, $amount)
    {
        $this->set('paid_amount', "paid_amount + $amount", false)->where('id', $id)->update();
        $bill = $this->find($id);
        
        $newStatus = 'open';
        if ($bill['paid_amount'] + $amount >= $bill['total_amount']) {
            $newStatus = 'paid';
        } elseif ($bill['paid_amount'] + $amount > 0) {
            $newStatus = 'partially_paid';
        }
        
        $this->update($id, ['status' => $newStatus]);
    }
}
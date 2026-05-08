<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccReceivableModel extends Model
{
    protected $table = 'acc_receivables';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'invoice_number', 'customer_name', 'customer_type', 'customer_tax_id',
        'customer_address', 'customer_phone', 'customer_email',
        'invoice_date', 'due_date', 'currency_id', 'exchange_rate',
        'total_amount', 'tax_amount', 'discount_amount', 'paid_amount',
        'status', 'journal_id', 'coa_id', 'notes', 'created_by'
    ];
    
    protected $useTimestamps = true;
    
    public function generateInvoiceNumber()
    {
        $ym = date('Ym');
        $count = $this->where('invoice_number LIKE', "INV-$ym%")->countAllResults() + 1;
        return sprintf('INV-%s-%05d', $ym, $count);
    }
    
    public function getAgingReport()
    {
        $invoices = $this->whereIn('status', ['open', 'partially_paid'])->findAll();
        
        $aging = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0];
        $today = date('Y-m-d');
        
        foreach ($invoices as $inv) {
            $daysOverdue = (strtotime($today) - strtotime($inv['due_date'])) / 86400;
            $amount = $inv['total_amount'] - $inv['paid_amount'];
            
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
        $invoice = $this->find($id);
        
        $newStatus = 'open';
        if ($invoice['paid_amount'] + $amount >= $invoice['total_amount']) {
            $newStatus = 'paid';
        } elseif ($invoice['paid_amount'] + $amount > 0) {
            $newStatus = 'partially_paid';
        }
        
        $this->update($id, ['status' => $newStatus]);
    }
}
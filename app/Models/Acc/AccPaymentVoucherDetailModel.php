<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccPaymentVoucherDetailModel extends Model
{
    protected $table = 'acc_payment_voucher_details';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'voucher_id', 'payable_id', 'invoice_number', 'amount', 'discount_taken', 'description'
    ];
    
    /**
     * Get details by voucher ID
     */
    public function getByVoucher($voucherId)
    {
        return $this->select('acc_payment_voucher_details.*, 
                             acc_payables.bill_number, acc_payables.supplier_name,
                             acc_payables.total_amount as bill_total, acc_payables.paid_amount as bill_paid')
                    ->join('acc_payables', 'acc_payables.id = acc_payment_voucher_details.payable_id', 'left')
                    ->where('acc_payment_voucher_details.voucher_id', $voucherId)
                    ->findAll();
    }
}
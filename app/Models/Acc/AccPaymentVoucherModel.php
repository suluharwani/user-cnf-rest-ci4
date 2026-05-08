<?php

namespace App\Models\Acc;

use CodeIgniter\Model;

class AccPaymentVoucherModel extends Model
{
    protected $table = 'acc_payment_vouchers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'voucher_number', 'voucher_date', 'payment_type', 'cash_bank_id',
        'currency_id', 'exchange_rate', 'total_amount', 'payee',
        'check_number', 'check_date', 'bank_reference', 'journal_id',
        'status', 'approved_by', 'paid_by', 'paid_at', 'notes', 'created_by'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Generate voucher number
     */
    public function generateNumber()
    {
        $ym = date('Ym');
        $count = $this->where('voucher_number LIKE', "PV-$ym%")->countAllResults() + 1;
        return sprintf('PV-%s-%05d', $ym, $count);
    }
    
    /**
     * Get voucher with details
     */
    public function getWithDetails($id = null)
    {
        $builder = $this->select('acc_payment_vouchers.*, 
                                  inv_currencies.code as currency_code,
                                  acc_cash_banks.account_name as bank_name,
                                  acc_cash_banks.bank_account_number,
                                  creator.fullname as created_by_name,
                                  approver.fullname as approved_by_name')
                        ->join('inv_currencies', 'inv_currencies.id = acc_payment_vouchers.currency_id')
                        ->join('acc_cash_banks', 'acc_cash_banks.id = acc_payment_vouchers.cash_bank_id', 'left')
                        ->join('users as creator', 'creator.id = acc_payment_vouchers.created_by', 'left')
                        ->join('users as approver', 'approver.id = acc_payment_vouchers.approved_by', 'left');
        
        if ($id) {
            $voucher = $builder->where('acc_payment_vouchers.id', $id)->first();
            if ($voucher) {
                $detailModel = new AccPaymentVoucherDetailModel();
                $voucher['details'] = $detailModel->getByVoucher($id);
            }
            return $voucher;
        }
        
        return $builder->orderBy('voucher_date', 'DESC')->findAll();
    }
    
    /**
     * Approve voucher
     */
    public function approve($id, $userId)
    {
        return $this->update($id, [
            'status' => 'approved',
            'approved_by' => $userId
        ]);
    }
    
    /**
     * Mark as paid
     */
    public function markAsPaid($id, $userId)
    {
        return $this->update($id, [
            'status' => 'paid',
            'paid_by' => $userId,
            'paid_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get pending approvals
     */
    public function getPendingApprovals()
    {
        return $this->where('status', 'draft')
                    ->orderBy('voucher_date', 'ASC')
                    ->findAll();
    }
}
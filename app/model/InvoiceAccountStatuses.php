<?php
namespace model;
use Illuminate\Database\Eloquent\Model;

class InvoiceAccountStatuses extends Model{
    public function __construct(){
        $this->table = 'accounting_invoices_statuses';
        $this->timestamps = false;
    }
}
<?php
namespace model;
use Illuminate\Database\Eloquent\Model;

class InvoiceAccountConcepts extends Model{
    public function __construct(){
        $this->timestamps = false;
        $this->table = 'accounting_invoices_concepts';
    }
}
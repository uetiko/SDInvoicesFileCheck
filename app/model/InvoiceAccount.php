<?php
/**
 * Created by PhpStorm.
 * User: amk-011
 * Date: 21/12/15
 * Time: 01:54 PM
 */

namespace model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InvoiceAccount extends Model{
    /** @var Model $model */
    private $model = null;
    public function __construct(){
        $this->timestamps = false;
        $this->table = 'accounting_invoices';
    }
    public function addModel(Model $model){
        $this->model = $model;
    }

    public function QB(){
    }
}
<?php
namespace model;
use Illuminate\Database\Eloquent\Model;

class Client extends Model{
    public function __construct(){
        $this->timestamps = false;
        $this->table = 'clients';
    }
}
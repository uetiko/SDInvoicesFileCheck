<?php
namespace dto;
/**
 * Class Invoice
 * @package dto
 */
class Invoice implements \Serializable{
    /** @var string $month */
    private $month = null;
    /** @var string $uuid */
    private $uuid = null;
    /** @var string $clientName */
    private $clientName = null;
    /** @var string $rfc */
    private $rfc = null;
    /** @var float $total */
    private $total = null;
    /** @var float $subtotal */
    private $subtotal = null;
    /** @var float $iva */
    private $iva = null;
    /** @var array $array */
    private $array = null;
    /** @var \DateTime $updateTime */
    private $updateTime = null;
    /** @var \DateTime $periodEnd */
    private $periodEnd = null;
    /** @var array $header */
    private $header = null;

    /**
     * @return string
     */
    public function getMonth(){
        $this->setMouth();
        return $this->month;
    }

    /**
     * @param string $month
     */
    public function setMouth(){
        $this->month = $this->periodEnd->format('m');
    }

    /**
     * @return string
     */
    public function getUuid(){
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid($uuid){
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getClientName(){
        return $this->clientName;
    }

    /**
     * @param string $clientName
     */
    public function setClientName($clientName){
        $this->clientName = $clientName;
    }

    /**
     * @return string
     */
    public function getRfc(){
        return $this->rfc;
    }

    /**
     * @param string $rfc
     */
    public function setRfc($rfc){
        $this->rfc = $rfc;
    }

    /**
     * @return float
     */
    public function getTotal(){
        return $this->total;
    }

    /**
     * @param float $total
     */
    public function setTotal($total){
        $this->total = $total;
    }

    /**
     * @return float
     */
    public function getSubtotal(){
        return $this->subtotal;
    }

    /**
     * @param float $subtotal
     */
    public function setSubtotal($subtotal){
        $this->subtotal = $subtotal;
    }

    /**
     * @return float
     */
    public function getIva(){
        return $this->iva;
    }

    /**
     * @param float $iva
     */
    public function setIva($iva){
        $this->iva = $iva;
    }

    /**
     * Regresa el contenido del DTO en un hashmap
     * @return array
     */
    public function toArray(){
        $this->buildArray();
        return $this->array;
    }

    /**
     * Regresa el contenido del DTO en json
     * @return string
     */
    public function toJSONString(){
        $this->buildArray();
        return json_encode($this->array);
    }

    /**
     * @param $updateTime
     */
    public function setUpdateTime($updateTime){
        $this->updateTime = new \DateTime($updateTime, new \DateTimeZone('America/Mexico_City'));
    }

    /**
     * @param $periodEnd
     */
    public function setPeriodEnd($periodEnd){
        $this->periodEnd = new \DateTime($periodEnd, new \DateTimeZone('America/Mexico_City'));
    }

    public function getPeriodEnd($format){
        return $this->periodEnd->format($format);
    }

    /**
     * Building an array
     */
    private function buildArray(){
        $this->array = [
            $this->getMonth(),
            $this->periodEnd->format('Y'),
            $this->getUuid(),
            $this->getClientName(),
            $this->getRfc(),
            $this->getTotal(),
            $this->getSubtotal(),
            $this->getIva()
        ];
    }

    public function getArrayHeader(){
        return [
            'Month',
            'Year',
            'uuid',
            'Client',
            'rfc',
            'total',
            'subtotal',
            'iva'
        ];
    }

    public function getPartOfNameFile(){
        return "{$this->uuid}";
    }

    /**
     * @return string
     */
    public function serialize(){
        return serialize($this);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized){
        $values = unserialize($serialized);
        foreach ($values as $key => $value) {
            $this->$key = $value;
        }
    }
}
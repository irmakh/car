<?php
/**
 * Created by PhpStorm.
 * User: madcoe
 * Date: 16.10.2015
 * Time: 13:50
 */


class car{

    protected $askedPrice,
        $partialRefundRatio = 0.55,
        $taxRatio,
        $roadTaxPaid,
        $monthsLeft,
        $OMW,
        $COE,
        $regDate,
        $OMWExpireDate,
        $salePrice,
        $shippingTaxRatio = 0.20,
        $shippingCost,
        $minMatchRatio = 0.70,
        $containerPrice,
        $debug = false,
        $totalCost,
        $omvReturn,
        $roadTaxReturn,
        $log;




    /**
     * @return mixed
     */
    public function getRegDate()
    {
        return $this->regDate;
    }

    /**
     * @param mixed $regDate
     */
    public function setRegDate($regDate)
    {
        $this->regDate = $regDate;
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @param boolean $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @return mixed
     */
    public function getCOE()
    {

        return $this->COE;

    }

    /**
     * @param mixed $COE
     */
    public function setCOE($COE)
    {
        $this->COE = $COE;
    }

    /**
     * @return float
     */
    public function getShippingTaxRatio()
    {
        return $this->shippingTaxRatio;
    }

    /**
     * @param float $shippingTaxRatio
     */
    public function setShippingTaxRatio($shippingTaxRatio)
    {
        $this->shippingTaxRatio = $shippingTaxRatio;
    }

    /**
     * @return mixed
     */
    public function getContainerPrice()
    {
        return $this->containerPrice;
    }

    /**
     * @param mixed $containerPrice
     */
    public function setContainerPrice($containerPrice)
    {
        $this->containerPrice = $containerPrice;
    }

    function println($msg,$type=false){
        if($type){
            $this->log[$type] = $msg;
        } else {
            echo PHP_EOL.$msg.PHP_EOL;
        }

    }

    /**
     * @return mixed
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param mixed $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    function calCOEReturn(){
        $ret = $this->getCOE()/120*$this->getMonthsLeft();
        if($this->isDebug())$this->println("COE RETURN: ".$ret,1);
       return $ret;
    }

    function calBuyCost(){
        $ret =  $this->getAskedPrice()-$this->calRoadTaxReturn()-$this->calOMWReturn()-$this->calCOEReturn();
        if($this->isDebug())$this->println("BUY COST: ".$ret,2);
        return $ret;
    }

    function calShippingCost(){
        $ret =  $this->getContainerPrice()+$this->calShippingTax();
        $this->setShippingCost($ret);
        if($this->isDebug())$this->println("SHIPPING COST: ".$ret,3);
        return $ret;
    }


    function calShippingTax(){
        $ret =  ($this->calBuyCost()+$this->getContainerPrice())*$this->getShippingTaxRatio();
        if($this->isDebug())$this->println("SHIPPING TAX: ".$ret,4);
        return $ret;
    }


    function calRoadTaxReturn(){
        $ret =  $this->getRoadTaxPaid()/12*$this->getMonthsLeft();
        if($ret < 0 )$ret = 0;
        $this->setRoadTaxReturn($ret);
        if($this->isDebug())$this->println("ROAD TAX RETURN: ".$ret,5);
        return $ret;
    }

    function calOMWReturn(){
            $ret =  $this->getOMW()*$this->getPartialRefundRatio();
        $this->setOmvReturn($ret);
        if($this->isDebug())$this->println("OMW RETURN: ".$ret,6);
        return $ret;
    }

    function calTotalCost(){
        $ret =  $this->calShippingCost()+$this->calBuyCost();
        $this->setTotalCost($ret);
        if($this->isDebug())$this->println("TOTAL COST: ".$ret,7);
        return $ret;
    }

    function calTotalEarn(){
        $ret= $this->getSalePrice()-$this->calTotalCost();
      //  if($this->isDebug())echo $this->println("TOTAL EARN: ".$ret,8);
        return $ret;
    }

    function resultPercent(){
        $ret =   ($this->calTotalEarn()/$this->calTotalCost())*100;
       // if($this->isDebug())$this->println("PERCENT GAIN: %".$ret,9);
        return $ret;
    }

    /**
     * @return mixed
     */
    public function getAskedPrice()
    {
        return $this->askedPrice;
    }

    /**
     * @param mixed $askedPrice
     */
    public function setAskedPrice($askedPrice)
    {
        $this->askedPrice = $askedPrice;
    }

    /**
     * @return int
     */
    public function getPartialRefundRatio()
    {
        return $this->partialRefundRatio;
    }

    /**
     * @param int $partialRefundRatio
     */
    public function setPartialRefundRatio($partialRefundRatio)
    {
        $this->partialRefundRatio = $partialRefundRatio;
    }

    /**
     * @return mixed
     */
    public function getTaxRatio()
    {
        return $this->taxRatio;
    }

    /**
     * @param mixed $taxRatio
     */
    public function setTaxRatio($taxRatio)
    {
        $this->taxRatio = $taxRatio;
    }

    /**
     * @return mixed
     */
    public function getRoadTaxPaid()
    {
        return $this->roadTaxPaid;
    }

    /**
     * @param mixed $roadTaxPaid
     */
    public function setRoadTaxPaid($roadTaxPaid)
    {
        $this->roadTaxPaid = $roadTaxPaid;
    }

    /**
     * @return mixed
     */
    public function getMonthsLeft()
    {

        $this->setMonthsLeft(($this->getOMWExpireDate()-time())/60/60/24/7/4);



        return $this->monthsLeft;
    }

    /**
     * @param mixed $monthsLeft
     */
    public function setMonthsLeft($monthsLeft)
    {
        $this->monthsLeft = $monthsLeft;
    }

    /**
     * @return mixed
     */
    public function getOMW()
    {
        return $this->OMW;
    }

    /**
     * @param mixed $OMW
     */
    public function setOMW($OMW)
    {
        $this->OMW = $OMW;
    }

    /**
     * @return mixed
     */
    public function getOMWExpireDate()
    {
        return strtotime($this->getRegDate())+(10*60*60*24*30*12);
    }

    /**
     * @param mixed $OMWExpireDate
     */
    public function setOMWExpireDate($OMWExpireDate)
    {
        $this->OMWExpireDate = $OMWExpireDate;
    }

    /**
     * @return mixed
     */
    public function getSalePrice()
    {
        return $this->salePrice;
    }

    /**
     * @param mixed $salePrice
     */
    public function setSalePrice($salePrice)
    {
        $this->salePrice = $salePrice;
    }

    /**
     * @return int
     */
    public function getSaleTaxRatio()
    {
        return $this->saleTaxRatio;
    }

    /**
     * @param int $saleTaxRatio
     */
    public function setSaleTaxRatio($saleTaxRatio)
    {
        $this->saleTaxRatio = $saleTaxRatio;
    }

    /**
     * @return mixed
     */
    public function getShippingCost()
    {
        return $this->shippingCost;
    }

    /**
     * @param mixed $shippingCost
     */
    public function setShippingCost($shippingCost)
    {
        $this->shippingCost = $shippingCost;
    }

    /**
     * @return int
     */
    public function getMinMatchRatio()
    {
        return $this->minMatchRatio;
    }

    /**
     * @param int $minMatchRatio
     */
    public function setMinMatchRatio($minMatchRatio)
    {
        $this->minMatchRatio = $minMatchRatio;
    }

    /**
     * @return mixed
     */
    public function getTotalCost()
    {
        return $this->totalCost;
    }

    /**
     * @param mixed $totalCost
     */
    public function setTotalCost($totalCost)
    {
        $this->totalCost = $totalCost;
    }

    /**
     * @return mixed
     */
    public function getOmvReturn()
    {
        return $this->omvReturn;
    }

    /**
     * @param mixed $omvReturn
     */
    public function setOmvReturn($omvReturn)
    {
        $this->omvReturn = $omvReturn;
    }

    /**
     * @return mixed
     */
    public function getRoadTaxReturn()
    {
        return $this->roadTaxReturn;
    }

    /**
     * @param mixed $roadTaxReturn
     */
    public function setRoadTaxReturn($roadTaxReturn)
    {
        $this->roadTaxReturn = $roadTaxReturn;
    }


}
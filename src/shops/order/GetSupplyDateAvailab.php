<?php

namespace Ecomws\Order;

use Ecomws\Order\Base;

class GetSupplyDateAvailab extends Base
{
    /**
     * The string of endPoint.
     *
     * @var string
     */
    protected $endPoint = 'GetSupplyDateAvailab';

    /**
     * The dateTime of supplyDate.
     *
     * @var dateTime
     */
    protected $supplyDate;


    public function setSupplyDate($supplyDate)
    {
        $this->supplyDate = $supplyDate;

        return $this;
    }

    /**
     * The int of supplyArea.
     *
     * @var int
     */
    protected $supplyArea;


    public function setSupplyArea($supplyArea)
    {
        $this->supplyArea = $supplyArea;

        return $this;
    }

    /**
     * The int of supplyFromHour.
     *
     * @var int
     */
    protected $supplyFromHour;


    public function setSupplyFromHour($supplyFromHour)
    {
        $this->supplyFromHour = $supplyFromHour;

        return $this;
    }

    /**
     * The int of supplyUntilHour.
     *
     * @var int
     */
    protected $supplyUntilHour;


    public function setSupplyUntilHour($supplyUntilHour)
    {
        $this->supplyUntilHour = $supplyUntilHour;

        return $this;
    }

    public function toArray()
    {
        return [
            'Token' => Self::$token,
            'SupplyDate' => $this->supplyDate,
            'SupplyArea' => $this->supplyArea,
            'SupplyFromHour' => $this->supplyFromHour,
            'SupplyUntilHour' => $this->supplyUntilHour
        ];
    }

}

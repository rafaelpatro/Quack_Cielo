<?php
class Quack_Cielo_Model_Payment_Dc extends Quack_Cielo_Model_Payment
{
    protected $_code = 'qck_cielo_dc';
    
    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Cc::assignData()
     */
    public function assignData($data)
    {
        Mage::log('Quack_Cielo_Model_Payment_Dc::assignData');
        parent::assignData($data);
        $this->getInfoInstance()->setAdditionalInformation('cc_installments', 1);
        return $this;
    }
}
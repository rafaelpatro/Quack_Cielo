<?php
class Quack_Cielo_Model_Payment_Cc extends Quack_Cielo_Model_Payment
{
    protected $_code = 'qck_cielo_cc';
    protected $_formBlockType = 'qck_cielo/form_cc';
    protected $_infoBlockType = 'qck_cielo/info_cc';

    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Cc::assignData()
     */
    public function assignData($data)
    {
        Mage::log('Quack_Cielo_Model_Payment_Cc::assignData');
        parent::assignData($data);
        $this->getInfoInstance()
            ->setAdditionalInformation('cc_installments', 1)
            ->setAdditionalInformation('cc_taxvat', $data->getCcTaxvat())
            ->setAdditionalInformation('cc_bin', substr($data->getCcNumber(), 0, 6))
            ->setAdditionalInformation('cc_phone', $data->getCcPhone());
        
        if (is_numeric($data->getCcInstallments()) && (int) $data->getCcInstallments() > 0) {
            $this->getInfoInstance()->setAdditionalInformation('cc_installments', $data->getCcInstallments());
        }
        
        if ($this->getConfigData('product') == Quack_Cielo_Model_Source_Product::CREDIT) {
            $this->getInfoInstance()->setAdditionalInformation('cc_installments', 1);
        }
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Cc::validate()
     */
    public function validate()
    {
        Mage::log('Quack_Cielo_Model_Payment_Cc::validate');
        parent::validate();
        $this->validateCcTaxvat();
        $this->validateCcInstallments();
        return $this;
    }
    
}
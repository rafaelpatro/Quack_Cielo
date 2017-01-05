<?php
class Quack_Cielo_Block_Info_Cc extends Mage_Payment_Block_Info_Cc
{
    public function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data = array();
        if ($this->getInfo()->getAdditionalInformation('cc_installments')) {
            $data[Mage::helper('qck_cielo')->__('Installments')] = $this->getInfo()->getAdditionalInformation('cc_installments');
        }
        if (null !== $this->getInfo()->getCcStatus()) {
            $data[Mage::helper('qck_cielo')->__('Status')] = Mage::helper('qck_cielo')->__('STATUS ' . $this->getInfo()->getCcStatus());
        }
        $transport->setData(array_merge($transport->getData(), $data));
        return $transport;
    }
}
<?php
class Quack_Cielo_Block_Form_Cc extends Mage_Payment_Block_Form_Cc
{
    /**
     * (non-PHPdoc)
     * @see Mage_Core_Block_Abstract::_afterToHtml()
     */
    protected function _afterToHtml($html)
    {
        $block = $this->getLayout()->createBlock('core/template');
        $product = $this->getMethod()->getConfigData('product');
        $block->setTemplate('qck_cielo/form/cc.phtml');
        $block->setData('installments', $this->getInstallments())
            ->setData('cc_owner', $this->getInfoData('cc_owner'))
            ->setData('cc_taxvat', $this->getInfoData('cc_taxvat'))
            ->setData('cc_phone', $this->getInfoData('cc_phone'))
            ->setData('cc_installments', $this->getInfoData('cc_installments'));
        return parent::_afterToHtml($html) . $block->toHtml();
    }
    
    /**
     * @return array[]
     */
    public function getInstallments()
    {
        $output = array();
        $product = $this->getMethod()->getConfigData('product');
        if ($product != Quack_Cielo_Model_Source_Product::CREDIT) {
            $maxQty    = $this->getMethod()->getConfigData('installments_qty_max');
            $minAmount = $this->getMethod()->getConfigData('installments_amount_min');
            if (is_numeric($maxQty) && $maxQty > 1) {
                for ($i=1; $i<=$maxQty; $i++) {
                    $total = $this->getMethod()->getInfoInstance()->getQuote()->getBaseGrandTotal() / $i;
                    if ($total >= $minAmount) {
                        $total = Mage::helper('core')->currency($total, true, false);
                        $label = Mage::helper('qck_cielo')->__("%d x %s (without interest)", $i, $total);
                        $output[$i]['value'] = $i;
                        $output[$i]['label'] = $label;
                    }
                }
            }
        }
        return $output;
    }
}
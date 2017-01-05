<?php
class Quack_Cielo_Block_Onepage_Success extends Mage_Core_Block_Template
{

    /**
     * (non-PHPdoc)
     * @see Mage_Core_Block_Abstract::_beforeToHtml()
     */
    protected function _beforeToHtml()
    {
        $isMethodCielo = false;
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if ($orderId) {
            /* @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->load($orderId);
            $isMethodCielo = ($order->getPayment() && in_array($order->getPayment()->getMethod(), array('qck_cielo_cc', 'qck_cielo_dc')));
            if ($isMethodCielo) {
                $order->getPayment()->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_UPDATE, true);
                if ($order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING) {
                    $status = $order->getPayment()->getMethodInstance()->getConfigData('order_status');
                    if (!empty($status)) {
                        $order->setStatus($status);
                    }
                }
                $order->save();
                
                $data = $order->getPayment()->getTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS);
                $statusLabel = $data['Situação'];
                if ($statusLabel == Mage::helper('qck_cielo')->__('STATUS 5')
                    || $statusLabel == Mage::helper('qck_cielo')->__('STATUS 99')) {
                    $this->setData('status_message', "{$statusLabel}: {$data['LR']}");
                } elseif ($statusLabel == Mage::helper('qck_cielo')->__('STATUS 9')
                    || $statusLabel == Mage::helper('qck_cielo')->__('STATUS 12')) {
                    $this->setData('status_message', "{$statusLabel}: {$data['Cancelamento 1']}");
                }
                $this->setData('is_valid_status', in_array($statusLabel, $this->_getValidStatusLabels()));
                $this->setData('reorder_url', $this->getUrl('sales/order/reorder/', array('order_id' => $orderId)));
            }
        }
        $this->setData('is_method_cielo', $isMethodCielo);
        return parent::_beforeToHtml();
    }
    
    /**
     * @return array
     */
    private function _getValidStatusLabels()
    {
        return array(
            Mage::helper('qck_cielo')->__('STATUS 0'),
            Mage::helper('qck_cielo')->__('STATUS 1'),
            Mage::helper('qck_cielo')->__('STATUS 2'),
            Mage::helper('qck_cielo')->__('STATUS 3'),
            Mage::helper('qck_cielo')->__('STATUS 4'),
            Mage::helper('qck_cielo')->__('STATUS 6'),
            Mage::helper('qck_cielo')->__('STATUS 10')
        );
    }
}

<?php
class Quack_Cielo_Model_Payment extends Quack_Cielo_Model_Abstract
{
    protected $_isGateway                   = true;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canCaptureOnce              = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canReviewPayment            = true;
    protected $_canFetchTransactionInfo     = true;
    protected $_canUseInternal              = true;
    protected $_canVoid                     = true;
    
    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Abstract::canVoid()
     */
    public function canVoid(Varien_Object $payment)
    {
        return ($payment->getCcStatus() == Transacao::STATUS_AUTORIZADA);
    }
    
    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Abstract::order()
     */
    public function order(Varien_Object $payment, $amount)
    {
        Mage::log('Quack_Cielo_Model_Payment::order');
        $this->loadApi($payment, $amount);
        $this->getApi()->doTransacao(false, false);
        $transacao = $this->getApi()->getTransacao();
        
        if (!$this->isTransactionSuccess()) {
            Mage::throwException(
                $this->getHelper()->getErrorMessage($transacao));
        }
        
        Mage::register("{$this->_code}_url_autenticacao", (string) $transacao->getUrlAutenticacao());
        $payment->setTransactionId($transacao->getTid())
            ->setIsTransactionPending(true)
            ->setIsTransactionClosed(true)
            ->setCcStatus($transacao->getStatus())
            ->setCcTransId($transacao->getTid())
            ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->getLastResponse());
        
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Abstract::authorize()
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        Mage::log('Quack_Cielo_Model_Payment::authorize');
        $this->loadApi($payment, $amount);
        $this->getApi()->doTransacao(false, false);
        $transacao = $this->getApi()->getTransacao();
        
        if (!$this->isTransactionSuccess()) {
            Mage::throwException($this->getHelper()->getErrorMessage($transacao));
        }
        
        Mage::register("{$this->_code}_url_autenticacao", (string) $transacao->getUrlAutenticacao());
        $payment->setTransactionId($transacao->getTid())
            ->setIsTransactionPending(!$this->isAuthorizationSuccess())
            ->setIsTransactionClosed(false)
            ->setCcStatus($transacao->getStatus())
            ->setCcTransId($transacao->getTid())
            ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->getLastResponse());
        
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Abstract::capture()
     */
    public function capture(Varien_Object $payment, $amount)
    {
        Mage::log('Quack_Cielo_Model_Payment::capture');
        $this->loadApi($payment, $amount);
        $transacao = $this->getApi()->getTransacao();
        if (!$payment->getCcTransId()) {
            // Frontend operation. Action Payment is Authorize and Capture
            $this->getApi()->doTransacao(false, false);
            $transacao = $this->getApi()->getTransacao();
            
            if (!$this->isTransactionSuccess()) {
                Mage::throwException($this->getHelper()->getErrorMessage($transacao));
            }
            
            Mage::register("{$this->_code}_url_autenticacao", (string) $transacao->getUrlAutenticacao());
            $payment->setIsTransactionPending(!$this->isAuthorizationSuccess())
                ->setCcTransId($transacao->getTid())
                ->setTransactionId($transacao->getTid())
                ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->getLastResponse());
        } else {
            // Backend Invoice Operation
            $this->getApi()->doCaptura();
            $transacao = $this->getApi()->getTransacao();
            
            if (!$this->isCaptureSuccess() || !$this->isAuthorizationSuccess()) {
                Mage::throwException($this->getHelper()->getErrorMessage($transacao));
            }
            
            $payment->setParentTransactionId($transacao->getTid())
                ->setTransactionId($transacao->getTid().'-'.time())
                ->setShouldCloseParentTransaction(true)
                ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->getLastResponse());
        }
        
        $payment->setIsTransactionClosed(true)
            ->setCcStatus($transacao->getStatus());
        
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Abstract::refund()
     */
    public function refund(Varien_Object $payment, $amount)
    {
        Mage::log('Quack_Cielo_Model_Payment::refund');
        $this->loadApi($payment, $amount);
        $this->getApi()->doCancela();
        $transacao = $this->getApi()->getTransacao();
        
        if (!($this->isCancelationSuccess() || $this->isCaptureSuccess())) {
            Mage::throwException($this->getHelper()->getErrorMessage($transacao));
        }
        
        $payment->setIsTransactionClosed(true)
            ->setParentTransactionId($transacao->getTid())
            ->setTransactionId($transacao->getTid().'-'.time())
            ->setCcStatus($transacao->getStatus())
            ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->getLastResponse());
        
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Abstract::void()
     */
    public function void(Varien_Object $payment)
    {
        Mage::log('Quack_Cielo_Model_Payment::void');
        $this->loadApi($payment);
        $this->getApi()->doCancela();
        $transacao = $this->getApi()->getTransacao();
        
        if (!$this->isCancelationSuccess()) {
            Mage::throwException($this->getHelper()->getErrorMessage($transacao));
        }
        
        $payment->setIsTransactionClosed(true)
            ->setParentTransactionId($transacao->getTid())
            ->setTransactionId($transacao->getTid().'-'.time())
            ->setCcStatus($transacao->getStatus())
            ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->getLastResponse());
        
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Abstract::acceptPayment()
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        /* @var $payment Mage_Sales_Model_Order_Payment */
        Mage::log('Quack_Cielo_Model_Payment::acceptPayment');
        if (empty($payment->getCcTransId())) {
            Mage::throwException("Não foi possível efetuar a transação. Identificador TID não encontrado.");
        }
        
        $this->loadApi($payment);
        $this->getApi()->doConsulta();
        
        if (!$this->isAuthorizationSuccess()) {
            $this->getApi()->doAutorizacao();
            $payment->setCcStatus($this->getApi()->getTransacao()->getStatus())
                ->setParentTransactionId($this->getApi()->getTransacao()->getTid())
                ->setTransactionId($this->getApi()->getTransacao()->getTid().'-'.time())
                ->setIsTransactionClosed(true)
                ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->getLastResponse());
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        }
        
        if (!$this->isAuthorizationSuccess()) {
            Mage::throwException($this->getHelper()->getErrorMessage($this->getApi()->getTransacao()));
        }
        
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Abstract::denyPayment()
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        Mage::log('Quack_Cielo_Model_Payment::denyPayment');
        if (empty($payment->getCcTransId())) {
            Mage::throwException("Não foi possível efetuar a transação. Identificador TID não encontrado.");
        }

        $this->loadApi($payment);
        $this->getApi()->doConsulta();
        $failed = $this->isAuthorizationSuccess();
        
        if ($failed) {
            $this->getApi()->doCancela();
            $payment->setCcStatus($this->getApi()->getTransacao()->getStatus())
                ->setParentTransactionId($this->getApi()->getTransacao()->getTid())
                ->setTransactionId($this->getApi()->getTransacao()->getTid().'-'.time())
                ->setIsTransactionClosed(true)
                ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->getLastResponse());
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID);
            $failed = !$this->isCancelationSuccess();
        }
        
        if ($failed) {
            Mage::throwException($this->getHelper()->getErrorMessage($this->getApi()->getTransacao()));
        }

        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Abstract::fetchTransactionInfo()
     */
    public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId)
    {
        Mage::log('Quack_Cielo_Model_Payment::fetchTransactionInfo');
        if ($payment->getCcTransId() != $transactionId) {
            Mage::throwException("Acesse o registro pai {$payment->getCcTransId()}, para atualizar as informações");
        }
        
        $this->loadApi($payment);
        $this->getApi()->doConsulta();
        $transacao = $this->getApi()->getTransacao();
        
        if ($transacao->getStatus() == Transacao::STATUS_ERRO) {
            Mage::throwException($this->getHelper()->getErrorMessage($transacao));
        }
        
        $payment
            ->setIsTransactionApproved($this->isAuthorizationSuccess())
            ->setIsTransactionDenied($this->isCancelationSuccess())
            ->setIsTransactionClosed($this->isCancelationSuccess() || $this->isCaptureSuccess())
            ->setCcStatus($transacao->getStatus())
            ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->getLastResponse());
        
        $data = array_merge(parent::fetchTransactionInfo($payment, $transactionId), $this->getLastResponse());
        return $data;
    }
    
    /**
     * (non-PHPdoc)
     * @see Mage_Payment_Model_Method_Cc::getVerificationRegEx()
     */
    public function getVerificationRegEx()
    {
        $verificationExpList = parent::getVerificationRegEx();
        $verificationExpList['AU']   = '/^[0-9]{3}$/';
        $verificationExpList['EL']   = '/^[0-9]{3}$/';
        $verificationExpList['DICL'] = '/^[0-9]{3}$/';
        return $verificationExpList;
    }
    
    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $redirectUrl = Mage::registry("{$this->_code}_url_autenticacao");
        if (empty($redirectUrl)) {
            $redirectUrl = Mage::getUrl('checkout/onepage/success');
        }
        return $redirectUrl;
    }

}
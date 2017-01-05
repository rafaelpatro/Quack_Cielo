<?php
if (!@class_exists('CieloService')) {
    $lib = Mage::getBaseDir('lib');
    include_once "{$lib}/Tritoq/Payment/PortadorInterface.php";
    include_once "{$lib}/Tritoq/Payment/Exception/ResourceNotFoundException.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/AnaliseRisco.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/Cartao.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/CieloService.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/Loja.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/Pedido.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/Portador.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/Requisicao.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/Transacao.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/AnaliseRisco/AnaliseResultado.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/AnaliseRisco/ClienteAnaliseRiscoInterface.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/AnaliseRisco/PedidoAnaliseRisco.php";
    include_once "{$lib}/Tritoq/Payment/Cielo/AnaliseRisco/Modelo/ClienteAnaliseRiscoTest.php";
}

class Quack_Cielo_Model_Abstract extends Mage_Payment_Model_Method_Cc
{

    /**
     * @var CieloService
     */
    protected $_api = null;

    /**
     * @return CieloService
     */
    public function getApi()
    {
        if ($this->_api == null) {
            $this->_api = new CieloService();
        }
        return $this->_api;
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @return Quack_Cielo_Model_Abstract
     */
    public function loadApi(Varien_Object $payment, $amount = null)
    {
        $this->_setStoreData();
        if (empty($this->getInfoInstance()->getCcTransId())) {
            $this->_setTransactionData();
            $this->_setCardData();
            $this->_setOrderData($payment, $amount);
            $this->_setOwnerData($payment);
        } else {
            $transacao = new Transacao();
            $transacao
                ->setTid($this->getInfoInstance()->getCcTransId())
                ->setValor(number_format($amount, 2, '', ''));
            $this->getApi()->setTransacao($transacao);
        }
        $this->getApi()->setSsl($this->getConfigData('ssl_file'));
        return $this;
    }
    
    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Quack_Cielo_Model_Abstract
     */
    private function _setOwnerData(Varien_Object $payment)
    {
        $billing = $payment->getOrder()->getQuote()->getBillingAddress();
        $portador = new Portador();
        $portador->setCep($billing->getPostcode())
            ->setEndereco($billing->getStreet1())
            ->setNumero($billing->getStreet2())
            ->setComplemento($billing->getStreet3())
            ->setBairro($billing->getStreet4());
        $this->getApi()->setPortador($portador);
        return $this;
    }
    
    /**
     * @return Quack_Cielo_Model_Abstract
     */
    private function _setStoreData()
    {
        $loja = new Loja();
        $loja->setNumeroLoja($this->getConfigData('api_id'))
            ->setChave($this->getConfigData('api_key'))
            ->setUrlRetorno(Mage::getUrl('checkout/onepage/success'))
            ->setAmbiente($this->getConfigData('api_environment'));
        $this->getApi()->setLoja($loja);
        return $this;
    }
    
    /**
     * @return Quack_Cielo_Model_Abstract
     */
    private function _setCardData()
    {
        $info = $this->getInfoInstance();
        $hash = array(
            'AE'   => Cartao::BANDEIRA_AMERICAN_EXPRESS,
            'AU'   => Cartao::BANDEIRA_AURA,
            'DICL' => Cartao::BANDEIRA_DINERS,
            'DI'   => Cartao::BANDEIRA_DISCOVER,
            'EL'   => Cartao::BANDEIRA_ELO,
            'JCB'  => Cartao::BANDEIRA_JCB,
            'MC'   => Cartao::BANDEIRA_MASTERCARD,
            'VI'   => Cartao::BANDEIRA_VISA,
        );
        $cartao = new Cartao();
        $cartao
            ->setNumero($info->getCcNumber())
            ->setCodigoSegurancaCartao($info->getCcCid())
            ->setBandeira($hash[$info->getCcType()])
            ->setNomePortador($this->_getCcOwner())
            ->setValidade(
                $this->getHelper()
                    ->formatExpirationDate($info->getCcExpYear(), $info->getCcExpMonth()) );
        $this->getApi()->setCartao($cartao);
        return $this;
    }
    
    /**
     * @return Quack_Cielo_Model_Abstract
     */
    private function _setTransactionData()
    {
        $info = $this->getInfoInstance();
        $transacao = new Transacao();
        $transacao
            ->setProduto($this->_getProduct())
            ->setParcelas($info->getAdditionalInformation('cc_installments'))
            ->setAutorizar($this->_getAuthorization())
            ->setCapturar($this->_getCapture());
        $this->getApi()->setTransacao($transacao);
        return $this;
    }
    
    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @return Quack_Cielo_Model_Abstract
     */
    private function _setOrderData(Varien_Object $payment, $amount)
    {
        $pedido = new Pedido();
        $pedido->setDataHora(
            DateTime::createFromFormat('Y-m-d H:i:s', $payment->getOrder()->getCreatedAt()))
            ->setDescricao(Mage::app()->getStore()->getName())
            ->setIdioma(Pedido::IDIOMA_PORTUGUES)
            ->setNumero($payment->getOrder()->getIncrementId())
            ->setValor(number_format($amount, 2, '', ''));
        $this->getApi()->setPedido($pedido);
        return $this;
    }
    
    /**
     * @param string $type
     * @return array
     */
    public function getLastResponse()
    {
        $reqList = $this->getApi()->getTransacao()->getRequisicoes();
        $reqLast = array_pop($reqList);
        $requisicao = array_pop($reqLast);
        $xml = $requisicao->getXmlRetorno();
        $list = array();
        $list['Situação'] = $this->getHelper()->__("STATUS " . (int) $xml->status);
        if (isset($xml->{'dados-pedido'})) {
            $vlr = $xml->{'dados-pedido'}->valor / 100;
            $list['Valor do Pedido'] = Mage::helper('core')->currency($vlr, true, false);
        }
        if ($this->getInfoInstance()) {
            $info = $this->getInfoInstance();
            $list['Portador'] = $info->getCcOwner();
            $list['Portador'].= " | CPF: {$info->getAdditionalInformation('cc_taxvat')}";
            $list['Portador'].= " | Fone: {$info->getAdditionalInformation('cc_phone')}";
            $list['Cartão'] = "{$info->getAdditionalInformation('cc_bin')}******{$info->getCcLast4()}";
            $list['Validade'] = "{$info->getCcExpMonth()}/{$info->getCcExpYear()}";
        }
        if (isset($xml->{'forma-pagamento'})) {
            $list['Forma de Pagamento'] = strtoupper((string) $xml->{'forma-pagamento'}->bandeira);
            $list['Forma de Pagamento'].= " | " . $this->getHelper()->getSource('product', (string) $xml->{'forma-pagamento'}->produto);
            if ($xml->{'forma-pagamento'}->parcelas > 1) {
                $list['Forma de Pagamento'].= " | " . $xml->{'forma-pagamento'}->parcelas . " parcelas";
            }
        }
        if (isset($xml->{'url-autenticacao'})) {
            if (Mage::app()->getStore()->isAdmin()) {
                $list['Url Autenticação'] = (string) $xml->{'url-autenticacao'};
            }
        }
        if (isset($xml->autenticacao)) {
            $list['Autenticação'] = (string) $xml->autenticacao->mensagem;
            $list['ECI'] = $this->getHelper()->__(
                "ECI " . $xml->autenticacao->eci . " " . strtoupper((string) $xml->{'forma-pagamento'}->bandeira)
            );
        }
        if (isset($xml->autorizacao)) {
            $list['Autorização'] = (string) $xml->autorizacao->mensagem;
            $list['LR'] = $this->getHelper()->__("LR " . $xml->autorizacao->lr);
            $list['ARP'] = (string) $xml->autorizacao->arp;
            $list['NSU'] = (string) $xml->autorizacao->nsu;
        }
        if (isset($xml->captura)) {
            $vlr = $xml->captura->valor / 100;
            $list['Captura'] = $xml->captura->mensagem . " | Valor: " . Mage::helper('core')->currency($vlr, true, false);
        }
        if (isset($xml->cancelamentos)) {
            $i = 1;
            foreach ($xml->cancelamentos->children() as $cancelamento) {
                $time = (string) $cancelamento->{'data-hora'};
                $msg = (string) $cancelamento->mensagem;
                $vlr = Mage::helper('core')->currency($cancelamento->valor / 100, true, false);
                $list["Cancelamento {$i}"] =  "{$msg} | Valor: {$vlr}";
                $i++;
            }
        }
        
        return $list;
    }
    
    /**
     * @return Quack_Cielo_Model_Abstract
     */
    public function validateCcTaxvat()
    {
        $errorMsg = $this->getHelper()->__("Please, check your tax/vat number");
        $cpf = $this->getInfoInstance()->getAdditionalInformation('cc_taxvat');
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);
        if (strlen($cpf) != 11) {
            Mage::throwException($errorMsg);
        }
        $cpf9  = substr($cpf, 0, 9);
        $cpf10 = $this->getHelper()->cpfVerificationCode($cpf9, 10);
        $cpf11 = $this->getHelper()->cpfVerificationCode($cpf10,11);
        if ($cpf != $cpf11) {
            Mage::throwException($errorMsg);
        }
        return $this;
    }
    
    /**
     * @return Quack_Cielo_Model_Abstract
     */
    public function validateCcInstallments()
    {
        $qty        = $this->getInfoInstance()->getAdditionalInformation('cc_installments');
        $maxQty     = $this->getConfigData('installments_qty_max');
        $isEnabled  = (is_numeric($maxQty) && is_numeric($qty) && $qty > 1);
        if ($isEnabled) {
            if ($qty > $maxQty) {
                $message = $this->getHelper()->__("Exceeded installments");
                Mage::throwException($message);
            }
            
            $amount    = $this->_getAmount() / $qty;
            $minAmount = $this->getConfigData('installments_amount_min');
            if (round($amount, 2) < $minAmount) {
                $message = $this->getHelper()->__("Exceeded minimum installments value");
                Mage::throwException($message);
            }
        } elseif ((int) $qty != 1) {
            $message = $this->getHelper()->__("Invalid installments for card issuer");
            Mage::throwException($message);
        }
        return $this;
    }
    
    private function _getAuthorization()
    {
        $authorization = $this->getConfigData('authorization');
        $hasAuthentication = in_array($this->getInfoInstance()->getCcType(), array('VI', 'MC'));
        if (!$hasAuthentication) {
            /**
             * Para Diners, Discover, Elo, Amex, Aura e JCB o valor será sempre “3”, pois estas bandeiras não possuem programa de autenticação.
             */
            $authorization = Transacao::AUTORIZAR_SEM_AUTENTICACAO;
        }
        if ($this->getConfigPaymentAction() == Mage_Payment_Model_Method_Abstract::ACTION_ORDER) {
            $authorization = Transacao::AUTORIZAR_NAO_AUTORIZAR;
        }
        return $authorization;
    }
    
    private function _getProduct()
    {
        $product = $this->getConfigData('product');
        if ($product != Transacao::PRODUTO_DEBITO) {
            if ($this->getInfoInstance()->getAdditionalInformation('cc_installments') == 1) {
                $product = Transacao::PRODUTO_CREDITO_AVISTA;
            }
        }
        return $product;
    }
    
    private function _getCapture()
    {
        $capture = Transacao::CAPTURA_NAO;
        if ($this->getConfigPaymentAction() == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
            $capture = Transacao::CAPTURA_SIM;
        }
        return $capture;
    }
    
    /**
     * @return float
     */
    private function _getAmount()
    {
        $amount = 0;
        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            return $info->getOrder()->getQuoteBaseGrandTotal();
        }
        return $info->getQuote()->getBaseGrandTotal();
    }
    
    /**
     * @return string
     */
    private function _getCcOwner()
    {
        $info = $this->getInfoInstance();
        if (!$info->getCcOwner() && $info instanceof Mage_Sales_Model_Order_Payment) {
            return $info->getOrder()->getCustomerName();
        }
        return $info->getCcOwner();
    }
    
    /**
     * @return bool
     */
    public function isTransactionSuccess()
    {
        $status = $this->getApi()->getTransacao()->getStatus();
        return in_array($status, array(
            Transacao::STATUS_CRIADA,
            Transacao::STATUS_ANDAMENTO,
            Transacao::STATUS_AUTENTICADA,
            Transacao::STATUS_NAO_AUTENTICADA,
            Transacao::STATUS_AUTORIZADA,
            Transacao::STATUS_CAPTURADA,
            Transacao::STATUS_EM_AUTENTICACAO
        ));
    }
    
    /**
     * @return bool
     */
    public function isCancelationSuccess()
    {
        $status = $this->getApi()->getTransacao()->getStatus();
        $list = array(
            Transacao::STATUS_NAO_AUTORIZADA,
            Transacao::STATUS_CANCELADA
        );
        if ($this->_getProduct() == Transacao::PRODUTO_DEBITO) {
            $list[] = Transacao::STATUS_NAO_AUTENTICADA;
        }
        return in_array($status, $list);
    }
    
    public function isCaptureSuccess()
    {
        $status = $this->getApi()->getTransacao()->getStatus();
        return in_array($status, array(
            Transacao::STATUS_CAPTURADA
        ));
    }
    
    public function isAuthorizationSuccess()
    {
        $reqList = $this->getApi()->getTransacao()->getRequisicoes();
        foreach ($reqList as $req) {
            $requisicao = array_pop($req);
            $xml = $requisicao->getXmlRetorno();
            if (isset($xml->autorizacao)) {
                $isNational = ((string) $xml->autorizacao->lr == '00');
                $isInternational = ((string) $xml->autorizacao->lr == '11');
                return ($isNational || $isInternational);
            }
        }
        return false;
    }
    
    /**
     * @return Quack_Cielo_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('qck_cielo');
    }
}
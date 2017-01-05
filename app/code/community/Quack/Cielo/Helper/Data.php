<?php
class Quack_Cielo_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function strtoascii($str) {
        setlocale(LC_ALL, 'pt_BR.utf8');
        return iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    }
    
    public function formatExpirationDate($year, $month)
    {
        $result = false;
        $isValid = (is_numeric($year) && is_numeric($month));
        if ($isValid) {
            $month = str_pad($month, 2, 0, STR_PAD_LEFT);
            $result = "{$year}{$month}";
        }
        return $result;
    }
    
    public function getErrorMessage(Transacao $transacao)
    {
        $msg = array();
        $requisicoes = $transacao->getRequisicoes();
        foreach ($requisicoes as $req) {
            $requisicao = array_pop($req);
            /* @var $requisicao Requisicao */
            foreach ($requisicao->getErrors() as $erro) {
                $msg[] = self::__('ERRO ' . (string) $erro->codigo);
            }
            if (count($msg) == 0 && null !== $requisicao->getXmlRetorno()) {
                $xml = $requisicao->getXmlRetorno();
                $msg[] = self::__("STATUS " . $xml->status);
                if (isset($xml->autorizacao)) {
                    $msg[] = self::__('LR ' . (string) $xml->autorizacao->lr);
                }
            }
        }
        return implode(' | ', $msg);
    }
    
    public function cpfVerificationCode( $digitos, $posicoes = 10, $soma_digitos = 0 )
    {
        for ( $i = 0; $i < strlen( $digitos ); $i++  ) {
            $soma_digitos = $soma_digitos + ( $digitos[$i] * $posicoes );
            $posicoes--;
        }
        $soma_digitos = $soma_digitos % 11;
        
        if ( $soma_digitos < 2 ) {
            $soma_digitos = 0;
        } else {
            $soma_digitos = 11 - $soma_digitos;
        }
        
        $cpf = $digitos . $soma_digitos;
        return $cpf;
    }
    
    public function getSource($name, $value)
    {
        $source = Mage::getModel("qck_cielo/source_{$name}");
        foreach ($source->toOptionArray() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }
    
}
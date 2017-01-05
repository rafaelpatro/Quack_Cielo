<?php

class Quack_Cielo_Model_Source_Environment
{
    
    const SANDBOX    = 'teste';
    const PRODUCTION = 'producao';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::SANDBOX,
                'label' => Mage::helper('qck_cielo')->__('Teste')
            ),
            array(
                'value' => self::PRODUCTION,
                'label' => Mage::helper('qck_cielo')->__('Produção')
            ),
        );
    }
}
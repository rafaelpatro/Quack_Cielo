<?php

class Quack_Cielo_Model_Source_Product
{

    const DEBIT  = 'A';
    const CREDIT = '1';
    const INSTALLMENTS_BY_STORE = '2';
    const INSTALLMENTS_BY_ISSUER = '3';
    
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::DEBIT,
                'label' => Mage::helper('qck_cielo')->__('Débito')
            ),
            array(
                'value' => self::CREDIT,
                'label' => Mage::helper('qck_cielo')->__('Crédito à Vista')
            ),
            array(
                'value' => self::INSTALLMENTS_BY_STORE,
                'label' => Mage::helper('qck_cielo')->__('Parcelado Loja')
            ),
            array(
                'value' => self::INSTALLMENTS_BY_ISSUER,
                'label' => Mage::helper('qck_cielo')->__('Parcelado Administradora')
            )
        );
    }
}
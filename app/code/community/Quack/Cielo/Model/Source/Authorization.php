<?php

class Quack_Cielo_Model_Source_Authorization
{

    public function toOptionArray()
    {
        return array(
            /*array(
                'value' => 0,
                'label' => Mage::helper('qck_cielo')->__('Não autorizar (somente autenticar)')
            ),*/
            array(
                'value' => 1,
                'label' => Mage::helper('qck_cielo')->__('Autorizar somente se autenticada')
            ),
            array(
                'value' => 2,
                'label' => Mage::helper('qck_cielo')->__('Autorizar autenticada e não autenticada')
            ),
            array(
                'value' => 3,
                'label' => Mage::helper('qck_cielo')->__('Autorizar sem passar por autenticação')
            ),
            /*array(
                'value' => 4,
                'label' => Mage::helper('qck_cielo')->__('Transação recorrente')
            ),*/
       );
    }
}
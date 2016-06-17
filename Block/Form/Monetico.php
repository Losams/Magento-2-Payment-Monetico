<?php

namespace Ilio\Monetico\Block\Form;

/**
 * Abstract class for Cash On Delivery and Bank Transfer payment method form
 */
abstract class Monetico extends \Magento\Payment\Block\Form
{
    /**
     * Instructions text
     *
     * @var string
     */
    protected $_instructions;


    protected $_template = 'form/monetico.phtml';

    /**
     * Get instructions text from config
     *
     * @return null|string
     */
    public function getInstructions()
    {
        if ($this->_instructions === null) {
            /** @var \Magento\Payment\Model\Method\AbstractMethod $method */
            $method = $this->getMethod();
            $this->_instructions = $method->getConfigData('instructions');
        }
        return $this->_instructions;
    }
}

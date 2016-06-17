<?php

namespace Ilio\Monetico\Controller\Index;

/**
 * Description of Redirect
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->_checkoutSession = $checkoutSession;

        parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->_checkoutSession->getLastRealOrder();

        if ($order->getId()) {
            $this->_checkoutSession
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus())
                ->setLastSuccessQuoteId($order->getQuoteId())
            ;
        }

        $this->_redirect('checkout/onepage/success');
    }
}

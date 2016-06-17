<?php

namespace Ilio\Monetico\Controller\Checkout;

/**
 * Description of Redirect
 */
class Redirect extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_session;

    /**
     * @var \Paynl\Payment\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    protected $_moneticoModel;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ilio\Monetico\Model\Monetico $moneticoModel,
        \Magento\Customer\Model\Session $session,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_moneticoModel = $moneticoModel;
        $this->_session = $session;

        parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        if ($order->getId() && $order->getCustomerId() == $this->_session->getId()) {
            $incrementId = $this->_checkoutSession->getLastRealOrderId();
            $datas = $this->_moneticoModel->getPostData($incrementId);

            $datasInGet = http_build_query($datas);

            $urlBank = $this->_moneticoModel->getGateUrl();
            $redirectUrl = $urlBank . '?' . $datasInGet;
            $this->getResponse()->setRedirect($redirectUrl);
        }
    }
}

<?php

namespace Ilio\Monetico\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Ilio\Monetico\Model\Config\Source\Order\Status\Paymentreview;
use Magento\Sales\Model\Order;


/**
 * Pay In Store payment method model
 */
class Monetico extends AbstractMethod
{
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    protected $_isGateway = true;

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'moneticopayment';

    // Warning read comment below
    protected $_orderPlaceRedirectUrl = "/monetico/checkout/redirect"; // Not used anymore on magento2, only used to stop email when order is created

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = false;

    public $_devise = 'EUR';

    /**
     * Payment additional info block
     *
     * @var string
     */
    protected $_formBlockType = 'Ilio\Monetico\Block\Form\Monetico';

    /**
     * Sidebar payment info block
     *
     * @var string
     */
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

    protected $_bankUrl = "https://p.monetico-services.com/paiement.cgi";

    protected $_bankUrlTest = "https://p.monetico-services.com/test/paiement.cgi";

    protected $_tpeProd;

    protected $_tpeTest;

    protected $_societyTest;

    protected $_societyProd;

    protected $_paymentKey;

    protected $_test;

    protected $_storeName;

    protected $_emailContact;

    protected $orderFactory;

    protected $_urlBuilder;

    protected $_orderSender;

    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function getOrderPlaceRedirectUrl()
    {
        return $this->_orderPlaceRedirectUrl;
    }

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []){

        $this->orderFactory = $orderFactory;
        $this->_urlBuilder = $urlBuilder;
        $this->_orderSender = $orderSender;

        parent::__construct($context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data);

        // Init attributes
        $this->init();
    }

    protected function getOrder($orderId)
    {
        $orderFactory=$this->orderFactory;
        return $orderFactory->create()->loadByIncrementId($orderId);

    }

    /**
     * Set order state and status
     *
     * @param string $paymentAction
     * @param \Magento\Framework\Object $stateObject
     * @return void
     */
    public function init()
    {
        // Params in config (after parent::construct)
        $paramsPayment = $this->_scopeConfig->getValue('payment/moneticopayment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->_storeName = $this->_scopeConfig->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->_emailContact = $this->_scopeConfig->getValue('contact/email/recipient_email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $this->_test    = $paramsPayment['test'];
        $this->_bankUrl = $paramsPayment['bank_url'];
        $this->_bankUrlTest = $paramsPayment['bank_url_test'];
        $this->_tpeProd = $paramsPayment['tpe_prod'];
        $this->_tpeTest = $paramsPayment['tpe_test'];
        $this->_societyTest = $paramsPayment['society_test'];
        $this->_societyProd = $paramsPayment['society_prod'];
        $this->_paymentKey = $paramsPayment['payment_key'];
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote === null) {
            return false;
        }
        return parent::isAvailable($quote) && $this->isCarrierAllowed(
            $quote->getShippingAddress()->getShippingMethod()
        );
    }

    /**
     * Get external url Payment
     * @return string url
     */
    public function getGateUrl(){
        if ($this->_test) {
            return $this->_bankUrlTest;
        }

        return $this->_bankUrl;
    }

    /**
     * Check whether payment method can be used with selected shipping method
     *
     * @param string $shippingMethod
     * @return bool
     */
    protected function isCarrierAllowed($shippingMethod)
    {
        return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== false;
    }


    /**
     * Return the usable key in a correct format
     * in order to crypt MAC
     * @return string key
     */
    private function _getUsableKey()
    {
        $key = $this->_paymentKey;

        $hexStrKey  = substr($key, 0, 38);
        $hexFinal   = "" . substr($key, 38, 2) . "00";

        $cca0=ord($hexFinal);

        if ($cca0>70 && $cca0<97)
            $hexStrKey .= chr($cca0-23) . substr($hexFinal, 1, 1);
        else {
            if (substr($hexFinal, 1, 1)=="M")
                $hexStrKey .= substr($hexFinal, 0, 1) . "0";
            else
                $hexStrKey .= substr($hexFinal, 0, 2);
        }


        return pack("H*", $hexStrKey);
    }

    /**
     * Function to generate MAC key from all input
     * @return string length 40 MAC key
     */
    public function generateHash($params)
    {
        $formatStringToCrypt = "%s*%s*%s%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s";

        $phase1go_fields = sprintf($formatStringToCrypt,
            $params['TPE'],
            $params['date'],
            $params['montant'],
            $this->_devise,
            $params['reference'],
            $params['texte-libre'],
            $params['version'],
            $params['lgue'],
            $params['societe'],
            $params['mail'],
            '', //$sNbrEch,
            '', //$sDateEcheance1,
            '', //$sMontantEcheance1,
            '', //$sDateEcheance2,
            '', //$sMontantEcheance2,
            '', //$sDateEcheance3,
            '', //$sMontantEcheance3,
            '', //$sDateEcheance4,
            '', //$sMontantEcheance4,
            '' //$sOptions
        );

        return strtolower(hash_hmac("sha1", $phase1go_fields, $this->_getUsableKey()));

        return $hash;
    }

    /**
     * Function to generate MAC key from all input
     * @return string length 40 MAC key
     */
    public function generateHashResponse($params)
    {
        $formatStringToCrypt = "%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*";

        $phase1go_fields = sprintf($formatStringToCrypt,
            $params['TPE'],
            $params['date'],
            $params['montant'],
            isset($params['reference']) ? $params['reference'] : '',
            isset($params['texte-libre']) ? $params['texte-libre'] : '',
            '3.0',
            isset($params['code-retour']) ? $params['code-retour'] : '',
            isset($params['cvx']) ? $params['cvx'] : '',
            isset($params['vld']) ? $params['vld'] : '',
            isset($params['brand']) ? $params['brand'] : '',
            isset($params['status3ds']) ? $params['status3ds'] : '',
            isset($params['numauto']) ? $params['numauto'] : '',
            isset($params['motifrefus']) ? $params['motifrefus'] : '',
            isset($params['originecb']) ? $params['originecb'] : '',
            isset($params['bincb']) ? $params['bincb'] : '',
            isset($params['hpancb']) ? $params['hpancb'] : '',
            isset($params['ipclient']) ? $params['ipclient'] : '',
            isset($params['originetr']) ? $params['originetr'] : '',
            isset($params['veres']) ? $params['veres'] : '',
            isset($params['pares']) ? $params['pares'] : ''
        );

        return strtolower(hash_hmac("sha1", $phase1go_fields, $this->_getUsableKey()));

        return $hash;
    }

    public function getPostData($orderId)
    {
        $order = $this->getOrder($orderId);

        $orderDate = new \Datetime($order->getUpdatedAt());
        //OutSumCurrency
        $postData=[];

        // Real POST for monetico
        $postData['version'] = '3.0';
        $postData['TPE'] = $this->_test ? $this->_tpeTest : $this->_tpeProd;
        $postData['date'] = date("d/m/Y:H:i:s");
        $postData['montant'] = round($order->getGrandTotal(), 2);
        $postData['reference'] = $order->getIncrementId();
        $postData['url_retour'] = $this->_urlBuilder->getUrl('monetico');
        $postData['url_retour_ok'] = $this->_urlBuilder->getUrl('monetico');
        $postData['url_retour_err'] = $this->_urlBuilder->getUrl('/');
        $postData['lgue'] = 'FR';
        $postData['societe'] = $this->_test ? $this->_societyTest : $this->_societyProd;
        $postData['texte-libre'] = 'payment from ' . $this->_storeName;
        $postData['mail'] = $this->_emailContact;

        $postData['MAC'] = $this->generateHash($postData);

        // reformat amount with devise
        $postData['montant'] = $postData['montant'] . $this->_devise;

        return $postData;
    }

    /* ----------------------- */
    /* Treatments POST payment */
    /* ----------------------- */

    public function process($params)
    {
        // Send reception to bank
        $calcMac = $this->generateHashResponse($params);
        $getMAC = isset($params['MAC']) ? $params['MAC'] : '';
        $correctHash = $getMAC == $calcMac;
        $this->sendReceptionToBank($correctHash);

        // check MAC key
        // Stop treatment if signature is not send
        if (!$getMAC) {
            // Error no MAC signature
            return array('error', 'Signature (MAC) is not send');
        }

        // Stop treatment if signature is not good
        if (!$correctHash) {
            return array('error', 'Signature is not valid');
        }

        if(isset($params['reference'])){
            $order = $this->getOrder($params['reference']);

            if ($order->getId()) {
                return $this->_processOrder($order, $params);
            } else {
                return array('error', 'No order find for ' . $params['reference']);
            }
        } else {
            return array('error', 'Reference is not send');
        }
    }

    protected function _processOrder(\Magento\Sales\Model\Order $order , $params)
    {
        $payment = $order->getPayment();

        try {
            // If payment is ok
            if (isset($params['code-retour']) &&
                ( $params['code-retour'] == 'payetest' || $params['code-retour'] == 'paiement' )
            ) {
                $outSum = round($order->getGrandTotal(), 2);

                if ($outSum != (float)$params["montant"]) {
                    // Error amount difference
                    return array('error', 'Amount is not valid');
                }

                if (!isset($params['numauto'])) {
                    return array('error', 'NumAuto is not send');
                }

                // Finally, set the order to paid
                $payment->setTransactionId($params['numauto'])->setIsTransactionClosed(0);
                $order->setStatus(Order::STATE_PAYMENT_REVIEW);
                $order->save();

                // Send transact email
                $this->_orderSender->send($order);

                return array('info', "Order saved");
            } else {
                return array('notice', "Return code is canceled");
            }
        } catch (Exception $e) {
            return array('error', $e->getMessage());
        }
    }

    /**
     * Function to send good or bad reception for bank control
     */
    public function sendReceptionToBank($correctHash)
    {
        echo 
            'version=2
cdr=' . ($correctHash ? '1' : '0');

        return true;
    }
}

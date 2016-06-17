<?php

namespace Ilio\Monetico\Controller\Response;

/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $resultPageFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $_moneticoModel;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ilio\Monetico\Logger\Logger $logger,
        \Ilio\Monetico\Model\Monetico $moneticoModel,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->_moneticoModel = $moneticoModel;
        parent::__construct($context);
    }

    /**
     * Load the page defined in view/frontend/layout/samplenewpage_index_index.xml
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $this->logger->notice('--------------- New bank response ---------------');
        $this->logger->notice(json_encode($params));

        $return = $this->_moneticoModel->process($params);

        $this->logger->{$return[0]}($return[1]);
    }
}

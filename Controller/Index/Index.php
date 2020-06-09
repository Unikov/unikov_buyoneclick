<?php


namespace Unikov\BuyOneClick\Controller\Index;

use PHPUnit\Framework\MockObject\Stub\Exception;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    protected $jsonHelper;
    private $_inlineTranslation;
    protected $_transportBuilder;
    protected $_storeManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator


    ) {
        parent::__construct($context);
        $this->formKeyValidator = $formKeyValidator ?: ObjectManager::getInstance()->get(\Magento\Framework\Data\Form\FormKey\Validator::class);

        $this->_transportBuilder = $transportBuilder;
        $this->_logger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_jsonHelper = $jsonHelper;
    }



    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {


        try {
            // find out
            if (!$this->formKeyValidator->validate($this->getRequest())) {

                throw new \Exception('UPS');
            }

            if (!$productSku = $this->getRequest()->getParam('product_sku')) {

                throw new \Exception('product sku is empty');
            }


            $product = $this->_productRepository->get($productSku);
            $store = $this->_storeManager->getStore()->getId();
            $email = $this->_scopeConfig->getValue('trans_email/ident_sales/email');
            if(!$email) {
                throw new Exception('email is empty');
            }
            $transport = $this->_transportBuilder->setTemplateIdentifier(3)
                ->setTemplateOptions(['area' => 'frontend', 'store' => $store])
                ->setTemplateVars(
                    [
                        'product' => $product,
                        'phone' => $this->getRequest()->getParam('phone'),
                        'store' => $this->_storeManager->getStore(),
                    ]
                )
                ->setFrom('general')
                // you can config general email address in Store -> Configuration -> General -> Store Email Addresses
                ->addTo($email, 'Matryoshkadesign')
                ->getTransport();
            $transport->sendMessage();

            return $this->jsonResponse('OK');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            //$this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->_jsonHelper->jsonEncode($response)
        );
    }
}

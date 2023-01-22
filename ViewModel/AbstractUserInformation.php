<?php

namespace Tan\EnhancedEcommerce\ViewModel;

use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tan\EnhancedEcommerce\Service\FormatDate;
use Psr\Log\LoggerInterface;

abstract class AbstractUserInformation extends DataObject implements ArgumentInterface
{
    const CUSTOMER_REWARD_CACHE_TAG = 'reward_cache';
    const CUSTOMER_COUPON_CACHE_TAG = 'coupon_cache';
    const CUSTOMER_SUBSCRIBE_CACHE_TAG = 'subscribe_cache';

    protected $customerData = [];

    protected $globalAttributes = [
        'ga_client_id',
        'user_id',
        'user_type',
        'device_type',
        'registration_type',
        'login_status',
        'login_type'
    ];

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var FormatDate
     */
    protected $dateFormatter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ScopeConfigInterface $config,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CacheInterface $cache,
        SerializerInterface $serializer,
        FormatDate $dateFormatter,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($data);
        $this->cache = $cache;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->dateFormatter = $dateFormatter;
        try {
            $this->init();
            if ($this->customerSession->isLoggedIn()) {
                $this->fillData();
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @param string $key
     * @param null|string|int $index
     * @param bool $split is  need to split per Global Nestle or TAN Specific attributes
     * @return array|mixed|null
     */
    public function getData($key = '', $index = null, $split = false)
    {
        $data = parent::getData($key, $index);

        if ($split && empty($key)) {
            $formattedData = [];
            foreach ($this->customerData as $dataKey => $value) {
                $area = in_array($dataKey, $this->globalAttributes) ? 'global' : 'tan';
                $formattedData[$area][$dataKey] = $data[$dataKey];
            }

            return $formattedData;
        }

        return $data;
    }

    protected function init()
    {
        foreach ($this->customerData as $key => $field) {
            $this->setData($key, $field);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function fillData()
    {
        $userData = $this->getInfo();
        if (!empty($userData)) {
            foreach ($this->customerData as $key => $value) {
                if (isset($userData[$key])) {
                    $this->setData($key, $userData[$key]);
                }
            }
        }
    }

    /**
     * @return array
     */
    abstract protected function getInfo();
}

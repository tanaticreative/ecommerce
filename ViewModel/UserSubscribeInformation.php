<?php

namespace Tan\EnhancedEcommerce\ViewModel;

use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;
use Tan\EnhancedEcommerce\Model\Service\UserInformationService;
use Tan\EnhancedEcommerce\Service\FormatDate;
use Tan\Newsletter\Model\Config;
use Psr\Log\LoggerInterface;

/**
 * Class UserSubscribeInformation
 * @package Tan\EnhancedEcommerce\ViewModel
 * @method string getOptinValue()
 * @method UserSubscribeInformation  setOptinValue() setOptionValue($data)
 */
class UserSubscribeInformation extends AbstractUserInformation
{
    protected $customerData = [
        'optin_value' => Config::OPTOUT_LABEL
    ];

    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @var Config
     */
    private $subscriberConfig;

    /**
     * @var UserInformationService
     */
    private $service;

    public function __construct(
        ScopeConfigInterface $config,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CacheInterface $cache,
        SerializerInterface $serializer,
        FormatDate $dateFormatter,
        Subscriber $subscriber,
        Config $subscriberConfig,
        LoggerInterface $logger,
        UserInformationService $service,
        array $data = []
    ) {
        $this->subscriber = $subscriber;
        $this->subscriberConfig = $subscriberConfig;
        $this->service = $service;

        parent::__construct($config, $customerSession, $storeManager, $cache, $serializer, $dateFormatter, $logger, $data);
    }

    protected function getInfo()
    {
        $customer = $this->customerSession->getCustomer();
        $subscriberData = $this->cache->load(self::CUSTOMER_SUBSCRIBE_CACHE_TAG . '_' . $customer->getId());

        if (!$subscriberData) {
            $subscriberData = $this->service->updateUserSubscribeInfoCache($customer->getId());
        }

        return $this->serializer->unserialize($subscriberData);
    }
}

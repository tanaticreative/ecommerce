<?php

namespace Tan\EnhancedEcommerce\ViewModel;

use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\StoreManagerInterface;
use Tan\EnhancedEcommerce\Model\Service\UserInformationService;
use Tan\EnhancedEcommerce\Service\FormatDate;
use Psr\Log\LoggerInterface;

/**
 * Class UserRewardInformation
 * @package Tan\EnhancedEcommerce\ViewModel
 * @method string getPointBalance()
 * @method string getBalanceExpirationDate()
 * @method string getLastPointMovement()
 * @method UserRewardInformation setPointBalance() setPointBalance($data)
 * @method UserRewardInformation setBalanceExpirationDate() setBalanceExpirationDate($data)
 * @method UserRewardInformation setLastPointMovement() setLastPointMovement($data)
 */
class UserRewardInformation extends AbstractUserInformation
{
    protected $customerData = [
        'last_point_movement' => '',
        'balance_expiration_date' => '',
        'point_balance' => '',
    ];

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
        UserInformationService $service,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->service = $service;
        parent::__construct($config, $customerSession, $storeManager, $cache, $serializer, $dateFormatter, $logger, $data);
    }

    protected function getInfo()
    {
        $customer = $this->customerSession->getCustomer();
        $rewardData = $this->cache->load(self::CUSTOMER_REWARD_CACHE_TAG . '_' . $customer->getId());

        if (!$rewardData) {
            $rewardData = $this->service->updateUserRewardInfoCache($customer);
        }

        return $this->serializer->unserialize($rewardData);
    }
}

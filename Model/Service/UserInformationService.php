<?php

namespace Tan\EnhancedEcommerce\Model\Service;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\StoreManagerInterface;
use Tan\EnhancedEcommerce\Service\FormatDate;
use Tan\EnhancedEcommerce\ViewModel\UserRewardInformation;
use Tan\EnhancedEcommerce\ViewModel\UserSubscribeInformation;
use Tan\Newsletter\Model\Config;
use Psr\Log\LoggerInterface;

class UserInformationService
{
    private $cache;

    /**
     * @var Config
     */
    private $subscriberConfig;
    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RewardFactory
     */
    private $rewardFactory;

    /**
     * @var FormatDate
     */
    private $dateFormatter;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        CacheInterface $cache,
        SubscriberFactory $subscriberFactory,
        Config $subscriberConfig,
        SerializerInterface $serializer,
        RewardFactory $rewardFactory,
        FormatDate $dateFormatter,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->subscriberConfig = $subscriberConfig;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->rewardFactory = $rewardFactory;
        $this->dateFormatter = $dateFormatter;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function updateUserSubscribeInfoCache($customerId)
    {
        $value = [];
        try {
            /** @var  Subscriber $subscribeModel */
            $subscribeModel = $this->subscriberFactory->create()->loadByCustomerId($customerId);
            if ($subscribeModel->getSubscriberId()) {
                $value = $this->getSubscriberChannels($subscribeModel);
            }
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        $value = !empty($value) ? implode(',', $value) : Config::OPTOUT_LABEL;

        $serializedData = $this->serializer->serialize(['optin_value' => $value]);
        $this->cache->save(
            $serializedData,
            UserSubscribeInformation::CUSTOMER_SUBSCRIBE_CACHE_TAG . '_' . $customerId
        );

        return $serializedData;
    }

    public function updateUserRewardInfoCache($customer)
    {
        $data = [
            'point_balance' => '',
            'balance_expiration_date' => '',
            'last_point_movement' => ''
        ];
        try {
            /**
             * @var Reward $reward
             */
            $reward = $this->rewardFactory->create()
                ->setCustomer($customer)
                ->setWebsiteId($this->storeManager->getWebsite()->getId())
                ->loadByCustomer();

            if ($reward->getId()) {
                $data = [
                    'point_balance' => $reward->getPointsBalance(),
                    'balance_expiration_date' => $this->dateFormatter->execute($reward->getExpirationDate()),
                    'last_point_movement' => $reward->getPointsDelta() ?? ''
                ];
            }
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
        }

        $serializedData = $this->serializer->serialize($data);
        $this->cache->save($serializedData, UserRewardInformation::CUSTOMER_REWARD_CACHE_TAG . '_' . $customer->getId());

        return $serializedData;
    }

    /**
     * @param Subscriber $subscriber
     * @return array
     */
    public function getSubscriberChannels($subscriber)
    {
        $value = [];
        $newsletterOptions = $this->subscriberConfig->getNewsletterOptions($subscriber->getSubscriberId());
        if (!empty($newsletterOptions)) {
            foreach ($newsletterOptions as $channel => $option) {
                if (array_keys($option)[0]) {
                    switch ($channel) {
                        case Config::DOLCE_GUSTO_LEVEL:
                            $value[] = 'C';
                            break;
                        case Config::NESTLE_LEVEL:
                            $value[] = 'F';
                            break;
                    }
                }
            }
        }
        return $value;
    }
}

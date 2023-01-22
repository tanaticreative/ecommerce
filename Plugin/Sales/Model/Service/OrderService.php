<?php

namespace Tan\EnhancedEcommerce\Plugin\Sales\Model\Service;

use Magento\Customer\Api\CustomerRepositoryInterfaceFactory;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Service\OrderService as SalesOrderService;
use Magento\Store\Model\StoreManagerInterface;
use Tan\EnhancedEcommerce\Model\Service\UserInformationService;
use Tan\EnhancedEcommerce\Service\FormatDate;
use Tan\EnhancedEcommerce\ViewModel\UserCouponInformation;
use Psr\Log\LoggerInterface;

class OrderService
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var UserInformationService
     */
    private $service;

    /**
     * @var FormatDate
     */
    private $dateFormatter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer,
        CustomerRepositoryInterfaceFactory $customerRepositoryFactory,
        StoreManagerInterface $storeManager,
        UserInformationService $service,
        FormatDate $dateFormatter,
        LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->cache = $cache;
        $this->customerRepository = $customerRepositoryFactory->create();
        $this->storeManager = $storeManager;
        $this->service = $service;
        $this->dateFormatter = $dateFormatter;
        $this->logger = $logger;
    }

    public function afterPlace(SalesOrderService $orderService, OrderInterface $order): OrderInterface
    {
        if ($order->getCustomerId()) {
            try {
                $customer = $this->customerRepository->getById($order->getCustomerId());
                if ($order->getCouponCode()) {
                    $couponData = [
                        'last_code_entered' => $order->getCouponCode(),
                        'last_code_entered_date' => $this->dateFormatter->execute($order->getCreatedAt())
                    ];
                    $this->cache->save(
                        $this->serializer->serialize($couponData),
                        UserCouponInformation::CUSTOMER_COUPON_CACHE_TAG . '_' . $customer->getId()
                    );
                }
            } catch (LocalizedException $e) {
                $this->logger->critical($e->getMessage());
                return $order;
            }

            $this->service->updateUserRewardInfoCache($customer);
        }

        return $order;
    }
}

<?php

namespace Tan\EnhancedEcommerce\ViewModel;

use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tan\EnhancedEcommerce\Service\FormatDate;
use Psr\Log\LoggerInterface;

/**
 * Class UserCouponInformation
 * @package Tan\EnhancedEcommerce\ViewModel
 * @method string getLastCodeEntered()
 * @method string getLastCodeEnteredDate()
 * @method UserCouponInformation setLastCodeEntered() setLastCodeEntered($data)
 * @method UserCouponInformation setLastCodeEnteredDate() setLastCodeEnteredDate($data)
 */
class UserCouponInformation extends AbstractUserInformation
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    protected $customerData = [
        'last_code_entered' => '',
        'last_code_entered_date' => ''
    ];

    public function __construct(
        ScopeConfigInterface $config,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CacheInterface $cache,
        SerializerInterface $serializer,
        FormatDate $dateFormatter,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        SortOrderBuilder $sortOrderBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

        parent::__construct($config, $customerSession, $storeManager, $cache, $serializer, $dateFormatter, $logger, $data);
    }

    protected function getInfo()
    {
        $customer = $this->customerSession->getCustomer();
        $couponData = $this->cache->load(self::CUSTOMER_COUPON_CACHE_TAG . '_' . $customer->getId());
        if (!$couponData) {
            $data = ['last_code_entered' => '', 'last_code_entered_date' => ''];
            try {
                $sortOrder = $this->sortOrderBuilder->setField('created_at')
                    ->setDescendingDirection()
                    ->create();

                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('customer_id', $customer->getId())
                    ->addFilter('coupon_code', null, 'notnull')
                    ->setSortOrders([$sortOrder])
                    ->setPageSize(1)->create();

                $orders = $this->orderRepository->getList($searchCriteria)->getItems();

                if (count($orders)) {
                    $order = current($orders);
                    $data['last_code_entered'] = $order->getCouponCode();
                    $data['last_code_entered_date'] = $this->dateFormatter->execute($order->getCreatedAt());
                }
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
            }

            $couponData = $this->serializer->serialize($data);
            $this->cache->save($couponData, self::CUSTOMER_COUPON_CACHE_TAG . '_' . $customer->getId());
        }

        return $this->serializer->unserialize($couponData);
    }
}

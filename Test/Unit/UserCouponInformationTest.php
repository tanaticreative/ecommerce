<?php

namespace Tan\EnhancedEcommerce\Test\Unit;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tan\EnhancedEcommerce\Service\FormatDate;
use Tan\EnhancedEcommerce\ViewModel\UserCouponInformation;
use Tan\Newsletter\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserCouponInformationTest extends TestCase
{
    /**
     * @var MockObject| ScopeConfigInterface
     */
    private $configMock;

    /**
     * @var MockObject | CustomerSession
     */
    private $customerSessionMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var CouponRepositoryInterface|MockObject
     */
    private $couponRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var SortOrderBuilder|MockObject
     */
    private $sortOrderBuilderMock;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var MockObject
     */
    private $cacheMock;

    /**
     * @var MockObject|SerializerInterface
     */
    private $serializerMock;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var FormatDate
     */
    private $dateFormatter;

    /**
     * @var UserCouponInformation
     */
    private $viewModel;

    /**
     * @var MockObject | UserCouponInformation
     */
    private $viewModelMock;

    /**
     * @var SearchCriteriaInterface|MockObject
     */
    private $searchCriteriaMock;

    /**
     * @var OrderSearchResultInterface|MockObject
     */
    private $orderSearchResult;

    /**
     * @var SortOrder|MockObject
     */
    private $sortOrder;

    protected function setUp()
    {
        parent::setUp();
        $this->configMock = $this->createMock(ScopeConfigInterface::class);
        $this->customerSessionMock = $this->createMock(CustomerSession::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->couponRepositoryMock = $this->createMock(CouponRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $this->sortOrderBuilderMock = $this->createMock(SortOrderBuilder::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->orderSearchResult = $this->createMock(OrderSearchResultInterface::class);
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->dateFormatter = new FormatDate($this->loggerMock);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->sortOrder = $this->createMock(SortOrder::class);
    }

    /**
     * @return array
     */
    public function getDataSets(): array
    {
        return [
           'quest' => [false, null, null, null, ['optin_value' => Config::OPTOUT_LABEL]],
           'loggedIn_customer_without_cache' => [
               true,
               8090,
               false,
               null,
               ['last_code_entered' => 'FREEPRODUCT', 'last_code_entered_date' => '21/01/2021']
           ],
           'loggedIn_customer_cache_already_exist' => [
               true,
               8090,
               true,
               '{"last_code_entered":"FREEPRODUCT","last_code_entered_date":"21/01/2021"}',
               ['last_code_entered' => 'FREEPRODUCT', 'last_code_entered_date' => '21/01/2021']
           ]
        ];
    }

    /**
     * @dataProvider getDataSets
     * @param $isLoggedIn
     * @param $customerId
     * @param $isCacheExist
     * @param $cacheResponse
     * @param $result
     */
    public function testGetData($isLoggedIn, $customerId, $isCacheExist, $cacheResponse, $result)
    {
        $this->customerSessionMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn($isLoggedIn);

        if ($isLoggedIn) {
            $customerMock = $this->createMock(Customer::class);
            $customerMock->expects($this->any())
                ->method('getId')
                ->willReturn($customerId);

            $this->customerSessionMock->expects($this->once())
                ->method('getCustomer')
                ->willReturn($customerMock);

            $this->cacheMock->expects($this->any())
                ->method('load')
                ->with(UserCouponInformation::CUSTOMER_COUPON_CACHE_TAG . '_' . $customerId)
                ->willReturn($cacheResponse);

            if (!$isCacheExist) {
                $this->sortOrderBuilderMock->expects($this->once())->method('setField')
                    ->with('created_at')
                     ->willReturnSelf();

                $this->sortOrderBuilderMock->expects($this->once())
                    ->method('setDescendingDirection')
                    ->willReturnSelf();

                $this->sortOrderBuilderMock->expects($this->once())
                    ->method('create')
                    ->willReturn($this->sortOrder);

                $this->searchCriteriaBuilderMock->expects($this->exactly(2))->method('addFilter')
                    ->withConsecutive(
                        [$this->equalTo('customer_id'), $this->equalTo(8090)],
                        [$this->equalTo('coupon_code'), $this->equalTo(null), $this->equalTo('notnull')]
                    )
                    ->willReturnSelf();

                $this->searchCriteriaBuilderMock->expects($this->once())
                    ->method('setSortOrders')
                    ->with([$this->sortOrder])
                    ->willReturnSelf();

                $this->searchCriteriaBuilderMock->expects($this->once())->method('setPageSize')
                    ->with($this->equalTo(1))
                    ->willReturnSelf();

                $this->searchCriteriaBuilderMock->expects($this->once())->method('create')
                    ->willReturn($this->searchCriteriaMock);

                $orders = [];

                $order = $this->createMock(OrderInterface::class);
                $order->expects($this->once())
                        ->method('getCouponCode')
                        ->willReturn("FREEPRODUCT");

                $order->expects($this->once())
                    ->method('getCreatedAt')
                    ->willReturn('2021-01-21 06:03:58');

                $orders[] = $order;

                $this->orderSearchResult->expects($this->once())->method('getItems')->willReturn($orders);

                $this->orderRepositoryMock->expects($this->once())
                    ->method('getList')->with($this->searchCriteriaMock)->willReturn($this->orderSearchResult);

                $this->serializerMock->expects($this->once())->method('serialize')
                    ->with($result)
                    ->willReturn($cacheResponse);
            }

            $this->serializerMock->expects($this->any())->method('unserialize')
                ->with($cacheResponse)
                ->willReturn($result);

            $this->viewModel = new UserCouponInformation(
                $this->configMock,
                $this->customerSessionMock,
                $this->storeManagerMock,
                $this->cacheMock,
                $this->serializerMock,
                $this->dateFormatter,
                $this->loggerMock,
                $this->orderRepositoryMock,
                $this->sortOrderBuilderMock,
                $this->searchCriteriaBuilderMock
            );

            $this->assertEquals($result, $this->viewModel->getData());
        }
    }
}

<?php

namespace Tan\EnhancedEcommerce\Test\Unit;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\StoreManagerInterface;
use Tan\EnhancedEcommerce\Model\Service\UserInformationService;
use Tan\EnhancedEcommerce\Service\FormatDate;
use Tan\EnhancedEcommerce\ViewModel\UserRewardInformation;
use Tan\Newsletter\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserRewardInformationTest extends TestCase
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
     * @var MockObject|Config
     */
    private $subscriberConfigMock;

    /**
     * @var UserInformationService
     */
    private $service;

    /**
     * @var MockObject|UserRewardInformation
     */
    private $viewModel;

    /**
     * @var SubscriberFactory|MockObject
     */
    private $subscriberFactoryMock;

    protected function setUp()
    {
        parent::setUp();
        $this->configMock = $this->createMock(ScopeConfigInterface::class);
        $this->customerSessionMock = $this->createMock(CustomerSession::class);
        $this->storeManagerMock =  $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsite', 'getId'])
            ->getMockForAbstractClass();
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->dateFormatter = new FormatDate($this->loggerMock);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->subscriberConfigMock = $this->createMock(Config::class);
        $this->subscriberFactoryMock = $this->getMockBuilder(SubscriberFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
    }

    /**
     * @return array
     */
    public function getDataSets(): array
    {
        return [
            'quest' => [
                false,
                null,
                '{"last_point_movement":"","balance_expiration_date":"", "point_balance":""}',
                null,
                null,
                null,
                ["last_point_movement" => "", "balance_expiration_date" => "", "point_balance" => ""]
            ],
            'loggedIn_customer_with_point_balance' => [
                true,
                8090,
                '{"last_point_movement":"100","balance_expiration_date":"21/04/2021", "point_balance":"200"}',
                100,
                '2021-04-21 00:00:00',
                200,
                ["last_point_movement" => "100", "balance_expiration_date" => "21/04/2021", "point_balance" => "200"]
            ],
            'loggedIn_customer_without_point_balance' => [
                true,
                '{"last_point_movement":"","balance_expiration_date":"", "point_balance":""}',
                8090,
                null,
                null,
                0,
                ["last_point_movement" => "", "balance_expiration_date" => "", "point_balance" => "0"]
            ]
        ];
    }

    /**
     * @dataProvider getDataSets
     * @param $isLoggedIn
     * @param $customerId
     * @param $cacheResponse
     * @param $lastPointMovement
     * @param $balanceExpirationDate
     * @param $pointBalance
     * @param $result
     */
    public function testGetData($isLoggedIn, $customerId, $cacheResponse, $lastPointMovement, $balanceExpirationDate, $pointBalance, $result)
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

            $this->cacheMock->expects($this->once())
                ->method('load')
                ->with($this->equalTo(UserRewardInformation::CUSTOMER_REWARD_CACHE_TAG . '_' . $customerId))
                ->willReturn(false);

            $this->serializerMock->expects($this->once())->method('serialize')
                ->with($result)
                ->willReturn($cacheResponse);

            $rewardFactoryMock = $this->getMockBuilder(RewardFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();
            $rewardMock = $this->getMockBuilder(Reward::class)
                ->disableOriginalConstructor()
                ->setMethods([
                    'loadByCustomer',
                    'getId',
                    'setCustomer',
                    'setWebsiteId',
                    'getPointsBalance',
                    'getExpirationDate',
                    'getPointsDelta'
                ])->getMock();

            $rewardMock->expects($this->once())->method('setCustomer')
                ->willReturnSelf();

            $this->storeManagerMock->expects($this->once())->method('getWebsite')
                ->willReturnSelf();

            $rewardMock->expects($this->once())->method('setWebsiteId')
                ->willReturnSelf();

            $rewardMock->expects($this->once())->method('loadByCustomer')
                ->willReturnSelf();

            $rewardMock->expects($this->once())->method('getId')
                ->willReturn(1663);

            $rewardMock->expects($this->once())->method('getPointsBalance')
                ->willReturn($pointBalance);

            $rewardMock->expects($this->once())->method('getExpirationDate')
                ->willReturn($balanceExpirationDate);

            $rewardMock->expects($this->once())->method('getPointsDelta')
                ->willReturn($lastPointMovement);

            $rewardFactoryMock->expects($this->once())->method('create')
                ->willReturn($rewardMock);

            $this->service = new UserInformationService(
                $this->cacheMock,
                $this->subscriberFactoryMock,
                $this->subscriberConfigMock,
                $this->serializerMock,
                $rewardFactoryMock,
                $this->dateFormatter,
                $this->storeManagerMock,
                $this->loggerMock
            );

            $this->serializerMock->expects($this->any())->method('unserialize')
                ->with($cacheResponse)
                ->willReturn($result);

            $this->viewModel = new UserRewardInformation(
                $this->configMock,
                $this->customerSessionMock,
                $this->storeManagerMock,
                $this->cacheMock,
                $this->serializerMock,
                $this->dateFormatter,
                $this->service,
                $this->loggerMock
            );

            $this->assertEquals($result, $this->viewModel->getData());
        }
    }
}

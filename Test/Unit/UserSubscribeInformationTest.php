<?php

namespace Tan\EnhancedEcommerce\Test\Unit;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\StoreManagerInterface;
use Tan\EnhancedEcommerce\Model\Service\UserInformationService;
use Tan\EnhancedEcommerce\Service\FormatDate;
use Tan\EnhancedEcommerce\ViewModel\UserSubscribeInformation;
use Tan\Newsletter\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserSubscribeInformationTest extends TestCase
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
     * @var MockObject|Subscriber
     */
    private $subscriberMock;

    /**
     * @var MockObject|Config
     */
    private $subscriberConfigMock;

    /**
     * @var UserInformationService
     */
    private $service;

    /**
     * @var MockObject|UserSubscribeInformation
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
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->dateFormatter = new FormatDate($this->loggerMock);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->subscriberMock = $this->createMock(Subscriber::class);
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
            'quest' => [false, null, '{"optin_value":"' . Config::OPTOUT_LABEL . '"}', null, ['optin_value' => Config::OPTOUT_LABEL]],
            'loggedIn_customer_with_nestle_subscription' => [
                true,
                8090,
                '{"optin_value":"F"}',
                [1 => [1 => [0 => 'Email']]],
                ['optin_value' => 'F'],
            ],
            'loggedIn_customer_with_dolce_gusto_subscription' => [
                true,
                8090,
                '{"optin_value":"C"}',
                [2 => [1 => [0 => 'Email']]],
                ['optin_value' => 'C']
            ],
            'loggedIn_customer_with_both_channel_subscription' => [
                true,
                8090,
                '{"optin_value":"F,C"}',
                [1 => [1 => [0 => 'Email']], 2 => [1 => [0 => 'Email']]],
                ['optin_value' => 'F,C']
            ],
            'loggedIn_customer_no_subscription' => [
                true,
                8090,
                '{"optin_value":"' . Config::OPTOUT_LABEL . '"}',
                [],
                ['optin_value' => Config::OPTOUT_LABEL]
            ]
        ];
    }

    /**
     * @dataProvider getDataSets
     * @param $isLoggedIn
     * @param $customerId
     * @param $cacheResponse
     * @param $channels
     * @param $result
     */
    public function testGetData($isLoggedIn, $customerId, $cacheResponse, $channels, $result)
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
                ->with($this->equalTo(UserSubscribeInformation::CUSTOMER_SUBSCRIBE_CACHE_TAG . '_' . $customerId))
                ->willReturn(false);

            $this->serializerMock->expects($this->once())->method('serialize')
                ->with($result)
                ->willReturn($cacheResponse);

            $rewardFactoryMock = $this->createMock(RewardFactory::class);

            $subscriberMock = $this->getMockBuilder(Subscriber::class)
                ->disableOriginalConstructor()
                ->setMethods(['getSubscriberId', 'loadByCustomerId'])
                ->getMock();

            $subscriberMock->expects($this->once())->method('loadByCustomerId')
                ->with($customerId)
                ->willReturnSelf();

            $subscriberMock->expects($this->exactly(2))->method('getSubscriberId')
                ->willReturn(7299);

            $this->subscriberFactoryMock->expects($this->once())->method('create')
                ->willReturn($subscriberMock);

            $this->subscriberConfigMock->expects($this->once())->method('getNewsletterOptions')
                ->willReturn($channels);

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

            $this->viewModel = new UserSubscribeInformation(
                $this->configMock,
                $this->customerSessionMock,
                $this->storeManagerMock,
                $this->cacheMock,
                $this->serializerMock,
                $this->dateFormatter,
                $this->subscriberMock,
                $this->subscriberConfigMock,
                $this->loggerMock,
                $this->service
            );

            $this->assertEquals($result, $this->viewModel->getData());
        }
    }
}

<?php

namespace Tan\EnhancedEcommerce\Test\Unit;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Attribute;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tan\EnhancedEcommerce\Service\FormatDate;
use Tan\EnhancedEcommerce\ViewModel\UserBaseInformation;
use Tan\EnhancedEcommerce\ViewModel\UserRewardInformation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserBaseInformationTest extends TestCase
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
     * @var MockObject|UserRewardInformation
     */
    private $viewModel;

    /**
     * @var Header|MockObject
     */
    private $httpHeaderMock;

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
        $this->httpHeaderMock = $this->createMock(Header::class);
    }
    /**
     * @return array
     */
    public function getDataSets(): array
    {
        return [
            'quest' => [false, null,  [
                'ga_client_id' => 'UA-42397253-16',
                'user_id' => '',
                'login_status' => 'No',
                'login_type' => '',
                'registration_type' => '',
                'technical_id' => '',
                'first_name' => '',
                'salutation' => '',
                'gender' => '',
                'birthdate' => '',
                'account_creation_date' => '',
                'user_type' => '',
                'device_type' => '',
                'mobile_app_installed' => '',
            ]],
            'loggedIn_customer' => [
                true,
                8090,
                [
                    'ga_client_id' => 'UA-42397253-16',
                    'user_id' => 'BR8090',
                    'login_status' => 'Yes',
                    'login_type' => 'email',
                    'registration_type' => 'email',
                    'technical_id' => '8090',
                    'first_name' => 'zptdTy',
                    'salutation' => '',
                    'gender' => '',
                    'birthdate' => '10/10/2000',
                    'account_creation_date' => '11/12/2020',
                    'user_type' => 'Returning',
                    'device_type' => 'Desktop',
                    'mobile_app_installed' => 'Yes',
                ]
            ]
        ];
    }

    /**
     * @dataProvider getDataSets
     * @param $isLoggedIn
     * @param $customerId
     * @param $result
     */
    public function testGetData($isLoggedIn, $customerId, $result)
    {
        $this->customerSessionMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn($isLoggedIn);

        if ($isLoggedIn) {
            $websiteMock = $this->createMock(WebsiteInterface::class);
            $websiteMock->expects($this->once())->method('getCode')
                ->willReturn('tan_br');

            $this->storeManagerMock->expects($this->once())->method('getWebsite')
                ->willReturn($websiteMock);

            $customerMock = $this->getMockBuilder(Customer::class)
                ->disableOriginalConstructor()
                ->setMethods([
                    'getFirstname',
                    'getPrefix',
                    'getGender',
                    'getDob',
                    'getData',
                    'getAttribute',
                    'getCreatedAt'
                ])->getMock();

            $this->customerSessionMock->expects($this->exactly(2))->method('getCustomerId')
                ->willReturn($customerId);

            $customerMock->expects($this->once())->method('getFirstname')
                ->willReturn('zptdTy');

            $customerMock->expects($this->any())->method('getPrefix')
                ->willReturn('');

            $customerMock->expects($this->once())->method('getGender')
                ->willReturn(null);

            $attributeSourceMock = $this->createMock(Table::class);
            $attributeSourceMock->expects($this->any())->method('getOptionText')
                ->willReturn('');

            $attributeMock = $this->getMockBuilder(Attribute::class)
                ->disableOriginalConstructor()
                ->setMethods(['getSource'])->getMock();
            $attributeMock->expects($this->any())->method('getSource')
                ->willReturn($attributeSourceMock);

            $customerMock->expects($this->exactly(2))->method('getDob')
                ->willReturn('2000-10-10');

            $customerMock->expects($this->any())->method('getCreatedAt')
                ->willReturn('2020-12-11 16:43:46');

            $this->customerSessionMock->expects($this->once())->method('getCustomer')
                ->willReturn($customerMock);

            $this->httpHeaderMock->expects($this->once())->method('getHttpUserAgent')
                ->willReturn('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36');
        }

        $this->viewModel = new UserBaseInformation(
           $this->configMock,
           $this->customerSessionMock,
           $this->storeManagerMock,
           $this->cacheMock,
           $this->serializerMock,
           $this->dateFormatter,
           $this->loggerMock,
           $this->httpHeaderMock
       );
    }
}

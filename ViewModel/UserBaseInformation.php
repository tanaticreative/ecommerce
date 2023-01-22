<?php

namespace Tan\EnhancedEcommerce\ViewModel;

use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tan\EnhancedEcommerce\Service\FormatDate;
use Psr\Log\LoggerInterface;

/**
 * Class UserBaseInformation
 * @package Tan\EnhancedEcommerce\ViewModel
 * @method string getLoginStatus()
 * @method string getLoginType()
 * @method string getRegistrationType()
 * @method string getGaClientId()
 * @method string getTechnicalId()
 * @method string getUserId()
 * @method string getFirstName()
 * @method string getSalutation()
 * @method string getGender()
 * @method string getBirthdate()
 * @method string getAccountCreationDate()
 * @method string getDeviceType()
 * @method string getUserType()
 * @method string getMobileAppInstalled()
 * @method UserBaseInformation setLoginStatus() setLoginStatus($data)
 * @method UserBaseInformation setLoginType() setLoginType($data)
 * @method UserBaseInformation setRegistrationType() setRegistrationType($data)
 * @method UserBaseInformation setGaClientId() setGaClientId($data)
 * @method UserBaseInformation setTechnicalId() setTechnicalId($data)
 * @method UserBaseInformation setUserId() setUserId($data)
 * @method UserBaseInformation setFirstName() setFirstName($data)
 * @method UserBaseInformation setSalutation() setSalutation($data)
 * @method UserBaseInformation setGender() setGender($data)
 * @method UserBaseInformation setBirthdate() setBirthdate($data)
 * @method UserBaseInformation setAccountCreationDate() setAccountCreationDate($data)
 * @method UserBaseInformation setDeviceType() setDeviceType($data)
 * @method UserBaseInformation setUserType() setUserType($data)
 * @method UserBaseInformation setMobileAppInstalled() setMobileAppInstalled($data)
 */
class UserBaseInformation extends AbstractUserInformation
{
    /**
     * @var Header
     */
    private $httpHeader;

    protected $customerData = [
        'ga_client_id' => '',
        'user_id' => '',
        'login_status' => '',
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
        'mobile_app_installed' => ''
    ];

    public function __construct(
        ScopeConfigInterface $config,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CacheInterface $cache,
        SerializerInterface $serializer,
        FormatDate $dateFormatter,
        LoggerInterface $logger,
        Header $httpHeader,
        array $data = []
    ) {
        $this->httpHeader = $httpHeader;
        parent::__construct($config, $customerSession, $storeManager, $cache, $serializer, $dateFormatter, $logger, $data);
    }

    protected function init()
    {
        foreach ($this->customerData as $key => $value) {
            switch ($key) {
                case 'login_status':
                    $this->setLoginStatus('No');
                    break;
                case 'ga_client_id':
                    $this->setGaClientId($this->config->getValue('ec/api/google_gtm_ua'));
                    break;
                default:
                    $this->setData($key, $value);
                    break;
            }
        }
    }

    protected function getInfo()
    {
        $customer = $this->customerSession->getCustomer();
        return [
            'login_status' => 'Yes',
            'technical_id' => $this->customerSession->getCustomerId(),
            'login_type' => 'email',
            'registration_type' => 'email',
            'user_id' => $this->getUserIdValue(),
            'first_name' => $customer->getFirstname(),
            'salutation' => !empty($customer->getPrefix()) ? $customer->getPrefix() : '',
            'gender' => !empty($customer->getGender()) ?
                $customer->getAttribute('gender')->getSource()->getOptionText($customer->getGender()) : '',
            'birthdate' => !empty($customer->getDob()) ? $this->dateFormatter->execute($customer->getDob()) : '',
            'account_creation_date' => $this->dateFormatter->execute($customer->getCreatedAt()),
            'device_type' => $this->isMobile() ? 'Mobile' : 'Desktop',
            'user_type' => $this->isNew($customer) ? 'New' : 'Returning',
            'mobile_app_installed' => $customer->getData('app_first_login_date') ? 'Yes' : 'No'
        ];
    }

    /**
     * @return string
     */
    protected function getUserIdValue()
    {
        $websiteCode = '';
        try {
            $websiteCode = strtoupper(str_replace('tan_', '', $this->storeManager->getWebsite()->getCode()));
        } catch (LocalizedException $e) {
            $this->logger->warning($e->getMessage());
        }

        return $websiteCode . $this->customerSession->getCustomerId();
    }

    /**
     * @param $customer
     * @return bool
     */
    public function isNew($customer)
    {
        $isNew = false;
        try {
            $now = new \DateTime('now');
            $creationDate = new \DateTime($customer->getCreatedAt());
            $difference = $now->diff($creationDate);
            $isNew = ($difference->days * 24 + $difference->h) < 24;
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        return $isNew;
    }

    /**
     * @return bool
     */
    public function isMobile()
    {
        $userAgent = $this->httpHeader->getHttpUserAgent();
        return \Zend_Http_UserAgent_Mobile::match($userAgent, $_SERVER);
    }
}

<?php

namespace Tan\EnhancedEcommerce\Plugin\Newsletter\Controller\Manage;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResponseInterface;
use Tan\EnhancedEcommerce\Model\Service\UserInformationService;
use Tan\Newsletter\Plugin\Newsletter\Controller\Manage\Save as SourceSave;
use Psr\Log\LoggerInterface;

/**
 * Class Save
 * @package Tan\EnhancedEcommerce\Plugin\Newsletter\Controller\Manage
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Save
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UserInformationService
     */
    private $service;

    public function __construct(
        CustomerSession $customerSession,
        CacheInterface $cache,
        UserInformationService $service,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->service = $service;
    }

    /**
     * @param SourceSave $subject
     * @param ResponseInterface $result
     * @return ResponseInterface
     */
    public function afterAfterExecute(SourceSave $subject, $result)
    {
        $customerId = $this->customerSession->getCustomerId();
        $this->service->updateUserSubscribeInfoCache($customerId);

        return $result;
    }
}

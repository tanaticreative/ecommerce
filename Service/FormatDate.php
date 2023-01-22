<?php

namespace Tan\EnhancedEcommerce\Service;

use Psr\Log\LoggerInterface;

class FormatDate
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute($date)
    {
        if (is_null($date)) {
            $date = '';
        }
        try {
            if (!empty($date)) {
                $dateObj = new \DateTime($date);
                $date = $dateObj->format('d/m/Y');
            }
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        return $date;
    }
}

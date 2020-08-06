<?php
/**
 * Copyright © Wolfsellers All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WolfSellers\EnableDisableTfa\Plugin\Backend\Magento\TwoFactorAuth\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\TwoFactorAuth\Observer\ControllerActionPredispatch as MagentoControllerActionPredispatch;

class ControllerActionPredispatch
{
    const TWOFACTOR_AUTH_ENABLED = "twofactorauth/general/enabled";

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ControllerActionPredispatch constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {

        $this->scopeConfig = $scopeConfig;
    }

    public function aroundExecute(
        MagentoControllerActionPredispatch $subject,
        callable $proceed,
        Observer $observer
    ) {
        if($this->scopeConfig->isSetFlag(self::TWOFACTOR_AUTH_ENABLED)) {
            $proceed($observer);
        }
    }
}


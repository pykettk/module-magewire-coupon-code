<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCouponCode\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Coupon implements ArgumentInterface
{
    /** @var CheckoutSession */
    private CheckoutSession $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return bool
     */
    public function hasCouponCode(): bool
    {
        try {
            return $this->checkoutSession->getQuote() && $this->checkoutSession->getQuote()->hasCouponCode();
        } catch (NoSuchEntityException | LocalizedException $e) {
            return false;
        }
    }
}

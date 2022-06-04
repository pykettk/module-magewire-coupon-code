<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCouponCode\Magewire;

use Magento\Checkout\Helper\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepository;
use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Model\CouponFactory;
use Magewirephp\Magewire\Component;

class Coupon extends Component
{
    const BROWSER_EVENT_RELOAD_CART_TOTALS = 'reload-cart-totals';

    /** @var CheckoutSession */
    private CheckoutSession $checkoutSession;

    /** @var QuoteRepository */
    private QuoteRepository $quoteRepository;

    /** @var CouponFactory */
    private CouponFactory $couponFactory;

    /** Magewire Component Properties */
    public string $couponCode = '';
    public bool $isCouponApplied = false;

    /**
     * @param CheckoutSession $checkoutSession
     * @param QuoteRepository $quoteRepository
     * @param CouponFactory $couponFactory
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        QuoteRepository $quoteRepository,
        CouponFactory $couponFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->couponFactory = $couponFactory;
    }

    /**
     * Set initial component state.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->getQuote() && $couponCode = $this->getQuote()->getCouponCode()) {
            $this->couponCode = $couponCode;
            $this->isCouponApplied = true;
        }
    }

    /**
     * Apply the coupon code to the current quote.
     *
     * @return void
     */
    public function apply(): void
    {
        if (!$this->isCouponCodeValid()) {
            $this->dispatchErrorMessage(__('Invalid coupon code'));

            return;
        }

        if (!($quote = $this->getQuote())) {
            return;
        }

        $quote->setCouponCode($this->couponCode);
        $this->quoteRepository->save($quote);
        $this->isCouponApplied = true;

        $this->dispatchSuccessMessage(__('Successfully applied coupon: %1', $this->couponCode));
        $this->dispatchBrowserEvent(self::BROWSER_EVENT_RELOAD_CART_TOTALS);
    }

    /**
     * Remove the coupon code from the current quote.
     *
     * @return void
     */
    public function remove(): void
    {
        if (!($quote = $this->getQuote())) {
            return;
        }

        $quote->setCouponCode('');
        $this->quoteRepository->save($quote);

        $this->dispatchSuccessMessage(__('Successfully removed coupon: %1', $this->couponCode));
        $this->dispatchBrowserEvent(self::BROWSER_EVENT_RELOAD_CART_TOTALS);

        $this->reset(['couponCode', 'isCouponApplied']);
    }

    /**
     * Attempt to fetch the current quote.
     *
     * @return CartInterface|null
     */
    private function getQuote(): ?CartInterface
    {
        try {
            return $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->dispatchErrorMessage(__('Could not retrieve current quote.'));

            return null;
        }
    }

    /**
     * @return bool
     */
    private function isCouponCodeValid(): bool
    {
        /** @var CouponInterface $coupon */
        $coupon = $this->couponFactory->create();
        $coupon->loadByCode($this->couponCode);

        if ($coupon->getId()
            && $coupon->getCode()
            && strlen($this->couponCode) <= Cart::COUPON_CODE_MAX_LENGTH
        ) {
            return true;
        }

        return false;
    }
}

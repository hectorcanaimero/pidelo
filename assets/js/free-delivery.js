/**
 * Free Delivery by Minimum Amount
 * Applies free delivery when order subtotal meets the minimum configured amount
 */
(function () {
  'use strict';

  /**
   * Check if free delivery should be applied based on subtotal
   * @param {number} subtotal - The order subtotal
   * @returns {boolean} - Whether free delivery should be applied
   */
  function shouldApplyFreeDelivery(subtotal) {
    if (!window.mydStoreInfo || !window.mydStoreInfo.freeDelivery) {
      console.log('[Free Delivery] No store info available');
      return false;
    }

    const { enabled, minimumAmount } = window.mydStoreInfo.freeDelivery;

    const shouldApply = enabled && minimumAmount > 0 && subtotal >= minimumAmount;

    console.log('[Free Delivery] Check:', {
      enabled: enabled,
      minimumAmount: minimumAmount,
      subtotal: subtotal,
      shouldApply: shouldApply,
    });

    return shouldApply;
  }

  /**
   * Show free delivery message
   */
  function showFreeDeliveryMessage() {
    const deliveryPriceElement = document.querySelector('.myd-cart__payment-amount-delivery .myd-cart__payment-amount-info-number');
    if (!deliveryPriceElement) return;

    // Check if message already exists
    if (deliveryPriceElement.querySelector('.myd-free-delivery-badge')) {
      return;
    }

    const message = window.mydStoreInfo?.messages?.freeDelivery || 'Delivery gratis!';
    const badge = document.createElement('span');
    badge.className = 'myd-free-delivery-badge';
    badge.textContent = ' ' + message;
    badge.style.cssText = 'color: #4caf50; font-weight: bold; font-size: 0.85em; margin-left: 5px;';

    deliveryPriceElement.appendChild(badge);
  }

  /**
   * Hide free delivery message
   */
  function hideFreeDeliveryMessage() {
    const badge = document.querySelector('.myd-free-delivery-badge');
    if (badge) {
      badge.remove();
    }
  }

  /**
   * Override MydOrder.calculateTotal to apply free delivery logic
   */
  function overrideCalculateTotal() {
    if (!window.MydOrder) {
      setTimeout(overrideCalculateTotal, 500);
      return;
    }

    // Store original method if not already stored
    if (!window.MydOrder._originalCalculateTotal) {
      window.MydOrder._originalCalculateTotal = window.MydOrder.calculateTotal.bind(window.MydOrder);
    }

    // Override with new method
    window.MydOrder.calculateTotal = function () {
      // Call original method first
      const result = window.MydOrder._originalCalculateTotal();

      // Get current subtotal
      const subtotal = this.subtotal || 0;

      // Check if free delivery should be applied
      if (shouldApplyFreeDelivery(subtotal)) {
        // Set delivery price to 0
        this.delivery = 0;

        // Recalculate total
        this.total = subtotal + this.delivery - this.discount;

        // Show free delivery message
        showFreeDeliveryMessage();
      } else {
        // Hide message if subtotal is below minimum
        hideFreeDeliveryMessage();
      }

      return result;
    };

    console.log('[Free Delivery] Override applied to MydOrder.calculateTotal');
  }

  /**
   * Override MydOrder.delivery.set to prevent overriding free delivery
   */
  function overrideDeliverySet() {
    if (!window.MydOrder || !window.MydOrder.delivery) {
      setTimeout(overrideDeliverySet, 500);
      return;
    }

    // Store original method
    if (!window.MydOrder.delivery._originalSet) {
      window.MydOrder.delivery._originalSet = window.MydOrder.delivery.set.bind(window.MydOrder.delivery);
    }

    // Override set method
    window.MydOrder.delivery.set = function (deliveryPrice) {
      // Check if free delivery is active
      const subtotal = window.MydOrder.subtotal || 0;

      if (shouldApplyFreeDelivery(subtotal)) {
        // Force delivery to 0 if free delivery is active
        deliveryPrice = 0;
      }

      // Call original method
      return window.MydOrder.delivery._originalSet(deliveryPrice);
    };

    console.log('[Free Delivery] Override applied to MydOrder.delivery.set');
  }

  /**
   * Force recalculation of totals with free delivery applied
   */
  function forceRecalculate() {
    if (!window.MydOrder) return;

    // Trigger calculateTotal
    if (window.MydOrder.calculateTotal) {
      console.log('[Free Delivery] Forcing recalculation');
      window.MydOrder.calculateTotal();
    }

    // Also update the DOM if needed
    const subtotal = window.MydOrder.subtotal || 0;
    if (shouldApplyFreeDelivery(subtotal)) {
      // Update delivery price in DOM
      const deliveryPriceElement = document.querySelector('.myd-cart__payment-amount-delivery .myd-cart__payment-amount-info-number');
      if (deliveryPriceElement) {
        const currencySymbol = window.mydStoreInfo?.currency?.symbol || '$';
        deliveryPriceElement.textContent = currencySymbol + ' 0.00';
      }
    }
  }

  /**
   * Monitor cart updates to reapply free delivery logic
   */
  function monitorCartUpdates() {
    // Listen to custom events that might be triggered when cart updates
    if (window.Myd && window.Myd.on) {
      window.Myd.on('MydCartUpdated', function () {
        setTimeout(forceRecalculate, 100);
      });
    }

    // Observe changes to cart items
    const observer = new MutationObserver(function (mutations) {
      let shouldRecalculate = false;

      mutations.forEach(function (mutation) {
        if (mutation.target.classList && mutation.target.classList.contains('myd-cart__products')) {
          shouldRecalculate = true;
        }
      });

      if (shouldRecalculate) {
        setTimeout(forceRecalculate, 100);
      }
    });

    const cartElement = document.querySelector('.myd-cart__products');
    if (cartElement) {
      observer.observe(cartElement, {
        childList: true,
        subtree: true,
      });
    }

    // Monitor delivery method changes
    const deliveryMethodInputs = document.querySelectorAll('input[name="myd-delivery-method"]');
    deliveryMethodInputs.forEach(function (input) {
      input.addEventListener('change', function () {
        console.log('[Free Delivery] Delivery method changed');
        setTimeout(forceRecalculate, 200);
      });
    });

    // Monitor when checkout tab changes
    const checkoutTabs = document.querySelectorAll('.myd-checkout__nav-item');
    checkoutTabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        setTimeout(forceRecalculate, 300);
      });
    });
  }

  /**
   * Initialize free delivery functionality
   */
  function init() {
    // Only proceed if free delivery is enabled
    if (!window.mydStoreInfo || !window.mydStoreInfo.freeDelivery || !window.mydStoreInfo.freeDelivery.enabled) {
      console.log('[Free Delivery] Feature is disabled');
      return;
    }

    console.log('[Free Delivery] Initializing...', {
      minimumAmount: window.mydStoreInfo.freeDelivery.minimumAmount,
      currency: window.mydStoreInfo.currency?.symbol,
    });

    overrideCalculateTotal();
    overrideDeliverySet();
    monitorCartUpdates();

    // Force initial calculation after a delay to ensure everything is loaded
    setTimeout(function () {
      console.log('[Free Delivery] Initial calculation');
      forceRecalculate();
    }, 1000);
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

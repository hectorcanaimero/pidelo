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
      return false;
    }

    const { enabled, minimumAmount } = window.mydStoreInfo.freeDelivery;

    return enabled && minimumAmount > 0 && subtotal >= minimumAmount;
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
   * Monitor cart updates to reapply free delivery logic
   */
  function monitorCartUpdates() {
    // Listen to custom events that might be triggered when cart updates
    if (window.Myd && window.Myd.on) {
      window.Myd.on('MydCartUpdated', function () {
        if (window.MydOrder && window.MydOrder.calculateTotal) {
          window.MydOrder.calculateTotal();
        }
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

      if (shouldRecalculate && window.MydOrder && window.MydOrder.calculateTotal) {
        window.MydOrder.calculateTotal();
      }
    });

    const cartElement = document.querySelector('.myd-cart__products');
    if (cartElement) {
      observer.observe(cartElement, {
        childList: true,
        subtree: true,
      });
    }
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
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/**
 * Free Delivery by Minimum Amount
 * Applies free delivery when order subtotal meets the minimum configured amount
 */
(function () {
  'use strict';

  /**
   * Show free delivery message
   */
  function showFreeDeliveryMessage() {
    const deliveryFeeValue = document.querySelector('#myd-cart-payment-delivery-fee-value');
    if (!deliveryFeeValue) {
      console.log('[Free Delivery] Delivery fee value element not found');
      return;
    }

    // Check if message already exists
    if (deliveryFeeValue.querySelector('.myd-free-delivery-badge')) {
      return;
    }

    const message = window.mydStoreInfo?.messages?.freeDelivery || 'Delivery gratis!';
    const badge = document.createElement('div');
    badge.className = 'myd-free-delivery-badge';
    badge.textContent = message;
    badge.style.cssText = 'color: #4caf50; font-weight: bold; font-size: 0.9em; margin-top: 4px;';

    deliveryFeeValue.appendChild(badge);
    console.log('[Free Delivery] Badge added to DOM');
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
   * Update delivery price in DOM
   */
  function updateDeliveryPriceInDOM(isFree) {
    const deliveryElement = document.querySelector('#myd-cart-payment-delivery-fee-value .myd-cart__summary-price-usd');

    if (!deliveryElement) {
      console.log('[Free Delivery] Delivery price element not found in DOM');
      return;
    }

    if (isFree) {
      const currencySymbol = window.mydStoreInfo?.currency?.symbol || '$';
      const decimalSeparator = window.mydStoreInfo?.currency?.decimalSeparator || '.';
      const decimalNumbers = window.mydStoreInfo?.currency?.decimalNumbers || 2;
      const freeText = currencySymbol + ' 0' + decimalSeparator + '0'.repeat(decimalNumbers);

      console.log('[Free Delivery] Updating delivery price to:', freeText);
      deliveryElement.textContent = freeText;

      // Also update MydOrder.delivery if it exists
      if (window.MydOrder) {
        window.MydOrder.delivery = 0;
        console.log('[Free Delivery] Updated MydOrder.delivery to 0');
      }
    }
  }

  /**
   * Check cart response data for free delivery flag
   */
  function checkFreeDeliveryFromResponse(data) {
    if (!data || typeof data !== 'object') return;

    console.log('[Free Delivery] Checking cart response:', {
      subtotal: data.subtotal,
      delivery_price: data.delivery_price,
      free_delivery_applied: data.free_delivery_applied,
    });

    // The backend already calculates free delivery
    // We just need to show the UI badge if it's applied
    if (data.free_delivery_applied === true) {
      console.log('[Free Delivery] Free delivery applied by backend!');

      // Try immediate update first
      updateDeliveryPriceInDOM(true);
      showFreeDeliveryMessage();

      // Also try with delays to catch any late DOM updates
      setTimeout(() => {
        updateDeliveryPriceInDOM(true);
        showFreeDeliveryMessage();
      }, 50);

      setTimeout(() => {
        updateDeliveryPriceInDOM(true);
        showFreeDeliveryMessage();
      }, 200);
    } else {
      hideFreeDeliveryMessage();
    }
  }

  /**
   * Intercept fetch requests to monitor cart API responses
   */
  function interceptFetchRequests() {
    const originalFetch = window.fetch;

    window.fetch = function (...args) {
      return originalFetch.apply(this, args).then((response) => {
        // Clone response so we can read it
        const clonedResponse = response.clone();

        // Check if this is a cart-related request
        const url = args[0];
        if (typeof url === 'string' && (url.includes('admin-ajax.php') || url.includes('/cart'))) {
          clonedResponse
            .json()
            .then((data) => {
              // Check for cart totals in response
              if (data && (data.subtotal !== undefined || data.free_delivery_applied !== undefined)) {
                checkFreeDeliveryFromResponse(data);
              }
            })
            .catch(() => {
              // Not JSON or parsing failed, ignore
            });
        }

        return response;
      });
    };

    console.log('[Free Delivery] Fetch interceptor installed');
  }

  /**
   * Override XMLHttpRequest to monitor AJAX cart responses
   */
  function interceptXHRRequests() {
    const originalOpen = XMLHttpRequest.prototype.open;
    const originalSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (method, url, ...args) {
      this._url = url;
      return originalOpen.apply(this, [method, url, ...args]);
    };

    XMLHttpRequest.prototype.send = function (...args) {
      this.addEventListener('load', function () {
        // Check if this is a cart-related AJAX request
        if (this._url && this._url.includes('admin-ajax.php')) {
          try {
            const response = JSON.parse(this.responseText);
            console.log('[Free Delivery] XHR Response received:', response);

            // Check both response.data and response directly
            if (response && response.data) {
              checkFreeDeliveryFromResponse(response.data);
            } else {
              checkFreeDeliveryFromResponse(response);
            }
          } catch (e) {
            // Not JSON, ignore
            console.log('[Free Delivery] XHR response not JSON:', e);
          }
        }
      });

      return originalSend.apply(this, args);
    };

    console.log('[Free Delivery] XHR interceptor installed');
  }


  /**
   * Initialize free delivery functionality
   */
  function init() {
    // Wait for mydStoreInfo to be available
    if (typeof window.mydStoreInfo === 'undefined') {
      // Try to use backup from wp_localize_script
      if (typeof window.mydStoreInfoBackup !== 'undefined') {
        console.log('[Free Delivery] Using backup mydStoreInfo');
        window.mydStoreInfo = window.mydStoreInfoBackup;
      } else {
        console.log('[Free Delivery] Waiting for mydStoreInfo...');
        setTimeout(init, 100);
        return;
      }
    }

    // Only proceed if free delivery is enabled
    if (!window.mydStoreInfo.freeDelivery || !window.mydStoreInfo.freeDelivery.enabled) {
      console.log('[Free Delivery] Feature is disabled', {
        freeDelivery: window.mydStoreInfo.freeDelivery,
      });
      return;
    }

    console.log('[Free Delivery] Initializing...', {
      minimumAmount: window.mydStoreInfo.freeDelivery.minimumAmount,
      currency: window.mydStoreInfo.currency?.symbol,
    });

    // Install interceptors to monitor cart API responses
    interceptFetchRequests();
    interceptXHRRequests();

    console.log('[Free Delivery] Initialization complete');
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

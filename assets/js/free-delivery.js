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
      return;
    }

    if (isFree) {
      const currencySymbol = window.mydStoreInfo?.currency?.symbol || '$';
      const decimalSeparator = window.mydStoreInfo?.currency?.decimalSeparator || '.';
      const decimalNumbers = window.mydStoreInfo?.currency?.decimalNumbers || 2;
      const freeText = currencySymbol + ' 0' + decimalSeparator + '0'.repeat(decimalNumbers);

      deliveryElement.textContent = freeText;

      // Remove or update conversion display for delivery fee
      const deliveryContainer = document.querySelector('#myd-cart-payment-delivery-fee-value .myd-cart__summary-price-container');
      if (deliveryContainer) {
        // Remove the conversion element if it exists
        const conversionElement = deliveryContainer.querySelector('.myd-currency-conversion');
        if (conversionElement) {
          conversionElement.remove();
        }
      }

      // Also update MydOrder.delivery if it exists
      if (window.MydOrder) {
        window.MydOrder.delivery = 0;

        // Recalculate total
        const subtotal = window.MydOrder.subtotal || 0;
        const discount = window.MydOrder.discount || 0;
        const newTotal = subtotal - discount;
        window.MydOrder.total = newTotal;

        // Update total in DOM
        const totalElement = document.querySelector('#myd-cart-payment-total-value .myd-cart__summary-price-usd');
        if (totalElement) {
          const totalFormatted = newTotal.toFixed(decimalNumbers).replace('.', decimalSeparator);
          const totalText = currencySymbol + ' ' + totalFormatted;
          totalElement.textContent = totalText;
        }

        // Update total conversion if it exists
        const totalContainer = document.querySelector('#myd-cart-payment-total-value .myd-cart__summary-price-container');
        if (totalContainer && window.mydStoreInfo?.conversion) {
          updateConversionDisplay(totalContainer, newTotal);
        }
      }
    }
  }

  /**
   * Update conversion display for a given container and amount
   */
  function updateConversionDisplay(container, amount) {
    if (!container || !window.mydStoreInfo?.conversion) {
      return;
    }

    const conversionEnabled = window.mydStoreInfo.conversion.enabled;
    const conversionRate = window.mydStoreInfo.conversion.rate;

    if (!conversionEnabled || !conversionRate || amount <= 0) {
      // Remove conversion if amount is 0 or conversion is disabled
      const conversionElement = container.querySelector('.myd-currency-conversion');
      if (conversionElement) {
        conversionElement.remove();
      }
      return;
    }

    // Calculate converted amount
    const convertedAmount = amount * conversionRate;
    const currencyCode = window.mydStoreInfo.conversion.currencyCode || 'VEF';
    const currencySymbol = window.mydStoreInfo.conversion.currencySymbol || 'Bs';

    // Format the converted amount (Venezuelan format: . for thousands, , for decimals)
    // First, separate integer and decimal parts
    const parts = convertedAmount.toFixed(2).split('.');
    const integerPart = parts[0];
    const decimalPart = parts[1];

    // Add thousands separator (.) to integer part
    const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    // Combine with comma as decimal separator
    const formattedConverted = formattedInteger + ',' + decimalPart;

    // Check if conversion element exists
    let conversionElement = container.querySelector('.myd-currency-conversion');

    if (!conversionElement) {
      // Create new conversion element
      conversionElement = document.createElement('div');
      conversionElement.className = 'myd-currency-conversion';
      container.appendChild(conversionElement);
    }

    // Update conversion HTML
    conversionElement.innerHTML = `
      <span class="myd-converted-price myd-vef-price">
        ${currencySymbol} ${formattedConverted} <small>${currencyCode}</small>
      </span>
    `;
  }

  // Store current free delivery state
  let freeDeliveryActive = false;

  /**
   * Apply free delivery to current DOM
   */
  function applyFreeDeliveryToDOM() {
    if (!freeDeliveryActive) return;

    updateDeliveryPriceInDOM(true);
    showFreeDeliveryMessage();
  }

  /**
   * Watch for DOM changes and reapply free delivery
   */
  function watchForDOMChanges() {
    const targetNode = document.body;

    const observer = new MutationObserver((mutations) => {
      // Check if the delivery fee element was added/modified
      const deliveryElement = document.querySelector('#myd-cart-payment-delivery-fee-value');

      if (deliveryElement && freeDeliveryActive) {
        // Small delay to ensure rendering is complete
        setTimeout(applyFreeDeliveryToDOM, 10);
      }
    });

    observer.observe(targetNode, {
      childList: true,
      subtree: true,
    });
  }

  /**
   * Check cart response data for free delivery flag
   */
  function checkFreeDeliveryFromResponse(data) {
    if (!data || typeof data !== 'object') return;

    // Update global state
    freeDeliveryActive = data.free_delivery_applied === true;

    if (freeDeliveryActive) {
      // Apply immediately
      applyFreeDeliveryToDOM();

      // Also try with delay in case DOM hasn't updated yet
      setTimeout(applyFreeDeliveryToDOM, 100);
      setTimeout(applyFreeDeliveryToDOM, 300);
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

            // Check both response.data and response directly
            if (response && response.data) {
              checkFreeDeliveryFromResponse(response.data);
            } else {
              checkFreeDeliveryFromResponse(response);
            }
          } catch (e) {
            // Not JSON, ignore
          }
        }
      });

      return originalSend.apply(this, args);
    };
  }


  /**
   * Initialize free delivery functionality
   */
  function init() {
    // Wait for mydStoreInfo to be available
    if (typeof window.mydStoreInfo === 'undefined') {
      // Try to use backup from wp_localize_script
      if (typeof window.mydStoreInfoBackup !== 'undefined') {
        window.mydStoreInfo = window.mydStoreInfoBackup;
      } else {
        setTimeout(init, 100);
        return;
      }
    }

    // Only proceed if free delivery is enabled
    if (!window.mydStoreInfo.freeDelivery || !window.mydStoreInfo.freeDelivery.enabled) {
      return;
    }

    // Install interceptors to monitor cart API responses
    interceptFetchRequests();
    interceptXHRRequests();

    // Watch for DOM changes to reapply free delivery
    watchForDOMChanges();
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/**
 * Skip Payment for Order in Store
 * Simplifies payment page when order type is "order-in-store"
 * - Hides payment integration options
 * - Auto-selects "Pago en local" (cash/upon delivery)
 * - Hides payment receipt upload
 * myd-cart__checkout-payment
 */
(function () {
  'use strict';

  function initSkipPayment() {
    const paymentSection = document.getElementById('myd-cart-payment');

    if (!paymentSection) {
      console.log('[Skip Payment] Payment section not found');
      return;
    }

    const skipPaymentEnabled = paymentSection.getAttribute('data-skip-payment-in-store') === 'yes';

    console.log('[Skip Payment] Enabled:', skipPaymentEnabled);

    if (!skipPaymentEnabled) {
      return;
    }

    let currentOrderType = null;
    let savedOrderId = null; // Save order ID before order.min.js overwrites

    // Function to update current order type and payment UI
    function updateOrderType(orderType) {
      currentOrderType = orderType;
      console.log('[Skip Payment] Order type changed to:', orderType);

      updatePaymentUI();
    }

    // Function to update payment UI based on order type
    function updatePaymentUI() {
      // Wait for payment page to be loaded
      setTimeout(function () {
        const paymentNav = document.querySelector('.myd-cart__nav-payment');

        // Only update UI when payment page is active
        if (!paymentNav || !paymentNav.classList.contains('myd-cart__nav--active')) {
          return;
        }

        const paymentIntegration = document.querySelector('details[data-type="payment-integration"]');
        const uponDelivery = document.querySelector('details[data-type="upon-delivery"]');
        // const receiptUpload = document.querySelector('.myd-cart__payment-receipt-wrapper');
        const receiptUpload = document.querySelector('.myd-cart__checkout-payment');

        if (currentOrderType === 'order-in-store') {
          console.log('[Skip Payment] Configuring payment for order-in-store');

          // Hide payment integration option
          if (paymentIntegration) {
            paymentIntegration.style.display = 'none';
            paymentIntegration.removeAttribute('open');
          }

          // Show and open upon delivery option
          if (uponDelivery) {
            uponDelivery.style.display = 'block';
            uponDelivery.setAttribute('open', 'open');
          }

          // Hide receipt upload
          if (receiptUpload) {
            receiptUpload.style.display = 'none';

            // Remove required attribute from receipt input
            const receiptInput = receiptUpload.querySelector('input[type="file"]');
            if (receiptInput) {
              receiptInput.removeAttribute('required');
              receiptInput.removeAttribute('aria-required');
            }
          }

          // Auto-select first payment option (should be cash/pago móvil)
          const firstPaymentOption = document.querySelector('.myd-cart__payment-input-option');
          if (firstPaymentOption && !firstPaymentOption.checked) {
            firstPaymentOption.checked = true;

            // Trigger change event in case there are listeners
            const changeEvent = new Event('change', { bubbles: true });
            firstPaymentOption.dispatchEvent(changeEvent);
          }

          console.log('[Skip Payment] Payment configured for in-store order');
        } else {
          console.log('[Skip Payment] Restoring normal payment UI');

          // Restore payment integration option
          if (paymentIntegration) {
            paymentIntegration.style.display = 'block';
          }

          // Restore receipt upload
          if (receiptUpload) {
            receiptUpload.style.display = 'block';

            // Restore required attribute if needed
            const receiptInput = receiptUpload.querySelector('input[type="file"]');
            if (receiptInput && receiptInput.dataset.originallyRequired === 'true') {
              receiptInput.setAttribute('required', 'required');
              receiptInput.setAttribute('aria-required', 'true');
            }
          }
        }
      }, 100);
    }

    // Watch for payment nav activation
    function watchPaymentNav() {
      const paymentNav = document.querySelector('.myd-cart__nav-payment');

      if (!paymentNav) {
        console.log('[Skip Payment] Payment nav not found');
        return;
      }

      const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            const target = mutation.target;

            if (
              target.classList.contains('myd-cart__nav-payment') &&
              target.classList.contains('myd-cart__nav--active')
            ) {
              console.log('[Skip Payment] Payment page activated');
              updatePaymentUI();
            }
          }
        });
      });

      observer.observe(paymentNav, { attributes: true, attributeFilter: ['class'] });
      console.log('[Skip Payment] Watching payment nav for activation');
    }

    // Listen for clicks on checkout options
    const checkoutOptions = document.querySelectorAll('.myd-cart__checkout-option');

    console.log('[Skip Payment] Found checkout options:', checkoutOptions.length);

    checkoutOptions.forEach(function (option) {
      option.addEventListener('click', function () {
        const orderType = this.getAttribute('data-type');
        console.log('[Skip Payment] Clicked on checkout option:', orderType);
        updateOrderType(orderType);
      });
    });

    // Also listen for clicks on the inner divs (for better UX)
    const checkoutOptionInnerDivs = document.querySelectorAll('.myd-cart__checkout-option > div[data-type]');

    checkoutOptionInnerDivs.forEach(function (innerDiv) {
      innerDiv.addEventListener('click', function () {
        const orderType = this.getAttribute('data-type');
        console.log('[Skip Payment] Clicked on inner div:', orderType);
        updateOrderType(orderType);
      });
    });

    // Check initial state on page load
    const activeOption = document.querySelector('.myd-cart__checkout-option--active');
    if (activeOption) {
      const initialOrderType = activeOption.getAttribute('data-type');
      console.log('[Skip Payment] Initial order type:', initialOrderType);
      updateOrderType(initialOrderType);
    }

    // Start watching payment nav
    watchPaymentNav();

    // Override placePayment to skip for order-in-store and go directly to finished
    function overridePlacePaymentMethod() {
      if (!window.MydOrder || !window.MydOrder.placePayment) {
        setTimeout(overridePlacePaymentMethod, 200);
        return;
      }

      const originalPlacePayment = window.MydOrder.placePayment.bind(window.MydOrder);

      window.MydOrder.placePayment = async function () {
        console.log('[Skip Payment] placePayment called, order type:', currentOrderType);

        // If order-in-store with skip payment, go directly to finished page
        if (currentOrderType === 'order-in-store' && skipPaymentEnabled) {
          console.log('[Skip Payment] Skipping placePayment for order-in-store, going to finished');

          // Save order ID for later use
          if (window.MydOrder && window.MydOrder.id) {
            savedOrderId = window.MydOrder.id;
            console.log('[Skip Payment] Order ID saved:', savedOrderId);
          }

          // Update order number immediately
          const finishedOrderNumber = document.getElementById('finished-order-number');
          if (finishedOrderNumber && savedOrderId) {
            finishedOrderNumber.innerText = '#' + savedOrderId;
            console.log('[Skip Payment] Order number updated to:', savedOrderId);
          }

          // Clear cart and go to finished page
          if (window.MydOrder && window.MydOrder.clear) {
            window.MydOrder.clear();
          }

          if (window.MydCheckout && window.MydCheckout.goTo) {
            window.MydCheckout.goTo('orderComplete');
          }

          // Hide next button
          if (window.MydCheckout && window.MydCheckout.elements && window.MydCheckout.elements.nextButton) {
            window.MydCheckout.elements.nextButton.style.display = 'none';
          }

          // Trigger order complete event
          if (window.Myd && window.Myd.newEvent) {
            window.Myd.newEvent('MydOrderComplete', { orderTotal: this.total });
          }

          // Remove loading animation
          if (window.Myd && window.Myd.removeLoadingAnimation) {
            window.Myd.removeLoadingAnimation('.myd-cart__button-text');
          }

          return true;
        }

        // Otherwise, call original method
        return originalPlacePayment();
      };

      console.log('[Skip Payment] placePayment method overridden');
    }

    // Initialize the override after other scripts load
    setTimeout(overridePlacePaymentMethod, 500);
  }

  // Try to initialize immediately if DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSkipPayment);
  } else {
    // DOM is already ready, wait a bit for other scripts to load
    setTimeout(initSkipPayment, 100);
  }
})();

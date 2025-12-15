/**
 * Fix for cart item removal
 * Ensures product removal from cart works correctly
 */
(function () {
  'use strict';

  /**
   * Use event delegation on parent container
   */
  function setupEventDelegation() {
    // Get the cart products container (parent that doesn't get re-rendered)
    const cartContainer = document.querySelector('.myd-cart__products');

    if (!cartContainer) {
      // Container not ready yet, try again
      setTimeout(setupEventDelegation, 100);
      return;
    }

    // Remove any existing delegated listeners by cloning and replacing
    const newCartContainer = cartContainer.cloneNode(true);
    cartContainer.parentNode.replaceChild(newCartContainer, cartContainer);

    // Add delegated event listener
    newCartContainer.addEventListener('click', function (e) {
      // Check if clicked element is or contains the remove button
      const removeButton = e.target.closest('.myd-cart__products-action');

      if (removeButton) {
        e.preventDefault();
        e.stopPropagation();

        const productKey = removeButton.dataset.productKey;

        if (productKey !== undefined && window.MydCart) {
          window.MydCart.removeItem(productKey);
        }
      }
    });
  }

  /**
   * Initialize on DOM ready
   */
  function init() {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', setupEventDelegation);
    } else {
      setupEventDelegation();
    }
  }

  init();
})();

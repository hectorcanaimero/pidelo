/**
 * Fix for cart item removal
 * Ensures product removal from cart works correctly
 */
(function () {
  'use strict';

  /**
   * Attach remove item handlers to cart items
   */
  function attachRemoveHandlers() {
    const removeButtons = document.querySelectorAll('.myd-cart__products-action');

    removeButtons.forEach((button) => {
      // Remove any existing listeners by cloning
      const newButton = button.cloneNode(true);
      button.parentNode.replaceChild(newButton, button);

      // Add fresh listener
      newButton.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const productKey = this.dataset.productKey;

        if (productKey !== undefined && window.MydCart) {
          window.MydCart.removeItem(productKey);
        }
      });
    });
  }

  /**
   * Initialize on DOM ready
   */
  function init() {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', attachRemoveHandlers);
    } else {
      attachRemoveHandlers();
    }

    // Reattach handlers when cart updates
    window.addEventListener('MydCartUpdated', function () {
      setTimeout(attachRemoveHandlers, 100);
    });
  }

  init();
})();

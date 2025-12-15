/**
 * Fix for cart item removal
 * Ensures product removal from cart works correctly
 */
(function () {
  'use strict';

  /**
   * Use event delegation on document body (always available)
   */
  function setupEventDelegation() {
    // Use document.body for delegation (never gets re-rendered)
    document.body.addEventListener('click', function (e) {
      // Check if clicked element is or contains the remove button
      const removeButton = e.target.closest('.myd-cart__products-action');

      if (removeButton) {
        e.preventDefault();
        e.stopPropagation();

        const productKey = removeButton.dataset.productKey;

        if (productKey !== undefined && window.MydCart) {
          console.log('[Cart Fix] Removing item with key:', productKey);
          window.MydCart.removeItem(productKey);
        }
      }
    });

    console.log('[Cart Fix] Event delegation installed on document.body');
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

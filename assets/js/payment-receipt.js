/**
 * Payment Receipt Upload Handler
 * Handles the upload of payment receipt files during checkout
 */
(function () {
  'use strict';

  // Storage key for persisting payment method selection
  const STORAGE_KEY_PAYMENT_METHOD = 'myd_selected_payment_method';

  /**
   * Verifica si un elemento es visible en el DOM (funciona en mobile y desktop)
   */
  function isElementVisible(element) {
    if (!element) return false;

    // Múltiples checks para mayor compatibilidad en mobile
    const style = window.getComputedStyle(element);
    const isDisplayed = style.display !== 'none';
    const isVisible = style.visibility !== 'hidden';
    const hasOpacity = parseFloat(style.opacity) > 0;
    const hasHeight = element.offsetHeight > 0 || element.getBoundingClientRect().height > 0;

    // Check si está dentro de un <details> abierto
    let parent = element.parentElement;
    let isInOpenDetails = true;

    while (parent) {
      if (parent.tagName === 'DETAILS' && !parent.hasAttribute('open')) {
        isInOpenDetails = false;
        break;
      }
      parent = parent.parentElement;
    }

    return isDisplayed && isVisible && hasOpacity && hasHeight && isInOpenDetails;
  }

  /**
   * Guarda el método de pago seleccionado en sessionStorage
   */
  function saveSelectedPaymentMethod(value) {
    if (value) {
      try {
        sessionStorage.setItem(STORAGE_KEY_PAYMENT_METHOD, value);
      } catch (e) {
        console.warn('Could not save payment method to sessionStorage:', e);
      }
    }
  }

  /**
   * Restaura el método de pago seleccionado desde sessionStorage
   */
  function restoreSelectedPaymentMethod() {
    try {
      const savedMethod = sessionStorage.getItem(STORAGE_KEY_PAYMENT_METHOD);
      if (savedMethod) {
        const radioInput = document.querySelector(
          `.myd-cart__payment-input-option[value="${savedMethod}"]`
        );
        if (radioInput && !radioInput.checked) {
          radioInput.checked = true;
          // Disparar evento change para actualizar la UI
          radioInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    } catch (e) {
      console.warn('Could not restore payment method from sessionStorage:', e);
    }
  }

  /**
   * Limpia el método de pago guardado (se llama al completar el pedido)
   */
  function clearSavedPaymentMethod() {
    try {
      sessionStorage.removeItem(STORAGE_KEY_PAYMENT_METHOD);
    } catch (e) {
      console.warn('Could not clear payment method from sessionStorage:', e);
    }
  }

  /**
   * Inicializa los event listeners para los radio buttons de pago
   */
  function initPaymentMethodPersistence() {
    const paymentRadios = document.querySelectorAll('.myd-cart__payment-input-option');

    paymentRadios.forEach((radio) => {
      radio.addEventListener('change', function () {
        if (this.checked) {
          saveSelectedPaymentMethod(this.value);
        }
      });
    });

    // Event listener para el file input - restaurar selección después de adjuntar
    const fileInput = document.getElementById('input-payment-receipt');
    if (fileInput) {
      fileInput.addEventListener('change', function () {
        // Pequeño delay para asegurar que el DOM se actualice
        setTimeout(restoreSelectedPaymentMethod, 100);
      });
    }

    // Restaurar selección al cargar la página
    restoreSelectedPaymentMethod();

    // También restaurar cuando se abren/cierran los <details> de métodos de pago
    const paymentDetails = document.querySelectorAll('.myd-cart__payment-options-container details');
    paymentDetails.forEach((details) => {
      details.addEventListener('toggle', function () {
        if (this.open) {
          setTimeout(restoreSelectedPaymentMethod, 50);
        }
      });
    });
  }

  /**
   * Override the placePayment method to support file uploads
   */
  function overridePlacePaymentMethod() {
    if (!window.MydOrder) {
      setTimeout(overridePlacePaymentMethod, 500);
      return;
    }

    // Store the original method
    const originalPlacePayment = window.MydOrder.placePayment.bind(window.MydOrder);

    // Override with new method that supports files
    window.MydOrder.placePayment = async function () {
      // Restaurar el método de pago antes de procesar
      restoreSelectedPaymentMethod();

      // Get the payment type first
      const paymentType = this.payment.get().type;

      // Get the payment receipt file input
      const fileInput = document.getElementById('input-payment-receipt');
      const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
      const isFileInputVisible = isElementVisible(fileInput);

      if (fileInput) {
        console.log('[DEBUG] File input details:', {
          paymentType: paymentType,
          display: window.getComputedStyle(fileInput).display,
          visibility: window.getComputedStyle(fileInput).visibility,
          opacity: window.getComputedStyle(fileInput).opacity,
          offsetParent: fileInput.offsetParent,
          offsetHeight: fileInput.offsetHeight,
          files: fileInput.files.length,
          isVisible: isFileInputVisible,
        });
      }

      // Validación: Solo requerir comprobante si el pago es "upon-delivery" y el campo está visible
      if (paymentType === 'upon-delivery' && isFileInputVisible && !hasFile) {
        // Detener el loading del botón
        window.Myd.removeLoadingAnimation('.myd-cart__button-text');

        // Mostrar alerta nativa (mejor UX en mobile)
        alert(
          '⚠️ Comprobante de Pago Obligatorio\n\nPor favor, adjunta tu comprobante de pago para continuar con el pedido.',
        );

        // Hacer scroll al campo y hacer focus (con timeout para mobile)
        setTimeout(() => {
          fileInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
          fileInput.focus();
        }, 100);

        return false;
      }

      // If no file (but not required), use original method
      if (!hasFile) {
        return originalPlacePayment();
      }

      // With file, use FormData

      const data = {
        id: this.id,
        payment: this.payment.get(),
      };

      const formData = new FormData();
      formData.append('action', 'myd_order_place_payment');
      formData.append('sec', ajax_object.order_nonce);
      formData.append('data', JSON.stringify(data));
      formData.append('payment_receipt', fileInput.files[0]);

      try {
        const response = await fetch(ajax_object.ajax_url, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
        });

        if (!response.ok) {
          window.Myd.removeLoadingAnimation('.myd-cart__button-text');
          window.Myd.notificationBar('error', 'Error to make the fetch request. Contact the store support.');
          return false;
        }

        const responseData = await response.json();

        // Manejar respuesta de error del backend (incluyendo validación de comprobante)
        if (responseData.success === false || responseData.error) {
          window.Myd.removeLoadingAnimation('.myd-cart__button-text');
          const errorMessage =
            responseData.data?.message ||
            responseData.error?.error_message ||
            responseData.error?.[0] ||
            'Error al procesar el pago';
          window.Myd.notificationBar('error', errorMessage);
          return false;
        }

        const finishedOrderNumber = document.getElementById('finished-order-number');
        const whatsappLink = document.querySelector('.myd-cart__finished-whatsapp > a');
        const trackOrderLink = document.querySelector('.myd-cart__finished-track-order > a');

        finishedOrderNumber.innerText = responseData.id;
        whatsappLink.href = responseData.whatsappLink;
        trackOrderLink.href = responseData.orderTrackLink;

        window.MydOrder.clear();
        window.MydCheckout.goTo('orderComplete');
        window.MydCheckout.elements.nextButton.style.display = 'none';
        window.Myd.newEvent('MydOrderComplete', { orderTotal: this.total });

        // Limpiar el método de pago guardado después de completar el pedido
        clearSavedPaymentMethod();

        if (mydStoreInfo.autoRedirect === 'yes') {
          window.location.href = responseData.whatsappLink;
        }

        return true;
      } catch (error) {
        window.Myd.removeLoadingAnimation('.myd-cart__button-text');
        window.Myd.notificationBar('error', 'Error al procesar el pago: ' + error.message);
        return false;
      }
    };
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      overridePlacePaymentMethod();
      initPaymentMethodPersistence();
    });
  } else {
    overridePlacePaymentMethod();
    initPaymentMethodPersistence();
  }
})();

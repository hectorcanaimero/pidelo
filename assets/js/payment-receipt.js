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
   * Muestra el indicador de carga del archivo
   */
  function showUploadProgress() {
    const wrapper = document.querySelector('.myd-cart__payment-receipt-wrapper');
    if (!wrapper) return;

    // Remover indicador previo si existe
    const existingProgress = wrapper.querySelector('.myd-receipt-upload-progress');
    if (existingProgress) {
      existingProgress.remove();
    }

    const progressHTML = `
      <div class="myd-receipt-upload-progress" style="margin-top: 10px; padding: 10px; background: #e3f2fd; border-radius: 4px; border-left: 3px solid #2196f3;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
          <div class="myd-receipt-spinner" style="width: 16px; height: 16px; border: 2px solid #2196f3; border-top-color: transparent; border-radius: 50%; animation: myd-spin 0.8s linear infinite;"></div>
          <span style="font-size: 0.9em; color: #1976d2; font-weight: 500;">Cargando archivo...</span>
        </div>
        <div style="width: 100%; height: 4px; background: #bbdefb; border-radius: 2px; overflow: hidden;">
          <div class="myd-receipt-progress-bar" style="width: 0%; height: 100%; background: #2196f3; transition: width 0.3s ease; animation: myd-progress 1.5s ease-in-out infinite;"></div>
        </div>
      </div>
    `;

    wrapper.insertAdjacentHTML('beforeend', progressHTML);

    // Agregar estilos de animación si no existen
    if (!document.getElementById('myd-receipt-animations')) {
      const style = document.createElement('style');
      style.id = 'myd-receipt-animations';
      style.textContent = `
        @keyframes myd-spin {
          to { transform: rotate(360deg); }
        }
        @keyframes myd-progress {
          0% { width: 0%; }
          50% { width: 70%; }
          100% { width: 100%; }
        }
      `;
      document.head.appendChild(style);
    }
  }

  /**
   * Oculta el indicador de carga del archivo
   */
  function hideUploadProgress() {
    const progress = document.querySelector('.myd-receipt-upload-progress');
    if (progress) {
      progress.remove();
    }
  }

  /**
   * Muestra mensaje de error en la carga del archivo
   */
  function showUploadError(message) {
    const wrapper = document.querySelector('.myd-cart__payment-receipt-wrapper');
    if (!wrapper) return;

    // Remover mensajes previos
    hideUploadProgress();
    const existingError = wrapper.querySelector('.myd-receipt-upload-error');
    if (existingError) {
      existingError.remove();
    }

    const errorHTML = `
      <div class="myd-receipt-upload-error" style="margin-top: 10px; padding: 12px; background: #ffebee; border-radius: 4px; border-left: 3px solid #f44336;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 1.2em;">❌</span>
          <span style="font-size: 0.9em; color: #c62828; font-weight: 500;">${message}</span>
        </div>
      </div>
    `;

    wrapper.insertAdjacentHTML('beforeend', errorHTML);

    // Auto-remover después de 5 segundos
    setTimeout(() => {
      const errorEl = wrapper.querySelector('.myd-receipt-upload-error');
      if (errorEl) {
        errorEl.remove();
      }
    }, 5000);
  }

  /**
   * Muestra mensaje de éxito en la carga del archivo
   */
  function showUploadSuccess(filename) {
    const wrapper = document.querySelector('.myd-cart__payment-receipt-wrapper');
    if (!wrapper) return;

    hideUploadProgress();

    const existingSuccess = wrapper.querySelector('.myd-receipt-upload-success');
    if (existingSuccess) {
      existingSuccess.remove();
    }

    const successHTML = `
      <div class="myd-receipt-upload-success" style="margin-top: 10px; padding: 12px; background: #e8f5e9; border-radius: 4px; border-left: 3px solid #4caf50;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 1.2em;">✅</span>
          <span style="font-size: 0.9em; color: #2e7d32; font-weight: 500;">Archivo cargado: ${filename}</span>
        </div>
      </div>
    `;

    wrapper.insertAdjacentHTML('beforeend', successHTML);
  }

  /**
   * Muestra mensaje de advertencia si falta el comprobante
   */
  function showMissingReceiptWarning() {
    const wrapper = document.querySelector('.myd-cart__payment-receipt-wrapper');
    if (!wrapper) return;

    const existingWarning = wrapper.querySelector('.myd-receipt-missing-warning');
    if (existingWarning) {
      existingWarning.classList.add('myd-receipt-shake');
      setTimeout(() => existingWarning.classList.remove('myd-receipt-shake'), 500);
      return;
    }

    const warningHTML = `
      <div class="myd-receipt-missing-warning myd-receipt-shake" style="margin-top: 10px; padding: 12px; background: #fff3e0; border-radius: 4px; border-left: 3px solid #ff9800;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 1.2em;">⚠️</span>
          <span style="font-size: 0.9em; color: #e65100; font-weight: 500;">Por favor, adjunta tu comprobante de pago para continuar</span>
        </div>
      </div>
    `;

    wrapper.insertAdjacentHTML('beforeend', warningHTML);

    // Agregar animación de shake si no existe
    if (!document.getElementById('myd-receipt-shake-animation')) {
      const style = document.createElement('style');
      style.id = 'myd-receipt-shake-animation';
      style.textContent = `
        @keyframes myd-shake {
          0%, 100% { transform: translateX(0); }
          25% { transform: translateX(-5px); }
          75% { transform: translateX(5px); }
        }
        .myd-receipt-shake {
          animation: myd-shake 0.3s ease-in-out;
        }
      `;
      document.head.appendChild(style);
    }
  }

  /**
   * Inicializa el manejo del input de archivo
   */
  function initFileInputHandler() {
    const fileInput = document.getElementById('input-payment-receipt');
    if (!fileInput) return;

    // Limpiar mensajes cuando se selecciona un archivo
    fileInput.addEventListener('change', function () {
      const wrapper = document.querySelector('.myd-cart__payment-receipt-wrapper');
      if (!wrapper) return;

      // Remover mensajes previos
      const warning = wrapper.querySelector('.myd-receipt-missing-warning');
      const error = wrapper.querySelector('.myd-receipt-upload-error');
      if (warning) warning.remove();
      if (error) error.remove();

      if (this.files && this.files.length > 0) {
        const file = this.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];

        // Validar tipo de archivo
        if (!allowedTypes.includes(file.type)) {
          showUploadError('Tipo de archivo no permitido. Solo JPG, PNG, GIF o PDF.');
          this.value = '';
          return;
        }

        // Validar tamaño
        if (file.size > maxSize) {
          showUploadError('El archivo es demasiado grande. Máximo 5MB.');
          this.value = '';
          return;
        }

        // Mostrar éxito
        showUploadSuccess(file.name);
      }
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

      // Validación: Verificar que se haya seleccionado un método de pago si el campo está visible
      if (paymentType === 'upon-delivery' && isFileInputVisible) {
        const paymentData = this.payment.get();
        const paymentOption = paymentData.option;

        // Verificar también si hay un radio button seleccionado
        const selectedRadio = document.querySelector('.myd-cart__payment-input-option:checked');

        console.log('[DEBUG] Payment validation:', {
          paymentType: paymentType,
          paymentOption: paymentOption,
          paymentData: paymentData,
          hasFile: hasFile,
          isFileInputVisible: isFileInputVisible,
          selectedRadio: selectedRadio,
          selectedRadioValue: selectedRadio ? selectedRadio.value : null,
        });

        // Si no hay archivo y el comprobante es obligatorio
        if (!hasFile) {
          window.Myd.removeLoadingAnimation('.myd-cart__button-text');

          showMissingReceiptWarning();

          alert(
            '⚠️ Comprobante de Pago Obligatorio\n\nPor favor, adjunta tu comprobante de pago para continuar con el pedido.',
          );

          setTimeout(() => {
            fileInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            fileInput.focus();
          }, 100);

          return false;
        }

        // Si hay archivo pero no se seleccionó método de pago
        // Verificamos tanto el objeto payment como el DOM directamente
        const hasPaymentMethod = (paymentOption && paymentOption.trim() !== '') || selectedRadio;

        if (hasFile && !hasPaymentMethod) {
          window.Myd.removeLoadingAnimation('.myd-cart__button-text');

          showUploadError('Debes seleccionar un método de pago antes de continuar');

          alert(
            '⚠️ Método de Pago Requerido\n\nPor favor, selecciona un método de pago (Efectivo, Transferencia, etc.) para continuar.',
          );

          // Hacer scroll a las opciones de pago
          const paymentOptions = document.querySelector('.myd-cart__payment-options-container');
          if (paymentOptions) {
            setTimeout(() => {
              paymentOptions.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
          }

          return false;
        }
      }

      // If no file (but not required), use original method
      if (!hasFile) {
        return originalPlacePayment();
      }

      // With file, use FormData
      showUploadProgress();

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

        hideUploadProgress();

        if (!response.ok) {
          window.Myd.removeLoadingAnimation('.myd-cart__button-text');
          showUploadError('Error al conectar con el servidor. Por favor, intenta de nuevo.');
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
          showUploadError(errorMessage);
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
        hideUploadProgress();
        window.Myd.removeLoadingAnimation('.myd-cart__button-text');
        const errorMsg = 'Error al procesar el pago: ' + error.message;
        showUploadError(errorMsg);
        window.Myd.notificationBar('error', errorMsg);
        return false;
      }
    };
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      overridePlacePaymentMethod();
      initPaymentMethodPersistence();
      initFileInputHandler();
    });
  } else {
    overridePlacePaymentMethod();
    initPaymentMethodPersistence();
    initFileInputHandler();
  }
})();

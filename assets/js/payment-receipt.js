/**
 * Payment Receipt Upload Handler
 * Handles the upload of payment receipt files during checkout
 */
(function() {
    'use strict';

    console.log('Payment Receipt JS loaded');

    /**
     * Override the placePayment method to support file uploads
     */
    function overridePlacePaymentMethod() {
        if (!window.MydOrder) {
            console.error('MydOrder not found, retrying in 500ms');
            setTimeout(overridePlacePaymentMethod, 500);
            return;
        }

        console.log('Overriding MydOrder.placePayment method');

        // Store the original method
        const originalPlacePayment = window.MydOrder.placePayment.bind(window.MydOrder);

        // Override with new method that supports files
        window.MydOrder.placePayment = async function() {
            console.log('Custom placePayment called');

            // Get the payment receipt file input
            const fileInput = document.getElementById('input-payment-receipt');
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
            const isFileInputVisible = fileInput && fileInput.offsetParent !== null;

            console.log('Has file:', hasFile);
            console.log('File input visible:', isFileInputVisible);

            // Validación: Si el campo de comprobante está visible (obligatorio) y no hay archivo
            if (isFileInputVisible && !hasFile) {
                console.log('Validation failed: Payment receipt is required');
                window.Myd.removeLoadingAnimation('.myd-cart__button-text');
                window.Myd.notificationBar('error', 'El comprobante de pago es obligatorio. Por favor, adjunta tu comprobante para continuar.');

                // Hacer scroll al campo y hacer focus
                fileInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                fileInput.focus();

                return false;
            }

            // If no file (but not required), use original method
            if (!hasFile) {
                console.log('No file, using original method');
                return originalPlacePayment();
            }

            // With file, use FormData
            console.log('File detected:', fileInput.files[0].name);

            const data = {
                id: this.id,
                payment: this.payment.get()
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
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    window.Myd.removeLoadingAnimation('.myd-cart__button-text');
                    window.Myd.notificationBar('error', 'Error to make the fetch request. Contact the store support.');
                    throw new Error(response.status);
                }

                const responseData = await response.json();

                // Manejar respuesta de error del backend (incluyendo validación de comprobante)
                if (responseData.success === false || responseData.error) {
                    window.Myd.removeLoadingAnimation('.myd-cart__button-text');
                    const errorMessage = responseData.data?.message || responseData.error?.error_message || responseData.error?.[0] || 'Error al procesar el pago';
                    window.Myd.notificationBar('error', errorMessage);
                    throw new Error(errorMessage);
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

                if (mydStoreInfo.autoRedirect === 'yes') {
                    window.location.href = responseData.whatsappLink;
                }

                return true;
            } catch (error) {
                console.error('Problem with fetch request:', error.message);
                return false;
            }
        };

        console.log('placePayment method overridden successfully');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', overridePlacePaymentMethod);
    } else {
        overridePlacePaymentMethod();
    }

})();

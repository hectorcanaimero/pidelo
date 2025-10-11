/**
 * Evolution API Admin JavaScript
 *
 * Funcionalidad para integración Evolution API en admin
 *
 * @package MydPro
 * @since 2.3.0
 */

(function ($) {
  'use strict';

  // Variables globales
  let qrRefreshInterval = null;

  /**
   * Esperar a que el DOM esté listo
   */
  $(document).ready(function () {
    // Verificar que mydEvolutionData existe
    if (typeof mydEvolutionData === 'undefined') {
      console.error('[Evolution API] mydEvolutionData not loaded');
      return;
    }

    /**
     * =========================================================================
     * FUNCIONES AUXILIARES (Definidas primero)
     * =========================================================================
     */

    /**
     * Actualizar indicador de estado de conexión
     */
    function updateStatusIndicator(isConnected) {
      const $indicator = $('#evolution-status-indicator');
      const $banner = $('.myd-evolution-status-banner');
      const $btnShowQr = $('#btn-show-qr-section');

      if (isConnected) {
        $indicator.addClass('connected');
        $banner.addClass('connected');
        $indicator.find('.status-text').text(mydEvolutionData.i18n.connected);
        $btnShowQr.hide();
      } else {
        $indicator.removeClass('connected');
        $banner.removeClass('connected');
        $indicator.find('.status-text').text(mydEvolutionData.i18n.disconnected);
        $btnShowQr.show();
      }
    }

    /**
     * Mostrar QR Code en pantalla
     */
    function displayQrCode(base64Image) {
      const $display = $('#qr-code-display');
      $display.html('<img src="' + base64Image + '" alt="QR Code" />');
    }

    /**
     * Resetear display de QR
     */
    function resetQrDisplay() {
      $('#qr-code-display').html(
        '<div class="qr-placeholder">' +
          '<span class="dashicons dashicons-smartphone"></span>' +
          '<p>' +
          mydEvolutionData.i18n.clickToGenerate +
          '</p>' +
          '</div>',
      );
      $('#btn-create-instance').show();
      $('#btn-refresh-qr').hide();
      $('#btn-logout-instance').hide();
    }

    /**
     * Mostrar mensaje de estado en sección QR
     */
    function showQrStatus(type, message) {
      const $status = $('#qr-status-message');
      $status.removeClass('success error info').addClass(type).text(message);
    }

    /**
     * Detener auto-refresh del QR
     */
    function stopQrRefresh() {
      if (qrRefreshInterval) {
        clearInterval(qrRefreshInterval);
        qrRefreshInterval = null;
      }
    }

    /**
     * Obtener QR Code desde API
     */
    function getQrCode() {
      $.ajax({
        url: mydEvolutionData.ajaxurl,
        type: 'POST',
        data: {
          action: 'myd_evolution_get_qr_code',
          nonce: mydEvolutionData.nonce,
        },
        success: function (response) {
          if (response.success && response.data.qr_code) {
            displayQrCode(response.data.qr_code);
            $('#btn-create-instance').hide();
            $('#btn-refresh-qr').show();
            $('#btn-logout-instance').show();
            showQrStatus('info', mydEvolutionData.i18n.scanQr);
          } else {
            // Si no hay QR disponible, probablemente ya está conectado
            stopQrRefresh();
            showQrStatus('success', mydEvolutionData.i18n.connected);
            // Verificar estado de conexión
            setTimeout(function () {
              $('#myd-evolution-test-connection').trigger('click');
            }, 1000);
          }
        },
        error: function () {
          showQrStatus('error', mydEvolutionData.i18n.qrError);
          stopQrRefresh();
        },
      });
    }

    /**
     * Iniciar auto-refresh del QR
     */
    function startQrRefresh() {
      // Mostrar sección de QR
      $('#qr-connection-section').fadeIn();

      // Primera carga inmediata
      getQrCode();

      // Auto-refresh cada 5 segundos
      stopQrRefresh(); // Detener cualquier intervalo previo
      qrRefreshInterval = setInterval(function () {
        getQrCode();
      }, 5000);
    }

    /**
     * Iniciar polling para verificar cuando se conecta la instancia
     */
    function startConnectionPolling() {
      let pollCount = 0;
      const maxPolls = 60; // 5 minutos (cada 5 segundos)

      const pollingInterval = setInterval(function () {
        pollCount++;

        // Verificar estado de conexión
        $.ajax({
          url: mydEvolutionData.ajaxurl,
          type: 'POST',
          data: {
            action: 'myd_evolution_check_status',
            nonce: mydEvolutionData.nonce,
          },
          success: function (response) {
            if (response.success && response.data.is_open) {
              // ¡Conectado!
              clearInterval(pollingInterval);
              stopQrRefresh();
              showQrStatus('success', '✓ WhatsApp conectado correctamente');
              updateStatusIndicator(true);

              // Ocultar sección QR después de 2 segundos
              setTimeout(function () {
                $('#qr-connection-section').fadeOut();
                resetQrDisplay();
              }, 2000);

              // Recargar página para actualizar todo
              setTimeout(function () {
                location.reload();
              }, 3000);
            }
          },
          error: function () {
            console.log('[Evolution API] Polling error');
          },
        });

        // Detener después de max intentos
        if (pollCount >= maxPolls) {
          clearInterval(pollingInterval);
          showQrStatus('info', 'El código QR sigue activo. Escanéalo cuando estés listo.');
        }
      }, 5000); // Cada 5 segundos
    }

    /**
     * =========================================================================
     * EVENT HANDLERS (Test de Conexión)
     * =========================================================================
     */

    /**
     * Test de Conexión en Settings
     */
    console.log('[Evolution API] Button found:', $('#myd-evolution-test-connection').length);

    $('#myd-evolution-test-connection').on('click', function () {
      console.log('[Evolution API] Test connection clicked');
      const $btn = $(this);
      const $result = $('#test-connection-result');
      const originalHtml = $btn.html();

      $btn
        .prop('disabled', true)
        .html('<span class="dashicons dashicons-update spin"></span> ' + mydEvolutionData.i18n.testing);
      $result.removeClass('success error').html('');

      $.ajax({
        url: mydEvolutionData.ajaxurl,
        type: 'POST',
        data: {
          action: 'myd_evolution_test_connection',
          nonce: mydEvolutionData.nonce,
        },
        success: function (response) {
          if (response.success) {
            $result.addClass('success').html('<span class="dashicons dashicons-yes"></span> ' + response.data.message);
            updateStatusIndicator(true);
            // Si está conectado, ocultar sección QR y detener refresh
            $('#qr-connection-section').fadeOut();
            stopQrRefresh();
          } else {
            $result.addClass('error').html('<span class="dashicons dashicons-no"></span> ' + response.data.message);
            updateStatusIndicator(false);
            // Si no está conectado, mostrar botón pero NO abrir QR automáticamente
          }
        },
        error: function (xhr, status, error) {
          $result
            .addClass('error')
            .html('<span class="dashicons dashicons-no"></span> ' + mydEvolutionData.i18n.connectionError);
          updateStatusIndicator(false);
        },
        complete: function () {
          $btn.prop('disabled', false).html(originalHtml);
        },
      });
    });

    /**
     * Mostrar sección QR al hacer clic en botón del banner
     */
    $('#btn-show-qr-section').on('click', function () {
      $('#qr-connection-section').fadeIn();
      // Scroll suave hacia la sección
      $('html, body').animate(
        {
          scrollTop: $('#qr-connection-section').offset().top - 50,
        },
        500,
      );
    });

    /**
     * =========================================================================
     * QR Code Management
     * =========================================================================
     */

    /**
     * Auto-Setup: Crear instancia automáticamente y mostrar QR
     *
     * Implementa el flujo del diagrama Mermaid:
     * Usuario → Sistema → Crear → Conectar → Verificar → Retornar
     */
    $('#btn-create-instance').on('click', function () {
      console.log('mydEvolutionData', mydEvolutionData);
      const $btn = $(this);
      const originalHtml = $btn.html();

      $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + 'Configurando...');

      showQrStatus('info', 'Iniciando configuración automática...');

      $.ajax({
        url: mydEvolutionData.ajaxurl,
        type: 'POST',
        data: {
          action: 'myd_evolution_auto_setup',
          nonce: mydEvolutionData.nonce,
        },
        success: function (response) {
          console.log('[Evolution API] Auto-setup response:', response);

          if (response.success) {
            const data = response.data;

            // Si hay QR code, mostrarlo
            if (data.qr_code) {
              displayQrCode(data.qr_code);
              $('#btn-create-instance').hide();
              $('#btn-refresh-qr').show();
              $('#btn-logout-instance').show();
              showQrStatus('success', data.message);

              // Iniciar polling para verificar cuando se conecte
              startConnectionPolling();
            } else if (data.status === 'connected') {
              // Ya estaba conectado
              showQrStatus('success', data.message);
              updateStatusIndicator(true);
              $('#qr-connection-section').fadeOut();
            } else {
              showQrStatus('info', data.message);
            }

            $btn.prop('disabled', false).html(originalHtml);
          } else {
            showQrStatus('error', response.data.message || 'Error en configuración automática');
            $btn.prop('disabled', false).html(originalHtml);
          }
        },
        error: function (xhr, status, error) {
          console.error('[Evolution API] Auto-setup error:', error);
          showQrStatus('error', mydEvolutionData.i18n.connectionError);
          $btn.prop('disabled', false).html(originalHtml);
        },
      });
    });

    /**
     * Actualizar QR manualmente
     */
    $('#btn-refresh-qr').on('click', function () {
      getQrCode();
    });

    /**
     * Desconectar instancia
     */
    $('#btn-logout-instance').on('click', function () {
      if (!confirm(mydEvolutionData.i18n.confirmLogout)) {
        return;
      }

      const $btn = $(this);
      const originalHtml = $btn.html();

      $btn
        .prop('disabled', true)
        .html('<span class="dashicons dashicons-update spin"></span> ' + mydEvolutionData.i18n.disconnecting);

      $.ajax({
        url: mydEvolutionData.ajaxurl,
        type: 'POST',
        data: {
          action: 'myd_evolution_logout_instance',
          nonce: mydEvolutionData.nonce,
        },
        success: function (response) {
          if (response.success) {
            showQrStatus('success', response.data.message);
            stopQrRefresh();
            resetQrDisplay();
            $('#qr-connection-section').fadeOut();
            updateStatusIndicator(false);
          } else {
            showQrStatus('error', response.data.message);
          }
        },
        error: function () {
          showQrStatus('error', mydEvolutionData.i18n.connectionError);
        },
        complete: function () {
          $btn.prop('disabled', false).html(originalHtml);
        },
      });
    });

    /**
     * Auto-check de conexión al cargar página de settings
     */
    if ($('#tab-evolution-api-content').length) {
      console.log('[Evolution API] Tab found, initializing...');

      // Verificar estado automáticamente
      checkInitialStatus();
    }

    /**
     * Verificar estado inicial al cargar la página
     */
    function checkInitialStatus() {
      $.ajax({
        url: mydEvolutionData.ajaxurl,
        type: 'POST',
        data: {
          action: 'myd_evolution_check_status',
          nonce: mydEvolutionData.nonce,
        },
        success: function (response) {
          console.log('[Evolution API] Initial status:', response);

          if (response.success && response.data.is_open) {
            // Ya está conectado
            updateStatusIndicator(true);
            $('#btn-show-qr-section').hide();
          } else {
            // No está conectado, mostrar botón de conexión
            updateStatusIndicator(false);
            $('#btn-show-qr-section').show();
          }
        },
        error: function (xhr, status, error) {
          console.error('[Evolution API] Failed to check initial status:', error);
          // Mostrar botón de conexión por si acaso
          updateStatusIndicator(false);
          $('#btn-show-qr-section').show();
        },
      });
    }

    /**
     * Mostrar sección QR al hacer clic en "Conectar WhatsApp"
     */
    $('#btn-show-qr-section').on('click', function () {
      $('#qr-connection-section').fadeIn();
    });

    /**
     * =========================================================================
     * Panel de Órdenes - Envío Manual
     * =========================================================================
     */

    /**
     * Click en botón "Enviar WhatsApp"
     */
    $(document).on('click', '.myd-evolution-send-btn', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const $btn = $(this);
      const $wrapper = $btn.closest('.myd-evolution-send-btn-wrapper');
      const orderId = $wrapper.data('manage-order-id');

      // Verificar si ya está enviando
      if ($btn.hasClass('sending')) {
        return;
      }

      // Confirmación
      if (!confirm(mydEvolutionData.i18n.confirmSend)) {
        return;
      }

      // Estado loading
      $btn.addClass('sending').prop('disabled', true);
      $wrapper.find('.evolution-sent-badge, .evolution-error-message').hide();

      $.ajax({
        url: mydEvolutionData.ajaxurl,
        type: 'POST',
        data: {
          action: 'myd_evolution_send_manual',
          nonce: mydEvolutionData.nonce,
          order_id: orderId,
        },
        success: function (response) {
          if (response.success) {
            // Mostrar badge de éxito
            const badge = $wrapper.find('.evolution-sent-badge');
            badge.text(mydEvolutionData.i18n.sentNow).show();

            // Ocultar badge después de 5 segundos
            setTimeout(function () {
              badge.fadeOut();
            }, 5000);

            // Trigger evento custom para otros scripts
            $(document).trigger('myd:evolution:message-sent', {
              orderId: orderId,
              response: response.data,
            });
          } else {
            // Mostrar error
            const errorMsg = $wrapper.find('.evolution-error-message');
            errorMsg.text(response.data.message).show();

            // Ocultar error después de 8 segundos
            setTimeout(function () {
              errorMsg.fadeOut();
            }, 8000);
          }
        },
        error: function () {
          const errorMsg = $wrapper.find('.evolution-error-message');
          errorMsg.text(mydEvolutionData.i18n.sendError).show();
        },
        complete: function () {
          $btn.removeClass('sending').prop('disabled', false);
        },
      });
    });

    /**
     * Actualizar order ID cuando se selecciona una orden en el panel
     */
    $(document).on('click', '.fdm-orders-items', function () {
      const orderId = $(this).attr('id');

      if (orderId) {
        // Actualizar el data-manage-order-id del wrapper de WhatsApp
        $('.myd-evolution-send-btn-wrapper').attr('data-manage-order-id', orderId);

        // Limpiar mensajes previos
        $('.evolution-sent-badge, .evolution-error-message').hide();
      }
    });

    /**
     * Debugging helper
     */
    console.log('[Evolution API] Script loaded successfully');
  }); // End document.ready
})(jQuery);

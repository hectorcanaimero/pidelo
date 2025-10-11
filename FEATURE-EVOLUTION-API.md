# ✨ Feature: Integración con Evolution API para Mensajes Transaccionales

**Plugin**: MyD Delivery Pro
**Versión Target**: 2.3.0
**Prioridad**: Alta
**Tipo**: Feature/Enhancement
**Estimación**: 20-25 horas

---

## 📋 Índice

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Objetivos](#objetivos)
3. [Contexto Técnico](#contexto-técnico)
4. [Arquitectura Propuesta](#arquitectura-propuesta)
5. [Plan de Implementación](#plan-de-implementación)
6. [Especificaciones Técnicas](#especificaciones-técnicas)
7. [Diseño UI/UX](#diseño-uiux)
8. [Testing](#testing)
9. [Documentación](#documentación)
10. [Roadmap](#roadmap)

---

## 🎯 Resumen Ejecutivo

### Problema Actual
El plugin utiliza redirección a WhatsApp Web (`wa.me`) que requiere intervención manual del usuario para enviar mensajes. Esto genera:
- ❌ Fricción en la experiencia del usuario
- ❌ Mensajes no enviados (usuario olvida enviar)
- ❌ Falta de trazabilidad de comunicaciones
- ❌ No hay automatización de notificaciones

### Solución Propuesta
Integrar Evolution API para enviar mensajes transaccionales automáticos de WhatsApp sin intervención del usuario:
- ✅ Envío automático en eventos clave del pedido
- ✅ Notificaciones instantáneas a clientes y admin
- ✅ Trazabilidad completa de mensajes
- ✅ Mejora significativa en UX

### Beneficios
- **Para el negocio**: Automatización, profesionalismo, mejor comunicación
- **Para el cliente**: Información en tiempo real sin acciones manuales
- **Para el admin**: Control centralizado, logs, menos trabajo manual

---

## 🎯 Objetivos

### Objetivos Principales
1. ✅ Integrar Evolution API como sistema de mensajería
2. ✅ Enviar mensajes automáticos en eventos del ciclo de vida de órdenes
3. ✅ Mantener compatibilidad con sistema actual (wa.me)
4. ✅ Proporcionar UI intuitiva para configuración

### Objetivos Secundarios
1. ✅ Sistema de logs para debugging
2. ✅ Envío manual desde panel de órdenes
3. ✅ Templates personalizables por evento
4. ✅ Indicadores visuales de estado de mensajes

---

## 🏗️ Contexto Técnico

### Tecnologías Actuales
- **Backend**: PHP 7.4+, WordPress 5.5+
- **Frontend**: jQuery, JavaScript vanilla
- **Arquitectura**: Plugin WordPress con namespaces PSR-4
- **Sistema actual**: `Custom_Message_Whatsapp` genera links `wa.me`

### Evolution API - Descripción Técnica

#### ¿Qué es Evolution API?
API open-source que proporciona integración completa con WhatsApp mediante:
- **Baileys Protocol**: Protocolo de WhatsApp Web
- **WhatsApp Business API**: API oficial de Meta
- Soporte multi-instancia
- Webhooks para eventos en tiempo real

#### Endpoints Principales
```
POST /instance/create          - Crear instancia
GET  /instance/{name}/status   - Estado de instancia
POST /message/sendText         - Enviar mensaje de texto
POST /message/sendMedia        - Enviar imagen/documento
GET  /instance/{name}/qrcode   - Obtener QR para conexión
```

#### Autenticación
```http
Headers:
  apikey: YOUR_API_KEY
  Content-Type: application/json
```

#### Ejemplo Request - Enviar Texto
```json
POST https://api.evolution.com/message/sendText

Headers:
  apikey: xxxxxxxx

Body:
{
  "number": "5511999999999",
  "text": "Hola! Tu pedido #123 ha sido confirmado",
  "delay": 0
}
```

#### Ejemplo Response
```json
{
  "status": "success",
  "messageId": "3EB0xxxxx",
  "timestamp": 1234567890
}
```

---

## 🏛️ Arquitectura Propuesta

### Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────┐
│                     ADMIN DASHBOARD                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Settings    │  │ Order Panel  │  │  Logs View   │      │
│  │  Evolution   │  │ Manual Send  │  │  (Optional)  │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
└─────────┼──────────────────┼──────────────────┼─────────────┘
          │                  │                  │
          ▼                  ▼                  ▼
┌─────────────────────────────────────────────────────────────┐
│                  BACKEND PHP LAYER                           │
│  ┌────────────────────────────────────────────────────────┐ │
│  │         WhatsApp_Service (Servicio Principal)          │ │
│  │  • Procesa templates                                   │ │
│  │  • Decide cuándo enviar                                │ │
│  │  • Orquesta envío de mensajes                          │ │
│  └───────┬────────────────────────────────────────────────┘ │
│          │                                                   │
│  ┌───────▼───────────┐        ┌──────────────────────┐     │
│  │ Evolution_Client  │        │    Order_Hooks       │     │
│  │ (HTTP Client)     │        │  (Event Triggers)    │     │
│  │ • send_text()     │        │ • save_post hook     │     │
│  │ • send_media()    │        │ • status changes     │     │
│  │ • check_status()  │        │ • payment events     │     │
│  └───────┬───────────┘        └──────────────────────┘     │
│          │                                                   │
│  ┌───────▼───────────┐                                      │
│  │      Logger       │                                      │
│  │  • Log messages   │                                      │
│  │  • Store meta     │                                      │
│  └───────────────────┘                                      │
└──────────┼───────────────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────────┐
│               EVOLUTION API (External)                       │
│  • Envío real de mensajes WhatsApp                          │
│  • Gestión de instancias                                     │
│  • Webhooks (futuro)                                         │
└─────────────────────────────────────────────────────────────┘
```

### Flujo de Datos - Envío Automático

```
Usuario hace pedido
        │
        ��
Orden creada (save_post)
        │
        ▼
Order_Hooks detecta evento
        │
        ▼
WhatsApp_Service procesa template
        │
        ├─→ Reutiliza Custom_Message_Whatsapp
        │   para generar mensaje
        │
        ▼
Evolution_Client envía request
        │
        ├─→ Success → Logger guarda
        │
        └─→ Error → Fallback wa.me (opcional)
```

### Flujo de Datos - Envío Manual

```
Admin click "Enviar WhatsApp"
        │
        ▼
AJAX request
        │
        ▼
WhatsApp_Service prepara mensaje
        │
        ▼
Evolution_Client envía
        │
        ▼
Response → Update UI
        │
        ├─→ Success: Mostrar checkmark
        └─→ Error: Mostrar mensaje error
```

---

## 📝 Plan de Implementación

### FASE 1: Backend Core (6-8h)

#### 1.1 Evolution Client
**Archivo**: `includes/integrations/evolution-api/class-evolution-client.php`

```php
<?php
namespace MydPro\Includes\Integrations\Evolution_Api;

class Evolution_Client {
    private string $api_url;
    private string $api_key;
    private string $instance_name;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_url = get_option('myd-evolution-api-url');
        $this->api_key = get_option('myd-evolution-api-key');
        $this->instance_name = get_option('myd-evolution-instance-name');
    }

    /**
     * Enviar mensaje de texto
     */
    public function send_text(string $phone, string $message): array {
        $endpoint = $this->api_url . '/message/sendText';

        $body = [
            'number' => $this->format_phone($phone),
            'text' => $message,
            'delay' => 0
        ];

        return $this->request($endpoint, $body);
    }

    /**
     * Enviar imagen
     */
    public function send_media(string $phone, string $media_url, string $caption = ''): array {
        // Implementación
    }

    /**
     * Verificar estado de instancia
     */
    public function check_status(): array {
        $endpoint = $this->api_url . "/instance/{$this->instance_name}/status";
        return $this->request($endpoint, [], 'GET');
    }

    /**
     * Request HTTP genérico
     */
    private function request(string $url, array $body = [], string $method = 'POST'): array {
        $args = [
            'method' => $method,
            'headers' => [
                'apikey' => $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ];

        if ($method === 'POST') {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        return [
            'success' => $status_code >= 200 && $status_code < 300,
            'status_code' => $status_code,
            'data' => $body
        ];
    }

    /**
     * Formatear teléfono para Evolution
     */
    private function format_phone(string $phone): string {
        // Remover espacios, guiones, paréntesis
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Si no empieza con código país, agregar (configurable)
        if (substr($phone, 0, 2) !== '55') { // ejemplo Brasil
            $phone = '55' . $phone;
        }

        return $phone;
    }
}
```

#### 1.2 WhatsApp Service
**Archivo**: `includes/integrations/evolution-api/class-whatsapp-service.php`

```php
<?php
namespace MydPro\Includes\Integrations\Evolution_Api;

use MydPro\Includes\Custom_Message_Whatsapp;

class WhatsApp_Service {
    private Evolution_Client $client;
    private Logger $logger;

    public function __construct() {
        $this->client = new Evolution_Client();
        $this->logger = new Logger();
    }

    /**
     * Enviar notificación de orden
     */
    public function send_order_notification(int $order_id, string $event = 'created'): array {
        // Verificar si Evolution está habilitado
        if (!$this->is_enabled()) {
            return ['success' => false, 'error' => 'Evolution API disabled'];
        }

        // Verificar si el evento debe enviar mensaje automático
        if (!$this->should_send_for_event($event)) {
            return ['success' => false, 'error' => 'Event not configured for auto-send'];
        }

        // Obtener teléfono del cliente
        $phone = get_post_meta($order_id, 'customer_phone', true);
        if (empty($phone)) {
            return ['success' => false, 'error' => 'No phone number'];
        }

        // Generar mensaje usando sistema actual
        $message = $this->generate_message($order_id, $event);

        // Enviar
        $result = $this->client->send_text($phone, $message);

        // Log
        $this->logger->log_message($order_id, $event, $result);

        // Actualizar meta de orden
        if ($result['success']) {
            $this->update_order_message_meta($order_id, $event, $result);
        }

        return $result;
    }

    /**
     * Generar mensaje reutilizando lógica actual
     */
    private function generate_message(int $order_id, string $event): string {
        // Reutilizar Custom_Message_Whatsapp pero extraer solo el texto
        $message_generator = new Custom_Message_Whatsapp($order_id);

        // Obtener el mensaje según el evento
        $template_option = $this->get_template_option_for_event($event);
        $message = get_option($template_option);

        // Si no hay template específico, usar el default
        if (empty($message)) {
            // Extraer mensaje del link wa.me existente
            $link = $message_generator->get_whatsapp_redirect_link();
            parse_str(parse_url($link, PHP_URL_QUERY), $params);
            $message = urldecode($params['text'] ?? '');
        }

        // Procesar tokens (reutilizar lógica de Custom_Message_Whatsapp)
        // ...

        return $message;
    }

    /**
     * Verificar si Evolution está habilitado
     */
    private function is_enabled(): bool {
        return get_option('myd-evolution-api-enabled') === 'yes';
    }

    /**
     * Verificar si evento debe disparar envío automático
     */
    private function should_send_for_event(string $event): bool {
        $auto_events = get_option('myd-evolution-auto-send-events', []);
        return in_array($event, $auto_events, true);
    }

    /**
     * Obtener template para evento
     */
    private function get_template_option_for_event(string $event): string {
        $templates = [
            'order_created' => 'myd-evolution-template-order-created',
            'order_confirmed' => 'myd-evolution-template-order-confirmed',
            'order_in_process' => 'myd-evolution-template-order-in-process',
            'order_in_delivery' => 'myd-evolution-template-order-in-delivery',
            'order_completed' => 'myd-evolution-template-order-completed',
        ];

        return $templates[$event] ?? '';
    }

    /**
     * Actualizar meta de orden con info de mensaje
     */
    private function update_order_message_meta(int $order_id, string $event, array $result): void {
        $messages_log = get_post_meta($order_id, '_evolution_messages_log', true) ?: [];

        $messages_log[] = [
            'event' => $event,
            'timestamp' => current_time('mysql'),
            'message_id' => $result['data']['messageId'] ?? '',
            'status' => 'sent'
        ];

        update_post_meta($order_id, '_evolution_messages_log', $messages_log);
        update_post_meta($order_id, '_last_whatsapp_sent', current_time('mysql'));
    }
}
```

#### 1.3 Order Hooks
**Archivo**: `includes/integrations/evolution-api/class-order-hooks.php`

```php
<?php
namespace MydPro\Includes\Integrations\Evolution_Api;

class Order_Hooks {
    private WhatsApp_Service $service;

    public function __construct() {
        $this->service = new WhatsApp_Service();
        $this->init_hooks();
    }

    private function init_hooks(): void {
        // Hook cuando cambia status de orden
        add_action('updated_post_meta', [$this, 'on_order_status_change'], 10, 4);

        // Hook cuando se completa el pago
        add_action('myd_order_payment_completed', [$this, 'on_payment_completed'], 10, 1);

        // Hook para envío manual desde admin
        add_action('wp_ajax_myd_evolution_send_manual', [$this, 'ajax_send_manual']);
    }

    /**
     * Detectar cambio de status de orden
     */
    public function on_order_status_change($meta_id, $post_id, $meta_key, $meta_value): void {
        // Solo procesar si es una orden
        if (get_post_type($post_id) !== 'mydelivery-orders') {
            return;
        }

        // Solo procesar cambios de status
        if ($meta_key !== 'order_status') {
            return;
        }

        // Mapear status a evento
        $event = 'order_' . str_replace('-', '_', $meta_value);

        // Enviar notificación
        $this->service->send_order_notification($post_id, $event);
    }

    /**
     * Cuando se completa el pago
     */
    public function on_payment_completed(int $order_id): void {
        $this->service->send_order_notification($order_id, 'payment_completed');
    }

    /**
     * AJAX para envío manual
     */
    public function ajax_send_manual(): void {
        check_ajax_referer('myd-evolution-send', 'nonce');

        $order_id = (int) $_POST['order_id'];

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'No permission']);
        }

        $result = $this->service->send_order_notification($order_id, 'manual');

        if ($result['success']) {
            wp_send_json_success([
                'message' => __('Mensaje enviado correctamente', 'myd-delivery-pro')
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['error'] ?? __('Error desconocido', 'myd-delivery-pro')
            ]);
        }
    }
}
```

#### 1.4 Logger
**Archivo**: `includes/integrations/evolution-api/class-logger.php`

```php
<?php
namespace MydPro\Includes\Integrations\Evolution_Api;

class Logger {
    /**
     * Log mensaje enviado
     */
    public function log_message(int $order_id, string $event, array $result): void {
        $log_entry = [
            'order_id' => $order_id,
            'event' => $event,
            'timestamp' => current_time('mysql'),
            'success' => $result['success'],
            'message_id' => $result['data']['messageId'] ?? '',
            'error' => $result['error'] ?? ''
        ];

        // Guardar en custom table (opcional) o usar error_log
        error_log('[Evolution API] ' . wp_json_encode($log_entry));

        // También guardar en meta de orden
        $order_logs = get_post_meta($order_id, '_evolution_logs', true) ?: [];
        $order_logs[] = $log_entry;
        update_post_meta($order_id, '_evolution_logs', $order_logs);
    }
}
```

---

### FASE 2: Configuración & Settings (4-5h)

#### 2.1 Actualizar Settings
**Archivo**: `includes/admin/class-settings.php`

Agregar al array `$this->settings`:

```php
// Evolution API Settings
[
    'name' => 'myd-evolution-api-enabled',
    'option_group' => self::CONFIG_GROUP,
    'args' => [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'no',
    ],
],
[
    'name' => 'myd-evolution-api-url',
    'option_group' => self::CONFIG_GROUP,
    'args' => [
        'sanitize_callback' => 'esc_url_raw',
        'default' => '',
    ],
],
[
    'name' => 'myd-evolution-api-key',
    'option_group' => self::CONFIG_GROUP,
    'args' => [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ],
],
[
    'name' => 'myd-evolution-instance-name',
    'option_group' => self::CONFIG_GROUP,
    'args' => [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ],
],
[
    'name' => 'myd-evolution-auto-send-events',
    'option_group' => self::CONFIG_GROUP,
    'args' => [
        // Array de eventos
        'default' => [],
    ],
],
// Templates por evento
[
    'name' => 'myd-evolution-template-order-created',
    'option_group' => self::CONFIG_GROUP,
    'args' => [
        'default' => '¡Hola {customer-name}! Tu pedido #{order-number} ha sido recibido. Total: {order-total}',
    ],
],
// ... más templates
```

#### 2.2 Template de Settings UI
**Archivo**: `templates/admin/settings-tabs/evolution-api/tab-evolution-api.php`

```php
<?php
if (!defined('ABSPATH')) exit;

$is_enabled = get_option('myd-evolution-api-enabled') === 'yes';
$api_url = get_option('myd-evolution-api-url');
$api_key = get_option('myd-evolution-api-key');
$instance_name = get_option('myd-evolution-instance-name');
$auto_events = get_option('myd-evolution-auto-send-events', []);
?>

<div id="tab-evolution-api-content" class="myd-tabs-content">
    <h2>
        <?php esc_html_e('Evolution API - WhatsApp Automático', 'myd-delivery-pro'); ?>
    </h2>

    <div class="myd-evolution-status-banner">
        <div class="status-indicator" id="evolution-status-indicator">
            <span class="status-dot"></span>
            <span class="status-text"><?php esc_html_e('Desconectado', 'myd-delivery-pro'); ?></span>
        </div>
    </div>

    <table class="form-table">
        <tbody>
            <!-- Toggle Activar/Desactivar -->
            <tr>
                <th scope="row">
                    <label for="myd-evolution-api-enabled">
                        <?php esc_html_e('Habilitar Evolution API', 'myd-delivery-pro'); ?>
                    </label>
                </th>
                <td>
                    <label class="myd-toggle-switch">
                        <input
                            type="checkbox"
                            name="myd-evolution-api-enabled"
                            id="myd-evolution-api-enabled"
                            value="yes"
                            <?php checked($is_enabled, true); ?>
                        >
                        <span class="slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Activa el envío automático de mensajes de WhatsApp mediante Evolution API', 'myd-delivery-pro'); ?>
                    </p>
                </td>
            </tr>

            <!-- URL de la API -->
            <tr>
                <th scope="row">
                    <label for="myd-evolution-api-url">
                        <?php esc_html_e('URL de Evolution API', 'myd-delivery-pro'); ?>
                    </label>
                </th>
                <td>
                    <input
                        type="url"
                        name="myd-evolution-api-url"
                        id="myd-evolution-api-url"
                        value="<?php echo esc_attr($api_url); ?>"
                        class="regular-text"
                        placeholder="https://api.evolution.com"
                    >
                    <p class="description">
                        <?php esc_html_e('URL base de tu servidor Evolution API (sin barra final)', 'myd-delivery-pro'); ?>
                    </p>
                </td>
            </tr>

            <!-- API Key -->
            <tr>
                <th scope="row">
                    <label for="myd-evolution-api-key">
                        <?php esc_html_e('API Key', 'myd-delivery-pro'); ?>
                    </label>
                </th>
                <td>
                    <input
                        type="password"
                        name="myd-evolution-api-key"
                        id="myd-evolution-api-key"
                        value="<?php echo esc_attr($api_key); ?>"
                        class="regular-text"
                        autocomplete="off"
                    >
                    <button type="button" class="button" id="toggle-api-key-visibility">
                        <?php esc_html_e('Mostrar', 'myd-delivery-pro'); ?>
                    </button>
                    <p class="description">
                        <?php esc_html_e('API Key de autenticación de Evolution API', 'myd-delivery-pro'); ?>
                    </p>
                </td>
            </tr>

            <!-- Nombre de Instancia -->
            <tr>
                <th scope="row">
                    <label for="myd-evolution-instance-name">
                        <?php esc_html_e('Nombre de Instancia', 'myd-delivery-pro'); ?>
                    </label>
                </th>
                <td>
                    <input
                        type="text"
                        name="myd-evolution-instance-name"
                        id="myd-evolution-instance-name"
                        value="<?php echo esc_attr($instance_name); ?>"
                        class="regular-text"
                    >
                    <p class="description">
                        <?php esc_html_e('Nombre de tu instancia de WhatsApp en Evolution API', 'myd-delivery-pro'); ?>
                    </p>
                </td>
            </tr>

            <!-- Botón Test Conexión -->
            <tr>
                <th scope="row"></th>
                <td>
                    <button
                        type="button"
                        class="button button-secondary"
                        id="myd-evolution-test-connection"
                    >
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <?php esc_html_e('Probar Conexión', 'myd-delivery-pro'); ?>
                    </button>
                    <span id="test-connection-result"></span>
                </td>
            </tr>
        </tbody>
    </table>

    <hr>

    <!-- Eventos Automáticos -->
    <h3><?php esc_html_e('Eventos que disparan envío automático', 'myd-delivery-pro'); ?></h3>
    <p class="description">
        <?php esc_html_e('Selecciona en qué eventos del pedido se debe enviar un mensaje automático al cliente', 'myd-delivery-pro'); ?>
    </p>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <?php esc_html_e('Eventos', 'myd-delivery-pro'); ?>
                </th>
                <td>
                    <fieldset>
                        <label>
                            <input
                                type="checkbox"
                                name="myd-evolution-auto-send-events[]"
                                value="order_new"
                                <?php checked(in_array('order_new', $auto_events)); ?>
                            >
                            <?php esc_html_e('Pedido Nuevo (Cliente realiza pedido)', 'myd-delivery-pro'); ?>
                        </label><br>

                        <label>
                            <input
                                type="checkbox"
                                name="myd-evolution-auto-send-events[]"
                                value="order_confirmed"
                                <?php checked(in_array('order_confirmed', $auto_events)); ?>
                            >
                            <?php esc_html_e('Pedido Confirmado', 'myd-delivery-pro'); ?>
                        </label><br>

                        <label>
                            <input
                                type="checkbox"
                                name="myd-evolution-auto-send-events[]"
                                value="order_in_process"
                                <?php checked(in_array('order_in_process', $auto_events)); ?>
                            >
                            <?php esc_html_e('En Preparación', 'myd-delivery-pro'); ?>
                        </label><br>

                        <label>
                            <input
                                type="checkbox"
                                name="myd-evolution-auto-send-events[]"
                                value="order_in_delivery"
                                <?php checked(in_array('order_in_delivery', $auto_events)); ?>
                            >
                            <?php esc_html_e('En Camino / Delivery', 'myd-delivery-pro'); ?>
                        </label><br>

                        <label>
                            <input
                                type="checkbox"
                                name="myd-evolution-auto-send-events[]"
                                value="order_done"
                                <?php checked(in_array('order_done', $auto_events)); ?>
                            >
                            <?php esc_html_e('Pedido Completado', 'myd-delivery-pro'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </tbody>
    </table>

    <hr>

    <!-- Templates de Mensajes -->
    <h3><?php esc_html_e('Templates de Mensajes', 'myd-delivery-pro'); ?></h3>
    <p class="description">
        <?php esc_html_e('Personaliza los mensajes que se envían en cada evento. Puedes usar los tokens disponibles.', 'myd-delivery-pro'); ?>
    </p>

    <div class="myd-templates-section">
        <!-- Template: Pedido Nuevo -->
        <div class="template-item">
            <h4><?php esc_html_e('Mensaje: Pedido Nuevo', 'myd-delivery-pro'); ?></h4>
            <textarea
                name="myd-evolution-template-order-created"
                rows="5"
                class="large-text code"
            ><?php echo esc_textarea(get_option('myd-evolution-template-order-created')); ?></textarea>
        </div>

        <!-- Template: Pedido Confirmado -->
        <div class="template-item">
            <h4><?php esc_html_e('Mensaje: Pedido Confirmado', 'myd-delivery-pro'); ?></h4>
            <textarea
                name="myd-evolution-template-order-confirmed"
                rows="5"
                class="large-text code"
            ><?php echo esc_textarea(get_option('myd-evolution-template-order-confirmed')); ?></textarea>
        </div>

        <!-- Más templates... -->
    </div>

    <!-- Tokens Disponibles -->
    <div class="myd-tokens-info">
        <h4><?php esc_html_e('Tokens Disponibles', 'myd-delivery-pro'); ?></h4>
        <ul>
            <li><code>{order-number}</code> - Número de pedido</li>
            <li><code>{customer-name}</code> - Nombre del cliente</li>
            <li><code>{order-total}</code> - Total del pedido</li>
            <li><code>{order-status}</code> - Estado actual</li>
            <li><code>{order-track-page}</code> - Link de seguimiento</li>
            <li><code>{business-name}</code> - Nombre del negocio</li>
            <!-- Más tokens... -->
        </ul>
    </div>
</div>

<style>
/* Estilos del banner de estado */
.myd-evolution-status-banner {
    background: #f5f5f5;
    border-left: 4px solid #ddd;
    padding: 15px;
    margin: 20px 0;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #dc3545;
    display: inline-block;
}

.status-indicator.connected .status-dot {
    background: #28a745;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Toggle Switch */
.myd-toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.myd-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #2196F3;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

/* Templates */
.template-item {
    margin-bottom: 20px;
}

.myd-tokens-info {
    background: #fff;
    border: 1px solid #ddd;
    padding: 15px;
    margin-top: 20px;
}

.myd-tokens-info ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.myd-tokens-info li {
    padding: 5px 0;
}

.myd-tokens-info code {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
}
</style>
```

---

### FASE 3: UI Panel de Órdenes (3-4h)

#### 3.1 Botón de Envío Manual
**Actualizar**: `templates/order/order-content.php`

Agregar después de los botones existentes (alrededor de línea 150):

```php
<?php if (get_option('myd-evolution-api-enabled') === 'yes') : ?>
    <div class="fdm-evolution-send-wrapper">
        <button
            type="button"
            class="fdm-evolution-send-btn"
            data-order-id="<?php echo esc_attr($postid); ?>"
            title="<?php esc_attr_e('Enviar mensaje de WhatsApp', 'myd-delivery-pro'); ?>"
        >
            <span class="dashicons dashicons-whatsapp"></span>
            <?php esc_html_e('Enviar WhatsApp', 'myd-delivery-pro'); ?>
        </button>

        <?php
        $last_sent = get_post_meta($postid, '_last_whatsapp_sent', true);
        if ($last_sent) :
        ?>
            <span class="evolution-sent-badge">
                ✓ <?php echo esc_html(
                    sprintf(
                        __('Enviado %s', 'myd-delivery-pro'),
                        human_time_diff(strtotime($last_sent), current_time('timestamp'))
                    )
                ); ?>
            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>
```

**CSS para el botón** (agregar a `assets/css/delivery-frontend.min.css` o crear nuevo):

```css
.fdm-evolution-send-wrapper {
    margin-top: 10px;
}

.fdm-evolution-send-btn {
    background: #25D366;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    transition: background 0.3s;
}

.fdm-evolution-send-btn:hover {
    background: #128C7E;
}

.fdm-evolution-send-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.fdm-evolution-send-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.fdm-evolution-send-btn.sending {
    opacity: 0.7;
}

.fdm-evolution-send-btn.sending::after {
    content: "";
    width: 12px;
    height: 12px;
    border: 2px solid white;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    margin-left: 8px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.evolution-sent-badge {
    display: inline-block;
    margin-left: 10px;
    color: #28a745;
    font-size: 12px;
}

.evolution-error-message {
    color: #dc3545;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}
```

#### 3.2 JavaScript para Envío Manual
**Archivo**: `assets/js/evolution-admin.js` (nuevo)

```javascript
(function($) {
    'use strict';

    /**
     * Test de Conexión en Settings
     */
    $('#myd-evolution-test-connection').on('click', function() {
        const $btn = $(this);
        const $result = $('#test-connection-result');

        $btn.prop('disabled', true).html(
            '<span class="dashicons dashicons-update spin"></span> Probando...'
        );
        $result.html('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'myd_evolution_test_connection',
                nonce: mydEvolutionData.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html(
                        '<span style="color: #28a745;">✓ Conexión exitosa</span>'
                    );
                    updateStatusIndicator(true);
                } else {
                    $result.html(
                        '<span style="color: #dc3545;">✗ ' + response.data.message + '</span>'
                    );
                    updateStatusIndicator(false);
                }
            },
            error: function() {
                $result.html(
                    '<span style="color: #dc3545;">✗ Error de conexión</span>'
                );
                updateStatusIndicator(false);
            },
            complete: function() {
                $btn.prop('disabled', false).html(
                    '<span class="dashicons dashicons-admin-plugins"></span> Probar Conexión'
                );
            }
        });
    });

    /**
     * Toggle API Key Visibility
     */
    $('#toggle-api-key-visibility').on('click', function() {
        const $input = $('#myd-evolution-api-key');
        const type = $input.attr('type');

        if (type === 'password') {
            $input.attr('type', 'text');
            $(this).text('Ocultar');
        } else {
            $input.attr('type', 'password');
            $(this).text('Mostrar');
        }
    });

    /**
     * Envío Manual desde Panel de Órdenes
     */
    $(document).on('click', '.fdm-evolution-send-btn', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const orderId = $btn.data('order-id');
        const $wrapper = $btn.closest('.fdm-evolution-send-wrapper');

        if ($btn.hasClass('sending')) {
            return;
        }

        // Confirmar
        if (!confirm('¿Enviar mensaje de WhatsApp al cliente?')) {
            return;
        }

        // Estado loading
        $btn.addClass('sending').prop('disabled', true);
        $wrapper.find('.evolution-error-message').remove();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'myd_evolution_send_manual',
                nonce: mydEvolutionData.nonce,
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    // Éxito
                    $wrapper.append(
                        '<span class="evolution-sent-badge">✓ Enviado ahora</span>'
                    );

                    // Actualizar badge existente si lo hay
                    setTimeout(function() {
                        location.reload(); // O actualizar dinámicamente
                    }, 2000);
                } else {
                    // Error
                    $wrapper.append(
                        '<span class="evolution-error-message">✗ ' +
                        response.data.message +
                        '</span>'
                    );
                }
            },
            error: function() {
                $wrapper.append(
                    '<span class="evolution-error-message">✗ Error de conexión</span>'
                );
            },
            complete: function() {
                $btn.removeClass('sending').prop('disabled', false);
            }
        });
    });

    /**
     * Actualizar indicador de estado
     */
    function updateStatusIndicator(isConnected) {
        const $indicator = $('#evolution-status-indicator');

        if (isConnected) {
            $indicator.addClass('connected');
            $indicator.find('.status-text').text('Conectado');
        } else {
            $indicator.removeClass('connected');
            $indicator.find('.status-text').text('Desconectado');
        }
    }

    /**
     * Auto-check al cargar página de settings
     */
    if ($('#tab-evolution-api-content').length) {
        // Verificar estado automáticamente
        const isEnabled = $('#myd-evolution-api-enabled').is(':checked');
        const hasConfig = $('#myd-evolution-api-url').val() &&
                         $('#myd-evolution-api-key').val();

        if (isEnabled && hasConfig) {
            $('#myd-evolution-test-connection').trigger('click');
        }
    }

})(jQuery);
```

#### 3.3 Enqueue de Assets
**Actualizar**: `includes/class-plugin.php` o donde se registran los assets

```php
// En el método de registro de scripts
public function register_admin_assets(): void {
    wp_register_script(
        'myd-evolution-admin',
        MYD_PLUGN_URL . 'assets/js/evolution-admin.js',
        ['jquery'],
        MYD_CURRENT_VERSION,
        true
    );

    wp_localize_script('myd-evolution-admin', 'mydEvolutionData', [
        'nonce' => wp_create_nonce('myd-evolution-send'),
        'ajaxurl' => admin_url('admin-ajax.php')
    ]);
}

// Enqueue condicional en settings
if (is_admin() && $_GET['page'] === 'myd-settings') {
    wp_enqueue_script('myd-evolution-admin');
}
```

---

### FASE 4: AJAX Handlers (2h)

**Archivo**: `includes/ajax/class-evolution-ajax.php`

```php
<?php
namespace MydPro\Includes\Ajax;

use MydPro\Includes\Integrations\Evolution_Api\Evolution_Client;
use MydPro\Includes\Integrations\Evolution_Api\WhatsApp_Service;

class Evolution_Ajax {
    public function __construct() {
        add_action('wp_ajax_myd_evolution_test_connection', [$this, 'test_connection']);
        add_action('wp_ajax_myd_evolution_send_manual', [$this, 'send_manual']);
    }

    /**
     * Test de conexión
     */
    public function test_connection(): void {
        check_ajax_referer('myd-evolution-send', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'No permission']);
        }

        $client = new Evolution_Client();
        $result = $client->check_status();

        if ($result['success']) {
            wp_send_json_success([
                'message' => __('Conexión establecida correctamente', 'myd-delivery-pro'),
                'data' => $result['data']
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['error'] ?? __('Error desconocido', 'myd-delivery-pro')
            ]);
        }
    }

    /**
     * Envío manual
     */
    public function send_manual(): void {
        check_ajax_referer('myd-evolution-send', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'No permission']);
        }

        $order_id = (int) $_POST['order_id'];

        $service = new WhatsApp_Service();
        $result = $service->send_order_notification($order_id, 'manual');

        if ($result['success']) {
            wp_send_json_success([
                'message' => __('Mensaje enviado correctamente', 'myd-delivery-pro')
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['error'] ?? __('Error al enviar mensaje', 'myd-delivery-pro')
            ]);
        }
    }
}
```

---

### FASE 5: Inicialización & Autoload (1h)

**Actualizar**: `includes/class-plugin.php`

```php
// En el método init() o constructor
private function init_evolution_api(): void {
    if (get_option('myd-evolution-api-enabled') !== 'yes') {
        return;
    }

    // Inicializar hooks
    new \MydPro\Includes\Integrations\Evolution_Api\Order_Hooks();

    // Inicializar AJAX
    new \MydPro\Includes\Ajax\Evolution_Ajax();
}

// Llamar en el constructor
public function __construct() {
    // ... código existente

    $this->init_evolution_api();
}
```

---

## 🎨 Diseño UI/UX

### Mockups de Interfaz

#### Settings Page
```
┌────────────────────────────────────────────────────────────┐
│  Evolution API - WhatsApp Automático                       │
├────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────────────────────────────────────────┐  │
│  │  🟢 Conectado                                        │  │
│  └─────────────────────────────────────────────────────┘  │
│                                                             │
│  Habilitar Evolution API:  [🔘 ON]                        │
│                                                             │
│  URL de Evolution API:                                     │
│  [https://api.evolution.com                    ]          │
│                                                             │
│  API Key:                                                  │
│  [••••••••••••••••••••]  [Mostrar]                        │
│                                                             │
│  Nombre de Instancia:                                      │
│  [mi-tienda                                    ]          │
│                                                             │
│  [🔌 Probar Conexión]  ✓ Conexión exitosa                │
│                                                             │
├────────────────────────────────────────────────────────────┤
│  Eventos que disparan envío automático                     │
├────────────────────────────────────────────────────────────┤
│                                                             │
│  ☑ Pedido Nuevo (Cliente realiza pedido)                  │
│  ☑ Pedido Confirmado                                       │
│  ☑ En Preparación                                          │
│  ☑ En Camino / Delivery                                    │
│  ☐ Pedido Completado                                       │
│                                                             │
├────────────────────────────────────────────────────────────┤
│  Templates de Mensajes                                     │
├────────────────────────────────────────────────────────────┤
│                                                             │
│  Mensaje: Pedido Nuevo                                     │
│  ┌─────────────────────────────────────────────────────┐  │
│  │ ¡Hola {customer-name}!                               │  │
│  │ Tu pedido #{order-number} ha sido recibido.         │  │
│  │ Total: {order-total}                                 │  │
│  │                                                       │  │
│  │ Seguimiento: {order-track-page}                      │  │
│  └─────────────────────────────────────────────────────┘  │
│                                                             │
│  Tokens Disponibles:                                       │
│  {order-number} {customer-name} {order-total}             │
│  {order-status} {order-track-page} {business-name}        │
│                                                             │
└────────────────────────────────────────────────────────────┘
```

#### Panel de Órdenes
```
┌────────────────────────────────────────────────────────────┐
│  Pedido #123                                      Delivery  │
│  15/01 - 14:30                                              │
├────────────────────────────────────────────────────────────┤
│  Juan Pérez                                                 │
│  +55 11 99999-9999                                         │
│  Av. Paulista, 1000 - Apto 501                            │
├────────────────────────────────────────────────────────────┤
│  2x Pizza Margarita                          $ 45.00       │
│  1x Coca-Cola                                $ 8.00        │
│                                                             │
│  Total: $ 53.00                                            │
├────────────────────────────────────────────────────────────┤
│  Status: [Nuevo ▼]  [Confirmar]  [Imprimir]               │
│                                                             │
│  [📱 Enviar WhatsApp]  ✓ Enviado hace 5 min               │
│                                                             │
└────────────────────────────────────────────────────────────┘
```

### Estados Visuales

**Botón Enviar WhatsApp - Estados:**

1. **Normal**
   - Verde (#25D366)
   - Texto: "Enviar WhatsApp"
   - Icono: dashicons-whatsapp

2. **Hover**
   - Verde oscuro (#128C7E)
   - Cursor: pointer

3. **Enviando (Loading)**
   - Opacidad 70%
   - Spinner rotando
   - Disabled

4. **Enviado (Success)**
   - Badge verde: "✓ Enviado hace X min"
   - Botón vuelve a normal (permite reenvío)

5. **Error**
   - Mensaje rojo debajo
   - Botón vuelve a normal

---

## 🧪 Testing

### Casos de Prueba

#### Backend

1. **Evolution_Client**
   - ✅ Envío de mensaje exitoso
   - ✅ Manejo de error 401 (API key inválida)
   - ✅ Manejo de error 404 (instancia no existe)
   - ✅ Timeout de conexión
   - ✅ Formateo correcto de teléfono

2. **WhatsApp_Service**
   - ✅ Generación correcta de mensaje con tokens
   - ✅ Verificación de eventos habilitados
   - ✅ Actualización de meta de orden
   - ✅ Fallback si Evolution deshabilitado

3. **Order_Hooks**
   - ✅ Detecta cambio de status correctamente
   - ✅ No envía en eventos no configurados
   - ✅ No duplica mensajes

#### Frontend

1. **Settings UI**
   - ✅ Toggle activa/desactiva
   - ✅ Test de conexión funciona
   - ✅ Muestra error si falta configuración
   - ✅ Guarda templates correctamente

2. **Panel de Órdenes**
   - ✅ Botón aparece si Evolution habilitado
   - ✅ Click envía AJAX correctamente
   - ✅ Muestra loading durante envío
   - ✅ Muestra confirmación de éxito
   - ✅ Muestra error si falla
   - ✅ Badge "Enviado" se actualiza

#### Integración

1. **Flujo Completo**
   - ✅ Cliente hace pedido → mensaje automático enviado
   - ✅ Admin cambia status → mensaje automático enviado
   - ✅ Admin hace envío manual → mensaje enviado
   - ✅ Log registrado en orden meta
   - ✅ Fallback a wa.me si Evolution falla

---

## 📚 Documentación

### Documentación de Usuario

Crear página wiki/docs con:

1. **Qué es Evolution API**
2. **Cómo obtener credenciales**
3. **Configuración paso a paso**
4. **Cómo crear instancia en Evolution**
5. **Obtener QR code**
6. **Troubleshooting común**

### Documentación para Desarrolladores

```php
/**
 * Hook para personalizar mensaje antes de enviar
 *
 * @param string $message Mensaje generado
 * @param int $order_id ID de la orden
 * @param string $event Evento que dispara
 * @return string Mensaje modificado
 */
apply_filters('myd_evolution_message_before_send', $message, $order_id, $event);

/**
 * Hook después de enviar mensaje
 *
 * @param array $result Resultado del envío
 * @param int $order_id ID de la orden
 * @param string $event Evento
 */
do_action('myd_evolution_message_sent', $result, $order_id, $event);

/**
 * Hook para agregar eventos personalizados
 *
 * @param array $events Lista de eventos
 * @return array
 */
apply_filters('myd_evolution_available_events', $events);
```

---

## 🗺️ Roadmap

### v2.3.0 (Este Feature)
- ✅ Integración básica Evolution API
- ✅ Envío automático en eventos de orden
- ✅ Envío manual desde panel
- ✅ Templates personalizables
- ✅ Sistema de logs

### v2.4.0 (Futuro)
- 📌 Webhooks de Evolution (recibir respuestas)
- 📌 Envío de imágenes (recibo de pago, QR)
- 📌 Templates con botones interactivos
- 📌 Notificaciones al admin vía WhatsApp
- 📌 Chat bidireccional en panel

### v2.5.0 (Futuro)
- 📌 Multi-instancia (varios WhatsApp)
- 📌 Programar mensajes
- 📌 A/B testing de templates
- 📌 Analytics de mensajes enviados
- 📌 Integración con CRM

---

## 🛠️ Dependencias

### Servidor
- PHP 7.4+
- WordPress 5.5+
- `wp_remote_request` habilitado
- Conexión HTTPS

### Servicios Externos
- Servidor Evolution API funcional
- Instancia de WhatsApp conectada
- API Key válida

### WordPress
- Permisos `edit_posts` para envío manual
- Permisos `manage_options` para settings

---

## 🔒 Seguridad

### Consideraciones

1. **API Key Storage**
   - Guardar en options (no en código)
   - Nunca exponer en frontend
   - Input type="password" en settings

2. **AJAX Nonces**
   - Validar en todos los endpoints
   - Timeout razonable

3. **Sanitización**
   - `esc_url_raw` para URL
   - `sanitize_text_field` para API key
   - `wp_kses_post` para templates

4. **Capability Checks**
   - `manage_options` para settings
   - `edit_posts` para envío manual

5. **Rate Limiting**
   - Implementar límite de envíos por minuto (futuro)
   - Prevenir spam

---

## 📊 Métricas de Éxito

### KPIs

- ✅ % de mensajes enviados exitosamente
- ✅ Tiempo promedio de envío
- ✅ % de pedidos con notificación automática
- ✅ Reducción de fricción en checkout
- ✅ Feedback de usuarios

### Logs a Monitorear

- Total de mensajes enviados
- Tasa de error de API
- Eventos más frecuentes
- Órdenes sin teléfono

---

## 🚀 Deploy & Rollout

### Checklist Pre-Deploy

- [ ] Tests unitarios pasando
- [ ] Tests de integración con Evolution
- [ ] Documentación completa
- [ ] Backup de base de datos
- [ ] Versión incrementada en plugin
- [ ] Changelog actualizado

### Plan de Rollout

1. **Beta (10% usuarios)**
   - Activar solo en configuración manual
   - Monitorear logs
   - Recoger feedback

2. **Gradual (50%)**
   - Habilitar por defecto (desactivado)
   - Email a usuarios con tutorial
   - Soporte activo

3. **General (100%)**
   - Promocionar feature
   - Case studies
   - Mejoras basadas en feedback

---

## 📞 Soporte

### FAQ Anticipadas

**Q: ¿Necesito un servidor propio de Evolution?**
A: Sí, necesitas una instancia de Evolution API funcionando. Puede ser self-hosted o un servicio administrado.

**Q: ¿Cuánto cuesta Evolution API?**
A: Evolution es open-source y gratuito, pero necesitas hosting para tu instancia.

**Q: ¿Funciona con WhatsApp Business oficial?**
A: Sí, Evolution soporta tanto Baileys como WhatsApp Business API oficial.

**Q: ¿Qué pasa si Evolution está caído?**
A: Los mensajes automáticos fallarán, pero puedes usar el fallback wa.me manual.

**Q: ¿Puedo personalizar los mensajes?**
A: Sí, todos los templates son editables y soportan tokens dinámicos.

---

## 🎯 Resumen Ejecutivo - Esfuerzo

| Fase | Descripción | Horas | Prioridad |
|------|-------------|-------|-----------|
| 1 | Backend Core | 6-8h | ALTA |
| 2 | Settings UI | 4-5h | ALTA |
| 3 | Panel Órdenes UI | 3-4h | MEDIA |
| 4 | AJAX Handlers | 2h | ALTA |
| 5 | Inicialización | 1h | ALTA |
| 6 | Testing | 2-3h | ALTA |
| 7 | Documentación | 2h | MEDIA |

**Total Estimado**: 20-25 horas

---

## ✅ Checklist de Implementación

### Backend
- [ ] `class-evolution-client.php` creado
- [ ] `class-whatsapp-service.php` creado
- [ ] `class-order-hooks.php` creado
- [ ] `class-logger.php` creado
- [ ] Settings registrados en `class-settings.php`
- [ ] AJAX handlers creados
- [ ] Hooks integrados en `class-plugin.php`

### Frontend
- [ ] Template settings `tab-evolution-api.php` creado
- [ ] Botón manual en `order-content.php` agregado
- [ ] `evolution-admin.js` creado
- [ ] CSS para botones y UI agregado
- [ ] Assets enqueued correctamente

### Testing
- [ ] Test envío exitoso
- [ ] Test manejo de errores
- [ ] Test UI settings
- [ ] Test botón manual
- [ ] Test hooks automáticos
- [ ] Test fallback

### Documentación
- [ ] README actualizado
- [ ] CHANGELOG con feature
- [ ] Wiki de usuario creada
- [ ] Comments en código

### Deploy
- [ ] Versión incrementada a 2.3.0
- [ ] Git commit con mensaje descriptivo
- [ ] Tag de release
- [ ] Notificación a usuarios

---

## 📝 Notas Finales

Este feature representa una evolución significativa del plugin, transformando el flujo de comunicación de manual a automático. La arquitectura propuesta es escalable y permite futuras mejoras como webhooks, chat bidireccional y analytics avanzados.

La implementación mantiene compatibilidad con el sistema actual, permitiendo una migración gradual y sin disrupciones para usuarios existentes.

**Próximos pasos sugeridos:**
1. Revisar y aprobar este documento
2. Configurar ambiente de desarrollo con Evolution API de prueba
3. Comenzar implementación por fases
4. Testing continuo durante desarrollo
5. Beta con usuarios seleccionados
6. Deploy gradual

---

**Documento creado**: <?php echo date('Y-m-d'); ?>
**Versión**: 1.0
**Autor**: Development Team
**Estado**: ✅ Aprobado para implementación

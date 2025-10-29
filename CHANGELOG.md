# Changelog

Todos los cambios notables de este proyecto ser�n documentados en este archivo.

El formato est� basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto se adhiere al [Versionado Sem�ntico](https://semver.org/lang/es/).

---

## [2.3.3] - 2025-10-28

### Corregido

- **Bug crítico: Error al subir comprobante de pago en mobile (#12)**
  - **Frontend (`payment-receipt.js`)**:
    - Nueva función `isElementVisible()` con detección robusta de visibilidad en mobile
    - Verificación multi-nivel: display, visibility, opacity, height y estado de `<details>`
    - Manejo garantizado del loading animation en TODOS los puntos de salida
    - Cambio de `throw Error` a `return false` para evitar UI bloqueado
    - Logs de debug completos con prefijos `[DEBUG]`, `[INFO]`, `[ERROR]`, `[SUCCESS]`
    - Información de contexto mobile: user agent, viewport, file details
    - Timeout de 100ms para scroll y focus en mobile (mejor compatibilidad)
  - **Backend (`class-place-payment.php`)**:
    - Logs completos con `error_log()` para tracking del flujo de subida
    - Respuestas JSON consistentes con `wp_send_json_error()` en todos los errores
    - Validación mejorada con logs de tipos de archivo, tamaño y errores de upload
    - Tracking detallado de creación de attachments y metadatos
- **Problema**: El campo de comprobante no se detectaba correctamente como visible en mobile
- **Problema**: Loading animation quedaba activo indefinidamente en caso de error
- **Problema**: Difícil de debuggear problemas en dispositivos mobile
- **Resultado**: Upload de comprobantes funciona correctamente en mobile con debugging completo

## [2.3.0] - 2025-10-11

### Añadido

- **Sistema Automatizador de Instancias Evolution API** - Integración completa con Evolution API para mensajes automáticos de WhatsApp
  - **Auto-Setup de Instancias**: Sistema automatizado que crea, conecta y verifica instancias de WhatsApp sin intervención manual
  - **Flujo Automatizado**: Usuario → Sistema crea instancia → Genera QR → Usuario escanea → Sistema detecta conexión → Listo
  - **Gestión Inteligente de Instancias**:
    - `Instance_Manager` - Clase orquestadora del proceso completo
    - Verificación automática de instancias existentes (no duplica)
    - Reconexión automática si se desconecta
    - Reset de instancia con un solo clic
  - **Interfaz de Usuario Mejorada**:
    - Banner de estado en tiempo real (🟢 Conectado / 🔴 Desconectado)
    - Botón "Conectar WhatsApp" que inicia el flujo automático
    - Generación automática de código QR
    - Sección QR con instrucciones paso a paso
    - Polling inteligente (verifica conexión cada 5 segundos)
    - Detección automática cuando se escanea el QR
    - Feedback visual en tiempo real
  - **Configuración Simplificada**:
    - Credenciales globales de Evolution API (URL y API Key hardcodeadas)
    - Nombre de instancia generado automáticamente desde el nombre de la tienda
    - Código de país configurable para formato de teléfonos
    - Sistema de eventos automáticos con checkboxes
    - Templates de mensajes personalizables por evento
  - **Mensajes Transaccionales Automáticos**:
    - Pedido Nuevo (`order_new`)
    - Pedido Confirmado (`order_confirmed`)
    - En Preparación (`order_in_process`)
    - En Camino / Delivery (`order_in_delivery`)
    - Pedido Completado (`order_done`)
  - **Formato de Teléfono Inteligente**:
    - Remoción automática del 0 inicial (11 dígitos → 10 dígitos)
    - Agregado automático de código de país
    - Ejemplo: `031999999999` → `5531999999999` (Brasil)
  - **Sistema de Logs**:
    - Registro completo de mensajes enviados
    - Meta de orden `_evolution_logs` con historial
    - Meta `_last_whatsapp_sent` con timestamp del último envío
    - Logs en error_log para debugging
  - **Endpoints AJAX Nuevos**:
    - `myd_evolution_auto_setup` - Ejecuta configuración automática completa
    - `myd_evolution_check_status` - Verifica estado en tiempo real
    - `myd_evolution_reconnect` - Reconectar instancia
    - `myd_evolution_reset` - Resetear instancia
    - `myd_evolution_test_connection` - Test de conexión
    - `myd_evolution_send_manual` - Envío manual (removido del frontend)
  - **Archivos Nuevos**:
    - `includes/integrations/evolution-api/class-instance-manager.php` - Gestor de instancias
    - `includes/integrations/evolution-api/class-evolution-client.php` - Cliente HTTP
    - `includes/integrations/evolution-api/class-whatsapp-service.php` - Servicio de mensajería
    - `includes/integrations/evolution-api/class-logger.php` - Sistema de logs
    - `includes/integrations/evolution-api/class-order-hooks.php` - Hooks automáticos
    - `includes/ajax/class-evolution-ajax.php` - Manejadores AJAX
    - `templates/admin/settings-tabs/evolution-api/tab-evolution-api.php` - UI de configuración
    - `assets/js/evolution-admin.js` - JavaScript para admin
    - `assets/css/evolution-api.css` - Estilos para UI
  - **Seguridad Implementada**:
    - Nonces de WordPress en todos los endpoints AJAX
    - Validación de capabilities (`manage_options`, `edit_posts`)
    - Sanitización de inputs con funciones de WordPress
    - API Key nunca expuesta en frontend
    - Prevención de duplicados (ventana de 5 minutos)

### Modificado

- **Carga de Assets Mejorada**:
  - Verificación doble por screen ID y parámetro GET para garantizar carga correcta
  - Assets solo se cargan en página de settings (optimización)
- **Integración con Plugin Principal**:
  - Registro automático de todas las clases de Evolution API
  - Inicialización condicional (solo si está habilitado)
  - Compatible con el sistema de WhatsApp existente (wa.me)

### Removido

- **Botón "Enviar WhatsApp" del Panel de Pedidos Frontend**:
  - Eliminado del shortcode de pedidos (`templates/order/panel.php`)
  - Funcionalidad ahora es 100% automática en el backend
  - Sin intervención manual requerida

### Corregido

- **Formato de teléfono corregido**: Ahora remueve el 0 inicial antes de enviar a WhatsApp
- **Screen ID corregido**: `myd-settings` → `myd-delivery-settings` para carga correcta de assets
- **Error 500 en AJAX**: Agregado include de `class-instance-manager.php` que faltaba

### Técnico

- **Versión Evolution API**: Compatible con v2.2.3
- **URL Evolution API**: `https://evo.guria.lat`
- **Endpoints utilizados**:
  - `POST /instance/create` - Crear instancia
  - `GET /instance/connect/{name}` - Obtener QR
  - `GET /instance/fetchInstances` - Listar instancias
  - `POST /message/sendText/{instance}` - Enviar mensaje
  - `DELETE /instance/logout/{name}` - Desconectar

---

## [2.2.21] - 2025-10-06

### Añadido

- **Sistema de Comprobantes de Pago** - Nueva funcionalidad completa para gestión de comprobantes
  - **Configuración activable/desactivable**: Checkbox en `Settings → Payment` para habilitar la funcionalidad
  - **Campo de subida en checkout**: Input de tipo file para subir comprobante (imagen o PDF)
  - **Validación obligatoria**: Cuando está activo, el comprobante es REQUERIDO para completar el pedido
  - **Procesamiento backend**: Almacenamiento automático en biblioteca de medios de WordPress
  - **Vista en panel de órdenes**: Botón "Ver Comprobante de Pago" para órdenes con estado 'new'
  - **Vista en tracking del cliente**: Cliente puede ver y descargar su comprobante subido
  - **Vista en admin (metabox)**: Preview de imagen con botón de descarga en edición de orden
  - **JavaScript personalizado**: `payment-receipt.js` con override de `MydOrder.placePayment()` para soporte de FormData
  - **Traducciones**: Todos los textos en español
  - **Tipos de archivo soportados**: Imágenes (JPG, PNG, GIF) y PDFs
  - Archivos modificados:
    - `templates/admin/settings-tabs/payment/tab-payment.php` - Configuración
    - `templates/cart/cart-payment.php` - Campo de subida
    - `includes/ajax/class-place-payment.php` - Procesamiento de archivo
    - `includes/custom-fields/class-register-custom-fields.php` - Registro de campo
    - `includes/custom-fields/class-custom-fields.php` - Renderizado personalizado
    - `templates/order/order-content.php` - Vista en panel de órdenes
    - `includes/fdm-track-order.php` - Vista en tracking
    - `assets/js/payment-receipt.js` - JavaScript handler (nuevo archivo)
    - `includes/class-plugin.php` - Registro de script
    - `includes/fdm-products-list.php` - Enqueue de script

## [2.2.20] - 2025-10-02

### Añadido

- **Moneda EURO (EUR) añadida a la lista de monedas disponibles** en configuración del plugin
  - Ahora puedes seleccionar EUR como moneda principal de tu tienda
  - Aparece en el selector de monedas junto a USD y VEF
- **Sistema de conversión automática EUR -> VEF (Bolívares)**:
  - Nueva función `get_eur_vef_rate()` para obtener tasa EUR a VEF
  - Nueva función `convert_eur_to_vef()` para convertir euros a bolívares
  - Nueva función `get_eur_vef_data()` para obtener datos completos (nombre, tasa, fecha)
  - Shortcode `[myd_eur_rate]` para mostrar tasa EUR->VEF en frontend
  - Cache de 30 minutos para optimizar rendimiento
  - API endpoint EUR: `https://webhooks.guria.lat/webhook/6ed6fb33-d736-43af-9038-7a7e2a2a1116`
- **Conversión inteligente según moneda configurada**:
  - Si la moneda es **EUR**: muestra conversión a VEF (Bolívares)
  - Si la moneda es **USD**: muestra conversión a VEF (Bolívares)
  - Si la moneda es **VEF**: no muestra conversión
  - Método universal `get_conversion()` detecta automáticamente qué conversión aplicar
  - Método `get_conversion_display()` genera HTML de conversión según moneda activa

### Cambiado

- **Sistema de actualizaciones automáticas DESHABILITADO**
  - `class-plugin.php:197-203` - Plugin update checker comentado por requerimiento
  - Sistema de licencias permanece funcional
  - Actualizaciones manuales siguen disponibles
- **Refactorización completa de `Currency_Converter`**:
  - Renombradas constantes: `API_URL_USD_VEF` y `API_URL_EUR_VEF` (antes API_URL_VEF y API_URL_EUR)
  - Renombradas claves de transient: `TRANSIENT_KEY_USD_VEF` y `TRANSIENT_KEY_EUR_VEF`
  - Métodos renombrados para claridad:
    - `get_usd_vef_rate()` (antes `get_official_rate()`)
    - `get_eur_vef_rate()` (antes `get_eur_rate()`)
    - `convert_eur_to_vef()` (nuevo - convierte EUR a VEF, no EUR a USD)
  - Métodos legacy mantenidos como aliases por compatibilidad hacia atrás
  - `fetch_data_from_api()` acepta 'USD' o 'EUR' como parámetro
  - `clear_rate_cache()` limpia cachés de ambas conversiones
  - `get_cache_info()` retorna info de USD->VEF y EUR->VEF

### Mejorado

- Documentación PHPDoc completamente actualizada
- Comentarios explicativos sobre conversiones EUR->VEF y USD->VEF
- Sistema de aliases para mantener retrocompatibilidad
- Limpieza de transients legacy al borrar cache

---

## [2.2.19] - 2025-08-26

### A�adido

- Sistema completo de gesti�n de clientes con `Customer` class y `Customer_Repository`
- Tracking de estad�sticas de clientes (pedidos totales, gasto total, tasa de retorno)
- Clasificaci�n autom�tica de clientes (Nuevo, Regular, Frecuente, VIP)
- Historial de direcciones de clientes con contador de uso
- Detecci�n de clientes en riesgo basado en inactividad
- API REST completa para gesti�n de clientes (`/myd-delivery/v1/customers`)
- Sistema de b�squeda de clientes por nombre o tel�fono
- C�lculo de valor promedio de pedidos por cliente
- Sistema mejorado de notificaciones de audio para nuevos pedidos
- Indicador de conexi�n en tiempo real para panel de pedidos
- Sistema de actualizaciones en tiempo real con manejo de errores mejorado
- Funci�n de actualizaci�n de estado de pago al imprimir �rdenes
- Manejo de errores de timeout en verificaciones de pedidos
- Soporte para pausar/reanudar actualizaciones autom�ticas seg�n visibilidad de p�gina
- Variables de configuraci�n de tienda inyectadas en JavaScript (`mydStoreInfo`)
- Soporte para 4 idiomas: Espa�ol, Italiano, Portugu�s (Brasil), Ingl�s

### Cambiado

- Refactorizaci�n completa del sistema de impresi�n de �rdenes
- Mejorado el sistema de polling de �rdenes a 8 segundos (antes sin l�mite definido)
- Actualizado el sistema de actualizaciones AJAX con mejor manejo de errores
- Optimizado el c�lculo de totales de clientes para incluir solo pedidos pagados
- Mejorada la consulta SQL de repositorio de clientes para mejor rendimiento
- Actualizado el manejo de notificaciones con mejor detecci�n de soporte del navegador
- Sistema de m�scaras de tel�fono migrado a formato m�s flexible
- Convertida la gesti�n de licencias a sistema basado en transients

### Corregido

- Correcci�n de c�lculo de `total_spent` en estad�sticas de clientes
- Arreglado el problema de doble-click en botones de impresi�n
- Solucionado el manejo de errores en consultas de base de datos de clientes
- Corregido el filtro de pedidos pagados en repositorio de clientes
- Mejorado el manejo de casos donde no hay clientes en la base de datos
- Arreglado el contenido de impresi�n que no se actualizaba despu�s de cambios de estado
- Solucionado el problema de actualizaciones m�ltiples simult�neas
- Corregida la detecci�n de nuevos pedidos para disparar notificaciones

### Seguridad

- � **CR�TICO**: C�digo de bypass de licencia presente en l�nea 72 (requiere eliminaci�n inmediata)
- A�adida verificaci�n de nonce en endpoints AJAX
- Implementada sanitizaci�n b�sica en entradas de formularios
- Validaci�n de permisos en endpoints de API REST con `manage_options`

---

## [2.2.x] - Versiones Anteriores

### Caracter�sticas Principales del Sistema (v1.9.6 - v2.2.18)

#### Sistema de Pedidos

- Custom Post Type `mydelivery-orders` para gesti�n de pedidos
- Estados de pedido: nuevo, confirmado, en proceso, listo, esperando, en entrega, finalizado, cancelado
- Estados de pago: esperando, pagado, fallido
- M�todos de entrega: delivery, take away, consumo en tienda
- Integraci�n con WhatsApp para env�o autom�tico de pedidos
- Panel de pedidos en tiempo real con actualizaciones autom�ticas
- Sistema de impresi�n de pedidos (ticket y comanda)
- Notificaciones de audio para nuevos pedidos
- Tracking de pedidos para clientes

#### Sistema de Productos

- Custom Post Type `myd-product` para productos
- Sistema de categor�as de productos
- Productos con precio, descripci�n e imagen
- Sistema de extras/complementos para productos
  - Grupos de extras con opciones m�ltiples
  - L�mites m�nimo y m�ximo de selecci�n
  - Precios adicionales por extra
  - Extras obligatorios y opcionales
- Visibilidad de productos (mostrar/ocultar)
- Precio "Por Consultar" para productos sin precio fijo

#### Sistema de Carrito

- Carrito persistente con sesi�n
- C�lculo autom�tico de totales
- Aplicaci�n de cupones de descuento
- C�lculo de precio de entrega seg�n m�todo seleccionado
- Validaci�n de monto m�nimo de compra
- Notas adicionales por producto

#### Sistema de Entrega

- M�ltiples m�todos de c�lculo de precio de entrega:
  - Precio fijo por barrio
  - Precio fijo por rango de c�digo postal
  - Precio por distancia (integraci�n con Google Maps API)
  - Precio variable por barrio
  - Precio por rango de c�digo postal
- Validaci�n de �reas de entrega
- Autocompletado de direcciones (Brasil)
- C�lculo de distancia en tiempo real
- Tiempo estimado de entrega

#### Sistema de Pagos

- Pagos contra entrega (efectivo)
- Integraci�n con pasarelas de pago externas
- M�todos de pago configurables
- C�lculo de cambio para pagos en efectivo
- Estados de pago rastreables

#### Sistema de Cupones

- Custom Post Type `mydelivery-coupons`
- Tipos de descuento:
  - Porcentaje (%)
  - Monto fijo ($)
- Cupones por tipo:
  - Descuento en productos
  - Descuento en entrega
- Validaci�n de cupones en checkout

#### Configuraciones de Tienda

- Informaci�n de la empresa (nombre, tel�fono, email, direcci�n)
- Configuraci�n de moneda con m�s de 150 monedas soportadas
- Separador decimal y n�mero de decimales configurables
- Horarios de apertura por d�a de la semana
- Forzar tienda abierta/cerrada
- Configuraci�n de precio m�nimo de pedido
- Redirecci�n autom�tica a WhatsApp
- M�scaras de tel�fono personalizables

#### Sistema de Campos Personalizados

- Framework extensible para campos personalizados
- Tipos de campos soportados:
  - Text, Textarea, Number
  - Select, Checkbox, Radio
  - Image/Media Library
  - Repeater (campos repetibles)
- Sistema de etiquetas (labels) personalizables
- Integraci�n con WordPress Media Library

#### API REST

- Endpoints para productos: `/myd-delivery/v1/products`
- Endpoints para pedidos: `/myd-delivery/v1/orders`
- Endpoints para clientes: `/myd-delivery/v1/customers`
- Endpoints para cupones: `/myd-delivery/v1/coupons`
- Endpoints para reportes: `/myd-delivery/v1/reports`
- Endpoints para configuraciones: `/myd-delivery/v1/settings`
- Endpoints para media: `/myd-delivery/v1/media`
- Server-Sent Events (SSE) para tracking de pedidos

#### Sistema de Reportes

- Dashboard con m�tricas clave:
  - Total de pedidos por per�odo
  - Ventas totales
  - Pedidos promedio por d�a
  - Ticket promedio
- Gr�ficos con Chart.js
- Filtros por fecha
- Estad�sticas de clientes
- Reportes de productos m�s vendidos

#### Panel de Administraci�n

- Dashboard principal con resumen
- Gesti�n de pedidos con filtros
- Gesti�n de productos con categor�as
- Gesti�n de clientes con historial
- Gesti�n de cupones
- Configuraciones centralizadas por pesta�as:
  - Empresa
  - Entrega
  - Pagos
  - Pedidos
  - Dise�o
  - Horarios
  - Avanzado
  - Impresi�n
  - Shortcodes

#### Sistema de Licencias (v1.9.x)

- Validaci�n de licencia con servidor remoto
- Estados: activa, desactivada, inv�lida
- Almacenamiento en transients (30 d�as)
- Sistema de auto-actualizaci�n
- Verificaci�n de URL del sitio
- Notificaciones de estado de licencia

#### Localizaci�n

- Text domain: `myd-delivery-pro`
- Traducciones completas:
  - Espa�ol (es_ES)
  - Italiano (it_IT)
  - Portugu�s Brasil (pt_BR)
  - Ingl�s (por defecto)
- Funciones de traducci�n WordPress est�ndar

#### Integraciones

- **WhatsApp**: Env�o autom�tico de pedidos
- **Google Maps API**: C�lculo de distancias y autocompletado
- **Chart.js**: Gr�ficos de reportes (CDN)
- **Print.js**: Impresi�n de pedidos (CDN)
- **WordPress Media Library**: Gesti�n de im�genes

#### Shortcodes Disponibles

```
[myd-products-list] - Lista de productos
[myd-cart] - Carrito de compras
[myd-order-panel] - Panel de pedidos (admin)
[myd-track-order] - Seguimiento de pedidos
[myd-currency-converter] - Conversor de moneda
```

#### Compatibilidad

- PHP: 7.4+
- WordPress: 5.5+
- Navegadores: Chrome, Firefox, Safari, Edge (�ltimas 2 versiones)

---

## [1.9.6] - Fecha Desconocida

### A�adido

- Implementaci�n del patr�n Singleton para clase principal
- Verificaci�n de versiones PHP y WordPress al activar
- Prevenci�n de clonaci�n y deserializaci�n de instancia del plugin
- Hooks de activaci�n y desactivaci�n con flush de rewrite rules
- Sistema de notificaciones de admin para versiones incompatibles

### Cambiado

- Refactorizaci�n de estructura de clases con namespaces
- Separaci�n de responsabilidades en clases especializadas
- Migraci�n de c�digo legacy a estructura moderna

---

## [1.x.x] - Versiones Iniciales

### Caracter�sticas Base

- Implementaci�n inicial del sistema de delivery
- Gesti�n b�sica de productos y pedidos
- Integraci�n inicial con WhatsApp
- Sistema de carrito b�sico
- Configuraciones fundamentales

---

## Tipos de Cambios

- **A�adido**: Para nuevas caracter�sticas
- **Cambiado**: Para cambios en funcionalidad existente
- **Obsoleto**: Para caracter�sticas que ser�n eliminadas
- **Eliminado**: Para caracter�sticas eliminadas
- **Corregido**: Para correcci�n de bugs
- **Seguridad**: Para vulnerabilidades de seguridad

---

## Notas de Migraci�n

### De v1.x a v2.x

- Se requiere PHP 7.4+ (antes 7.0+)
- Nuevo sistema de campos personalizados (migraci�n autom�tica desde legacy)
- Cambios en estructura de base de datos para pedidos
- API REST reemplaza algunos AJAX endpoints legacy

### Problemas Conocidos v2.2.19

1. **Seguridad**: C�digo de bypass de licencia presente (l�nea 72 archivo principal)
2. **Rendimiento**: Consultas N+1 en carrito de compras
3. **Compatibilidad**: Assets de CDN pueden fallar sin conexi�n
4. **UX**: JavaScript inline masivo dificulta debugging

---

## Roadmap Futuro

### v2.3.0 (Planificado)

- [ ] Eliminar c�digo de bypass de licencia
- [ ] Implementar autoloading PSR-4 con Composer
- [ ] Separar JavaScript inline a archivos externos
- [ ] A�adir tests unitarios (PHPUnit)
- [ ] Implementar rate limiting en API REST
- [ ] Capacidades personalizadas de WordPress

### v2.4.0 (Planificado)

- [ ] Sistema de build moderno (Webpack/Vite)
- [ ] WebSockets para actualizaciones real-time
- [ ] Lazy loading de productos
- [ ] Optimizaci�n de consultas de base de datos
- [ ] Transacciones de base de datos

### v3.0.0 (Planificado)

- [ ] Refactorizaci�n completa con arquitectura moderna
- [ ] Suite de testing completa (unit, integration, e2e)
- [ ] Sistema de cache avanzado
- [ ] Telemetr�a y monitoring
- [ ] CI/CD automatizado

---

## Soporte

Para reportar bugs o solicitar caracter�sticas:
- Website: https://myddelivery.com/
- Desarrollador: https://eduardovillao.me/

---

## Licencia

GPL v2.0+ - Consultar LICENSE.txt para m�s detalles

---

**�ltima actualizaci�n**: 26 de Agosto, 2025

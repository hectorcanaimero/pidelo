# Sistema de Notificaciones de Updates - Documentación

Documentación completa del sistema mejorado de notificaciones de actualización para MyD Delivery Pro.

## 🎯 Objetivo

Proporcionar múltiples canales de notificación para informar a los administradores sobre actualizaciones disponibles del plugin, mejorando la experiencia del usuario y facilitando el mantenimiento.

## 🏗️ Arquitectura

### Componentes del Sistema

```
┌──────────────────────────────────────────────────────────────────┐
│                  SISTEMA DE NOTIFICACIONES                        │
└──────────────────────────────────────────────────────────────────┘

1. Update Checker (Base)
   └─> Consulta GitHub Pages cada 12 horas
   └─> Valida licencia
   └─> Compara versiones

2. Dashboard Widget
   └─> Muestra estado en Dashboard
   └─> Features destacadas
   └─> Acciones rápidas

3. Email Notifications (Opt-in)
   └─> Email automático cuando hay update
   └─> HTML con diseño profesional
   └─> Changelog incluido

4. Auto-Updater (Opt-in)
   └─> Actualización automática
   └─> Respeta configuración global de WP
   └─> Seguro con rollback

5. Update History
   └─> Log de todas las actualizaciones
   └─> Éxitos y fallos
   └─> Exportable a CSV

6. Menu Badge
   └─> Notificación visual en menú
   └─> Solo con licencia válida
   └─> Actualización en tiempo real

7. Settings Page
   └─> Configuración centralizada
   └─> Estadísticas
   └─> Gestión de historial
```

## 📦 Archivos del Sistema

### Clases Principales

```
includes/plugin-update/
├── class-plugin-update.php              # Update checker base
├── class-update-dashboard-widget.php    # Dashboard widget
├── class-update-email-notification.php  # Email notifications
├── class-update-history.php             # Historial de updates
├── class-auto-updater.php               # Auto-actualización
├── class-update-menu-badge.php          # Badge en menú
└── class-update-settings-page.php       # Página de configuración
```

### Inicialización

En `includes/class-plugin.php`:

```php
// Initialize update notification system
new Plugin_Update\Update_Dashboard_Widget();
new Plugin_Update\Update_Email_Notification();
new Plugin_Update\Update_History();
new Plugin_Update\Auto_Updater();
new Plugin_Update\Update_Menu_Badge();

if ( is_admin() ) {
    new Plugin_Update\Update_Settings_Page();
}
```

## 🎨 Features Detalladas

### 1. Dashboard Widget

#### Descripción
Widget visual en el Dashboard de WordPress que muestra el estado de actualización del plugin.

#### Ubicación
Dashboard → MyD Delivery Pro - Estado de Actualización

#### Estados Posibles

**Estado 1: Actualizado** ✅
- Fondo verde
- Icono de check
- Mensaje: "Estás usando la última versión disponible"
- Botón: "Verificar Actualizaciones"

**Estado 2: Update Disponible** 🔔
- Fondo amarillo
- Icono de campana
- Versión actual vs disponible
- Lista de nuevas features (máximo 5)
- Botones: "Actualizar Ahora" + "Ver Changelog Completo"

**Estado 3: Licencia Requerida** 🔒
- Fondo rojo
- Warning box
- Mensaje: "Activa tu licencia para recibir actualizaciones"
- Link a página de licencia

#### Implementación

```php
namespace MydPro\Includes\Plugin_Update;

class Update_Dashboard_Widget {
    public function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
    }

    public function add_dashboard_widget() {
        if ( ! current_user_can( 'update_plugins' ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'myd_update_status_widget',
            __( 'MyD Delivery Pro - Estado de Actualización', 'myd-delivery-pro' ),
            array( $this, 'render_widget' )
        );
    }

    private function get_update_status() {
        // Obtener información de update
        // Validar licencia
        // Determinar estado
        // Retornar array con datos
    }

    private function extract_features_from_changelog( $changelog ) {
        // Parsear HTML del changelog
        // Extraer primeros 5 items
        // Retornar array
    }
}
```

#### Personalización

El widget incluye estilos inline que se pueden personalizar:

```php
<style>
.myd-update-widget { }
.myd-update-status.up-to-date { background: #d4edda; }
.myd-update-status.update-available { background: #fff3cd; }
.myd-update-status.error { background: #f8d7da; }
</style>
```

---

### 2. Email Notifications

#### Descripción
Sistema de notificaciones por email que alerta automáticamente a los administradores cuando hay una nueva actualización disponible.

#### Características

- **Opt-in**: Debe ser habilitado explícitamente por el administrador
- **No duplicado**: Solo envía un email por versión
- **Multi-admin**: Envía a todos los administradores del sitio
- **HTML profesional**: Diseño responsive con gradientes
- **Changelog incluido**: Muestra novedades de la versión
- **Links funcionales**: Botones para actualizar y ver changelog completo

#### Configuración

**Habilitar:**
```php
Update_Email_Notification::enable();
// O via settings page
```

**Deshabilitar:**
```php
Update_Email_Notification::disable();
```

**Verificar estado:**
```php
$enabled = Update_Email_Notification::is_enabled();
```

#### Email de Prueba

La settings page incluye botón para enviar email de prueba:

```php
Update_Email_Notification::send_test_email();
```

#### Estructura del Email

**Asunto:**
```
[Nombre del Sitio] Nueva actualización disponible - v2.4.0
```

**Cuerpo (HTML):**
- Header con gradiente morado
- Versión actual vs nueva (destacado)
- Changelog formateado
- Botones de acción (Actualizar / Ver Changelog)
- Footer con link para desactivar notificaciones

#### Hooks y Triggers

El email se envía automáticamente cuando WordPress detecta un update:

```php
add_action( 'set_site_transient_update_plugins', array( $this, 'maybe_send_notification' ) );
```

**Condiciones para envío:**
1. Email notifications habilitado
2. Hay update disponible para MyD Delivery Pro
3. No se ha enviado email para esta versión antes
4. Licencia es válida

#### Prevención de Duplicados

Se guarda la última versión notificada:

```php
const OPTION_LAST_SENT = 'myd_update_email_last_sent';

// Verificar antes de enviar
$last_sent = get_option( self::OPTION_LAST_SENT, '' );
if ( $last_sent === $new_version ) {
    return; // Ya notificado
}

// Guardar después de enviar
update_option( self::OPTION_LAST_SENT, $new_version );
```

#### Personalización del Email

Para personalizar el template del email, editar el método `send_update_email()` en `class-update-email-notification.php`.

**Variables disponibles:**
- `$new_version` - Nueva versión disponible
- `$current_version` - Versión instalada
- `$site_name` - Nombre del sitio
- `$site_url` - URL del sitio
- `$changelog` - Changelog en texto plano
- `$to` - Array de emails de administradores

#### Troubleshooting

**Email no se envía:**

```bash
# Test básico de WordPress mail
wp eval "wp_mail('test@example.com', 'Test', 'Test');"

# Verificar configuración
wp option get myd_update_email_enabled

# Ver logs
tail -f wp-content/debug.log | grep "MyD Update Email"
```

**Email va a spam:**

- Configurar SMTP plugin (WP Mail SMTP, Post SMTP)
- Verificar SPF y DKIM records
- Usar dominio real del sitio en From

---

### 3. Auto-Updater

#### Descripción
Permite que WordPress actualice automáticamente el plugin sin intervención manual.

#### ⚠️ Advertencias

- Usar con precaución en producción
- Recomendado solo con backups automáticos
- Puede causar downtime si actualización falla
- WordPress debe tener permisos de escritura

#### Configuración

**Habilitar:**
```php
update_option( 'myd_auto_update_enabled', '1' );
```

**Deshabilitar:**
```php
update_option( 'myd_auto_update_enabled', '0' );
```

**Verificar:**
```php
$enabled = Auto_Updater::is_enabled();
```

#### Cómo Funciona

WordPress ejecuta auto-updates via WP-Cron. El filtro `auto_update_plugin` controla qué plugins se actualizan:

```php
public function enable_auto_update( $update, $item ) {
    if ( isset( $item->plugin ) && $item->plugin === MYD_PLUGIN_BASENAME ) {
        if ( self::is_enabled() ) {
            return true; // Permitir auto-update
        }
    }
    return $update; // Default behavior
}
```

#### Frecuencia

WordPress verifica updates cada 12 horas por defecto. Para cambiar:

```php
// En wp-config.php
define( 'WP_AUTO_UPDATE_CORE', true );

// Forzar check inmediato
wp cron event run --due-now
```

#### Notificaciones Post-Update

Si email notifications está habilitado, se enviará un email después de la actualización automática.

#### Rollback en Caso de Fallo

WordPress automáticamente hace rollback si:
- Update falla al descargar
- ZIP está corrupto
- Instalación falla

El plugin permanece en la versión anterior.

#### Monitoreo

Ver updates automáticos en historial:

```bash
wp option get myd_update_history --format=json
```

#### Mejores Prácticas

1. **Backups**: Configurar backups diarios automáticos
2. **Staging**: Probar updates en staging primero
3. **Monitoring**: Usar servicio de uptime monitoring
4. **Logs**: Revisar logs después de auto-updates
5. **Desactivar en producción crítica**: Mejor actualizar manualmente

---

### 4. Update History

#### Descripción
Log completo de todas las actualizaciones del plugin, exitosas y fallidas.

#### Datos Almacenados

Cada entrada incluye:
```php
array(
    'version'     => '2.4.0',          // Versión instalada
    'timestamp'   => 1699999999,       // Unix timestamp
    'success'     => true,             // true/false
    'error'       => '',               // Mensaje de error si falla
    'user_id'     => 1,                // ID del usuario
    'user_login'  => 'admin',          // Login del usuario
    'site_url'    => 'https://...',    // URL del sitio
    'wp_version'  => '6.4',            // Versión de WordPress
    'php_version' => '8.1'             // Versión de PHP
)
```

#### Límite de Entradas

Máximo 50 entradas guardadas. Las más antiguas se eliminan automáticamente:

```php
const MAX_ENTRIES = 50;
```

#### Métodos Públicos

**Obtener historial:**
```php
$history = Update_History::get_history();
// Retorna array de entradas
```

**Última actualización exitosa:**
```php
$last = Update_History::get_last_successful_update();
// Retorna entry o null
```

**Actualizaciones fallidas:**
```php
$failed = Update_History::get_failed_updates();
// Retorna array de entries fallidas
```

**Estadísticas:**
```php
$stats = Update_History::get_statistics();
// Retorna:
// array(
//     'total' => 10,
//     'successful' => 9,
//     'failed' => 1,
//     'success_rate' => 90.0,
//     'last_update' => array(...)
// )
```

**Limpiar historial:**
```php
Update_History::clear_history();
```

**Exportar CSV:**
```php
$csv = Update_History::export_csv();
header( 'Content-Type: text/csv' );
echo $csv;
```

#### Logging Automático

El historial se registra automáticamente via hook:

```php
add_action( 'upgrader_process_complete', array( $this, 'log_update' ), 10, 2 );
```

Detecta si la actualización fue exitosa o falló y guarda la información.

#### UI en Settings Page

La settings page muestra:
- Tabla con últimas 20 actualizaciones
- Badges de color (verde = exitosa, rojo = fallida)
- Mensajes de error si los hay
- Botones para exportar y limpiar

#### Uso en Debugging

Para investigar problemas:

```bash
# Ver historial completo
wp option get myd_update_history --format=json | jq .

# Ver solo fallidas
wp option get myd_update_history --format=json | jq '.[] | select(.success == false)'

# Última actualización
wp option get myd_update_history --format=json | jq '.[0]'
```

---

### 5. Menu Badge

#### Descripción
Badge visual (número rojo) en el menú del plugin cuando hay actualización disponible.

#### Apariencia

Similar a los badges de WordPress para plugins/themes/comments:
```
MyD Delivery [1]
```

Donde `[1]` es un círculo rojo con el número.

#### Condiciones para Mostrar

Badge solo aparece si:
1. Hay update disponible (versión remota > versión local)
2. Licencia es válida y activa
3. Usuario tiene capability `update_plugins`

#### Implementación

Se agrega al menú via hook con prioridad alta:

```php
add_action( 'admin_menu', array( $this, 'add_update_badge' ), 999 );
```

**Código del badge:**
```php
$badge = ' <span class="update-plugins myd-update-badge"><span class="plugin-count">1</span></span>';
$menu[ $key ][0] .= $badge;
```

#### Estilos

Usa clases nativas de WordPress (`update-plugins`, `plugin-count`) más clase custom para ajustes:

```css
.myd-update-badge {
    display: inline-block;
    margin-left: 5px;
    vertical-align: top;
}
```

#### Actualización en Tiempo Real

El badge desaparece automáticamente después de actualizar porque:
1. WordPress refresca página después de update
2. `has_update()` retorna `false` (ya está actualizado)
3. Badge no se renderiza

#### Debugging

```php
// Verificar si debería mostrar badge
$widget = new Update_Menu_Badge();
var_dump( $widget->has_update() ); // private, usar reflection para testing
```

---

### 6. Settings Page

#### Descripción
Página centralizada para configurar todas las opciones del sistema de notificaciones y ver historial.

#### Ubicación

**Menú:** MyD Delivery → Actualizaciones
**URL:** `/wp-admin/admin.php?page=myd-update-settings`

#### Secciones

**1. Configuración de Notificaciones**

Checkboxes para:
- ☐ Enviar notificaciones por email
- ☐ Habilitar actualizaciones automáticas

Botones:
- "Guardar Configuración" (primary)
- "📧 Enviar Email de Prueba" (secondary, solo si emails habilitados)

**2. Estadísticas de Actualizaciones**

Grid de 4 cajas:
- Total Actualizaciones
- Exitosas (verde)
- Fallidas (rojo)
- Tasa de Éxito (porcentaje)

Más línea de texto con última actualización.

**3. Historial de Actualizaciones**

Tabla con columnas:
- Versión
- Fecha (con "hace X tiempo")
- Estado (badge)
- Usuario
- Entorno (WP + PHP versions)

Botones:
- "📥 Exportar como CSV"
- "🗑️ Limpiar Historial" (con confirmación)

#### Permisos

Solo accesible para usuarios con capability `manage_options` (administradores).

```php
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}
```

#### Form Handling

Usa nonces para seguridad:

```php
wp_nonce_field( 'myd_update_settings' );
check_admin_referer( 'myd_update_settings' );
```

Acciones disponibles:
- `save_settings` - Guarda configuración
- `test_email` - Envía email de prueba
- `clear_history` - Limpia historial
- `export_history` - Descarga CSV

#### Settings Errors

Usa WordPress Settings API para mensajes:

```php
add_settings_error(
    'myd_update_settings',
    'settings_saved',
    __( 'Configuración guardada exitosamente.', 'myd-delivery-pro' ),
    'success'
);

settings_errors( 'myd_update_settings' );
```

#### Estilos

La página incluye estilos inline personalizados para:
- Grid de estadísticas
- Tabla de historial
- Badges de estado
- Diseño responsive

## 🔧 Configuración

### Opciones de WordPress

Todas las configuraciones se guardan en `wp_options`:

```php
// Email notifications
'myd_update_email_enabled' => '1' | '0'
'myd_update_email_last_sent' => '2.4.0'

// Auto-update
'myd_auto_update_enabled' => '1' | '0'

// History
'myd_update_history' => array( ... )
```

### Via wp-cli

```bash
# Habilitar email notifications
wp option update myd_update_email_enabled '1'

# Habilitar auto-update
wp option update myd_auto_update_enabled '1'

# Ver historial
wp option get myd_update_history --format=json

# Limpiar historial
wp option delete myd_update_history

# Enviar email de prueba
wp eval "use MydPro\Includes\Plugin_Update\Update_Email_Notification; Update_Email_Notification::send_test_email();"
```

### Via PHP

```php
use MydPro\Includes\Plugin_Update\Update_Email_Notification;
use MydPro\Includes\Plugin_Update\Auto_Updater;
use MydPro\Includes\Plugin_Update\Update_History;

// Email
Update_Email_Notification::enable();
Update_Email_Notification::disable();
$enabled = Update_Email_Notification::is_enabled();

// Auto-update
$enabled = Auto_Updater::is_enabled();

// History
$history = Update_History::get_history();
$stats = Update_History::get_statistics();
Update_History::clear_history();
```

## 🧪 Testing

Ver documento completo: [UPDATE-NOTIFICATIONS-TESTING.md](UPDATE-NOTIFICATIONS-TESTING.md)

### Quick Tests

**Test Dashboard Widget:**
```bash
# Login como admin → Dashboard → Ver widget
```

**Test Email:**
```bash
# MyD Delivery → Actualizaciones → Enviar Email de Prueba
```

**Test Historial:**
```bash
# Actualizar plugin manualmente → Ver historial en settings page
```

**Test Badge:**
```bash
# Simular update disponible → Ver menú lateral
```

## 🐛 Troubleshooting

### Widget no aparece

**Causa:** Permisos insuficientes o widget oculto

**Solución:**
```php
// Verificar permisos
current_user_can( 'update_plugins' );

// Verificar widget en screen options
// Dashboard → Screen Options → Marcar widget
```

### Email no se recibe

**Causa:** Email server no configurado o email deshabilitado

**Solución:**
```bash
# Test email básico
wp eval "wp_mail('test@example.com', 'Test', 'Test');"

# Instalar plugin SMTP
wp plugin install wp-mail-smtp --activate

# Ver logs
tail -f wp-content/debug.log | grep "MyD Update Email"
```

### Badge no aparece

**Causa:** No hay update disponible o licencia inválida

**Solución:**
```bash
# Verificar licencia
wp transient get myd_license_data

# Verificar update disponible
wp transient get mydpro-update-data

# Limpiar cache
wp transient delete mydpro-update-data
```

### Historial no se guarda

**Causa:** Hook no registrado o permisos de DB

**Solución:**
```bash
# Verificar hook
wp hook list upgrader_process_complete

# Test manual
wp eval "
use MydPro\Includes\Plugin_Update\Update_History;
\$h = new Update_History();
\$h->add_entry(['version' => '9.9.9', 'success' => true, 'user_login' => 'test']);
"

# Ver opción
wp option get myd_update_history
```

### Auto-update no funciona

**Causa:** WP-Cron deshabilitado o permisos insuficientes

**Solución:**
```bash
# Verificar WP-Cron
wp eval "echo DISABLE_WP_CRON ? 'disabled' : 'enabled';"

# Ejecutar cron manualmente
wp cron event run --due-now

# Verificar permisos de archivos
ls -la wp-content/plugins/myd-delivery-pro/
```

## 📚 Referencias

- [WordPress Plugin Update API](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)
- [WordPress Dashboard Widgets](https://developer.wordpress.org/apis/handbook/dashboard-widgets/)
- [WordPress Email System](https://developer.wordpress.org/reference/functions/wp_mail/)
- [WordPress Auto-Updates](https://make.wordpress.org/core/2020/07/15/controlling-plugin-and-theme-auto-updates-ui-in-wordpress-5-5/)
- [WordPress Cron](https://developer.wordpress.org/plugins/cron/)

## 🔄 Changelog del Sistema

### v1.0.0 - 2025-11-10

**Implementado:**
- ✅ Dashboard Widget con 3 estados (actualizado, update disponible, licencia requerida)
- ✅ Email Notifications opt-in con HTML profesional
- ✅ Auto-Updater con safety checks
- ✅ Update History con límite de 50 entradas
- ✅ Settings Page centralizada con estadísticas
- ✅ Menu Badge con actualización automática
- ✅ Integración completa con sistema de licencias
- ✅ Exports de historial a CSV
- ✅ Email de prueba para configuración
- ✅ Documentación completa

**Pendiente (Futuro):**
- [ ] Notificación push en navegador
- [ ] Webhook notifications a Slack/Discord
- [ ] Scheduled updates (elegir fecha/hora)
- [ ] Rollback UI (revertir a versión anterior)
- [ ] Changelog diff visual
- [ ] A/B testing de updates en staging

---

**Última actualización:** 2025-11-10
**Versión:** 1.0.0
**Mantenido por:** PideAI Team

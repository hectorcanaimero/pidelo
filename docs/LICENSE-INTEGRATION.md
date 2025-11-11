# License Integration - Documentación

Esta documentación explica cómo funciona la integración del sistema de licencias con el update checker del plugin MyD Delivery Pro.

## 🎯 Objetivo

Asegurar que solo sitios con licencia válida puedan recibir actualizaciones del plugin, mientras se proporciona retroalimentación clara a los usuarios sobre el estado de su licencia.

## 🏗️ Arquitectura

### Componentes

1. **License System** (`includes/license/`)
   - `License` - Clase principal de gestión de licencias
   - `License_Manage_Data` - Gestión de transients y opciones
   - `License_Activate` / `License_Deactivate` - Acciones de licencia

2. **Update Checker** (`includes/plugin-update/class-plugin-update.php`)
   - Valida licencia antes de mostrar updates
   - Muestra admin notices según estado de licencia
   - Integra con WordPress update system

3. **WordPress Transients**
   - `myd_license_data` - Estado de licencia (30 días)
   - `mydpro-update-data` - Info de updates (12 horas)

## 🔄 Flujo de Validación

### 1. Usuario intenta actualizar

```
WordPress
  └─> pre_set_site_transient_update_plugins
      └─> Plugin_Update::update()
          └─> Plugin_Update::is_license_valid()
              ├─> Check license key existe
              ├─> Check license transient
              └─> Validate status === 'active'
          └─> Si válida: Mostrar update
          └─> Si inválida: Mostrar admin notice
```

### 2. Estados de Licencia

| Estado | Descripción | Permite Updates | Notice Type |
|--------|-------------|-----------------|-------------|
| `active` | Licencia activa | ✅ Sí | Ninguno |
| `expired` | Licencia expirada | ❌ No | Error (rojo) |
| `invalid` | Licencia inválida | ❌ No | Error (rojo) |
| `inactive` | Licencia no activada | ❌ No | Warning (amarillo) |
| No configurada | Sin licencia | ❌ No | Warning (amarillo) |

## 📝 Validación de Licencia

### Método `is_license_valid()`

```php
private function is_license_valid() {
    // 1. Check if license key exists
    if (empty($this->license_key)) {
        add_action('admin_notices', [$this, 'missing_license_notice']);
        return false;
    }

    // 2. Get license data from transient
    $license_data = License_Manage_Data::get_transient();

    if (!$license_data) {
        add_action('admin_notices', [$this, 'activate_license_notice']);
        return false;
    }

    // 3. Validate status
    if ($license_data['status'] !== 'active') {
        $status = $license_data['status'];
        add_action('admin_notices', [$this, 'inactive_license_notice_' . $status]);
        return false;
    }

    return true;
}
```

### Estructura de License Data

```php
// Transient: 'myd_license_data'
[
    'key' => 'XXXXX-XXXXX-XXXXX-XXXXX',
    'status' => 'active', // active, expired, invalid, inactive
    'site_url' => 'https://example.com',
]
```

## 🔔 Admin Notices

### 1. Sin Licencia Configurada

**Trigger:** `empty($this->license_key)`

**Notice:**
```
⚠️ MyD Delivery Pro: Necesitas activar una licencia para recibir actualizaciones del plugin.
[Activar Licencia]
```

**Acción:** Redirige a `admin.php?page=myd-license`

### 2. Licencia Necesita Activación

**Trigger:** `!$license_data` (transient no existe)

**Notice:**
```
⚠️ MyD Delivery Pro: Tu licencia necesita ser activada para recibir actualizaciones.
[Activar Ahora]
```

**Acción:** Redirige a página de licencia

### 3. Licencia Expirada

**Trigger:** `$license_data['status'] === 'expired'`

**Notice:**
```
❌ MyD Delivery Pro: Tu licencia ha expirado. Renueva tu licencia para seguir recibiendo actualizaciones y soporte.
[Renovar Licencia]
```

**Acción:** Abre página de renovación en nueva pestaña

### 4. Licencia Inválida

**Trigger:** `$license_data['status'] === 'invalid'`

**Notice:**
```
❌ MyD Delivery Pro: La licencia no es válida para este dominio. Verifica tu licencia o contacta con soporte.
[Revisar Licencia] [Contactar Soporte]
```

**Acciones:**
- Revisar: `admin.php?page=myd-license`
- Soporte: `https://pideai.com/soporte/`

### 5. Licencia Inactiva

**Trigger:** `$license_data['status'] === 'inactive'`

**Notice:**
```
⚠️ MyD Delivery Pro: Tu licencia no está activa. Actívala para recibir actualizaciones.
[Activar Licencia]
```

**Acción:** Redirige a página de licencia

## 🧪 Testing

### Test de Estados de Licencia

#### 1. Test Sin Licencia

```php
// 1. Eliminar licencia
delete_option('fdm-license');
delete_transient('myd_license_data');

// 2. Visitar admin
// Esperar: Notice "Necesitas activar una licencia"

// 3. Check updates
// URL: ?force-check=1
// Esperar: NO aparece notificación de update
```

#### 2. Test Licencia Activa

```php
// 1. Configurar licencia válida
update_option('fdm-license', 'VALID-LICENSE-KEY');
License_Manage_Data::set_transient('VALID-LICENSE-KEY', site_url(), 'active');

// 2. Check updates
// URL: ?force-check=1
// Esperar: SÍ aparece notificación si hay update

// 3. Verificar notices
// Esperar: NO aparece ningún notice de licencia
```

#### 3. Test Licencia Expirada

```php
// 1. Configurar licencia expirada
License_Manage_Data::set_transient('EXPIRED-KEY', site_url(), 'expired');

// 2. Visitar admin
// Esperar: Notice rojo "Tu licencia ha expirado"

// 3. Check updates
// URL: ?force-check=1
// Esperar: NO aparece notificación de update
```

#### 4. Test Licencia Inválida

```php
// 1. Configurar licencia inválida
License_Manage_Data::set_transient('INVALID-KEY', site_url(), 'invalid');

// 2. Visitar admin
// Esperar: Notice rojo "La licencia no es válida"

// 3. Verificar botones
// Esperar: Botón "Revisar Licencia" y "Contactar Soporte"
```

### Test de Transiciones

#### Activar Licencia

```bash
# 1. Sin licencia
# 2. Usuario ingresa licencia
# 3. Click "Activar"
# 4. Sistema valida con servidor
# 5. Set transient con status 'active'
# 6. Updates ahora disponibles
```

#### Renovar Licencia Expirada

```bash
# 1. Licencia expirada
# 2. Click "Renovar Licencia"
# 3. Redirige a pideai.com/renovar-licencia
# 4. Usuario renueva
# 5. Reactiva licencia
# 6. Status cambia a 'active'
# 7. Updates disponibles nuevamente
```

## 🐛 Debugging

### Verificar Estado de Licencia

```php
// En WordPress admin o wp-cli

// 1. Ver license key
$key = get_option('fdm-license');
echo "License Key: " . $key;

// 2. Ver transient
$data = get_transient('myd_license_data');
var_dump($data);

// Output esperado:
// array(
//   'key' => 'XXXXX-XXXXX...',
//   'status' => 'active',
//   'site_url' => 'https://example.com'
// )

// 3. Forzar revalidación
delete_transient('myd_license_data');
// Visitar admin para revalidar
```

### Habilitar Logs

```php
// En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Logs en wp-content/debug.log
```

### Errores Comunes

#### 1. Transient no se guarda

**Síntoma:** Licencia se valida pero notice aparece siempre

**Causa:** Transient expira o no se guarda correctamente

**Solución:**
```php
// Verificar que transient existe
$data = get_transient('myd_license_data');
if (false === $data) {
    // Re-activar licencia
    License_Manage_Data::set_transient($key, site_url(), 'active');
}
```

#### 2. Notice aparece múltiples veces

**Síntoma:** Multiple notices duplicados

**Causa:** `is_license_valid()` se llama varias veces

**Solución:**
```php
// Ya implementado - solo agrega notice una vez
// add_action solo ejecuta en primera llamada
```

#### 3. Update no aparece con licencia válida

**Síntoma:** Licencia activa pero no hay notificación de update

**Causas:**
- Cache de updates no expirado
- Versión en server no es mayor
- Error al consultar GitHub Pages

**Solución:**
```php
// Limpiar cache
delete_transient('mydpro-update-data');
delete_site_transient('update_plugins');

// Forzar check
// URL: ?force-check=1

// Verificar logs
tail -f wp-content/debug.log | grep "MyD"
```

## 🔒 Seguridad

### Validación Local vs Servidor

**Actual (GitHub Pages - Estático):**
- ✅ Validación local de licencia
- ✅ Download URL público
- ❌ No hay validación server-side

**Futuro (API Dinámica):**
- ✅ Validación local Y server-side
- ✅ Download URL con token de un solo uso
- ✅ Logs de intentos no autorizados

### Protección de Download URL

**Opción A: Token de Un Solo Uso** (Recomendado para producción)

```php
// En servidor
$token = wp_generate_password(32, false);
set_transient('download_token_' . $token, $license_key, 5 * MINUTE_IN_SECONDS);

$download_url = "https://api.pideai.com/download?token=" . $token;

// En endpoint de download
function handle_download($token) {
    $license_key = get_transient('download_token_' . $token);

    if (!$license_key) {
        wp_die('Token inválido o expirado');
    }

    // Validar licencia
    if (!validate_license($license_key)) {
        wp_die('Licencia inválida');
    }

    // Delete token (un solo uso)
    delete_transient('download_token_' . $token);

    // Servir archivo
    serve_plugin_zip();
}
```

**Opción B: Firma HMAC** (Actual)

```php
// Download URL incluye signature
$signature = hash_hmac('sha256', $license_key . $timestamp, SECRET_KEY);
$download_url .= "&signature=" . $signature . "&expires=" . $timestamp;
```

### Rate Limiting

```php
// Limitar intentos de validación
$attempts_key = 'license_validate_attempts_' . $ip;
$attempts = get_transient($attempts_key);

if ($attempts && $attempts > 5) {
    wp_die('Demasiados intentos. Intenta en 1 hora.');
}

set_transient($attempts_key, ($attempts ?: 0) + 1, HOUR_IN_SECONDS);
```

## 📈 Métricas y Logging

### Eventos a Trackear

```php
// 1. Intentos de update sin licencia
if (!$this->is_license_valid()) {
    do_action('myd_update_attempt_without_license', [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'site_url' => site_url(),
        'license_key' => $this->license_key,
    ]);
}

// 2. Updates exitosos
do_action('myd_update_successful', [
    'version_from' => $old_version,
    'version_to' => $new_version,
    'license_key' => $this->license_key,
]);

// 3. Licencias expiradas
if ($status === 'expired') {
    do_action('myd_license_expired', [
        'license_key' => $this->license_key,
        'site_url' => site_url(),
    ]);
}
```

## 🎯 Mejores Prácticas

### 1. Mensajes Claros

✅ **Bueno:**
```
"Tu licencia ha expirado. Renueva para seguir recibiendo actualizaciones."
[Renovar Licencia]
```

❌ **Malo:**
```
"Error: License status invalid"
```

### 2. CTAs Accionables

Siempre incluir botón con acción clara:
- ✅ "Activar Licencia" → `admin.php?page=myd-license`
- ✅ "Renovar Licencia" → URL de renovación
- ✅ "Contactar Soporte" → URL de soporte

### 3. Permisos

Solo mostrar notices a administradores:
```php
if (!current_user_can('manage_options')) {
    return;
}
```

### 4. Notices Dismissibles

Usar `is-dismissible` para que el usuario pueda cerrar:
```php
<div class="notice notice-warning is-dismissible">
```

### 5. No Bloquear Funcionalidad

Licencia solo afecta updates, no funcionalidad del plugin:
- ✅ Plugin sigue funcionando sin licencia
- ✅ Solo se bloquean updates
- ❌ NO desactivar features por falta de licencia

## 📚 Referencias

- [WordPress Options API](https://developer.wordpress.org/apis/handbook/options/)
- [WordPress Transients API](https://developer.wordpress.org/apis/handbook/transients/)
- [WordPress Admin Notices](https://developer.wordpress.org/reference/hooks/admin_notices/)
- [License System Documentation](LICENSE.md) (si existe)

## 🔄 Migración a API Dinámica

Cuando necesites migrar de GitHub Pages a API dinámica:

### Paso 1: Crear API Endpoint

```php
// En servidor externo
POST /api/check-update
{
  "license_key": "XXXXX-XXXXX",
  "domain": "https://example.com",
  "current_version": "2.3.8"
}

// Response
{
  "has_update": true,
  "version": "2.4.0",
  "download_url": "https://api.pideai.com/download?token=...",
  "changelog": "..."
}
```

### Paso 2: Actualizar Plugin_Update

```php
// En request()
$response = wp_remote_post(self::URL, [
    'body' => [
        'license_key' => $this->license_key,
        'domain' => $this->site_url,
        'current_version' => MYD_CURRENT_VERSION,
    ]
]);
```

### Paso 3: Validación Server-Side

El servidor valida la licencia antes de retornar download URL.

---

**Última actualización:** 2025-11-10
**Versión:** 1.0.0

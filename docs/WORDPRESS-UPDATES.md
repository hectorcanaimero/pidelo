# WordPress Update System - Documentación

Esta documentación explica cómo funciona el sistema de actualizaciones automáticas del plugin MyD Delivery Pro integrado con WordPress.

## 🎯 Objetivo

Permitir que WordPress detecte y descargue actualizaciones del plugin automáticamente desde el panel de administración, integrándose con el servidor de updates alojado en GitHub Pages.

## 🏗️ Arquitectura

### Componentes

1. **Update Server** (GitHub Pages)
   - Endpoint: `https://hectorcanaimero.github.io/pidelo/update-info.json`
   - Actualización automática via GitHub Actions
   - Ver documentación: [`UPDATE-SERVER.md`](UPDATE-SERVER.md)

2. **Plugin_Update Class** (`includes/plugin-update/class-plugin-update.php`)
   - Hook en WordPress update system
   - Consulta el update server
   - Valida licencias
   - Cachea respuestas

3. **WordPress Update System**
   - Transients para cache
   - Modal de detalles de plugin
   - Botón "Actualizar ahora"

## 🔄 Flujo de Actualización

### 1. WordPress Consulta Updates (Cada 12 horas)

```
WordPress
  └─> Hook: pre_set_site_transient_update_plugins
      └─> Plugin_Update::update()
          └─> Plugin_Update::request()
              ├─> Check cache (transient 'mydpro-update-data')
              ├─> Fetch from GitHub Pages
              ├─> Validate response
              └─> Cache for 12 hours
          └─> Compare versions
          └─> Check license (if configured)
          └─> Add to $transient->response if update available
```

### 2. Usuario ve notificación

WordPress detecta que hay una actualización disponible y muestra:
- Notificación en la lista de plugins
- Badge de número de actualizaciones en el menú
- Botón "Actualizar ahora"

### 3. Usuario hace click en "Ver detalles"

```
WordPress
  └─> Hook: plugins_api
      └─> Plugin_Update::info()
          └─> Fetch update info
          └─> Build plugin info object
          └─> Return to WordPress
  └─> WordPress muestra modal con:
      ├─> Description
      ├─> Installation instructions
      └─> Changelog
```

### 4. Usuario hace click en "Actualizar ahora"

```
WordPress
  └─> Download package from download_url
  └─> Verify ZIP file
  └─> Backup current plugin (if enabled)
  └─> Extract new version
  └─> Hook: upgrader_process_complete
      └─> Plugin_Update::purge()
          └─> Clear cache
  └─> Show success message
```

## 📝 Clase Plugin_Update

### Propiedades

```php
const URL = 'https://hectorcanaimero.github.io/pidelo/update-info.json';
private $license_key;      // License key from settings
private $site_url;          // Current site URL
private $already_forced;    // Prevent multiple force checks
```

### Métodos Principales

#### `__construct()`
Inicializa hooks de WordPress:
- `plugins_api` - Información del plugin
- `site_transient_update_plugins` - Check de updates
- `upgrader_process_complete` - Limpieza post-update

#### `request()`
Obtiene información de updates desde GitHub Pages:

```php
// URL consultada
https://hectorcanaimero.github.io/pidelo/update-info.json

// Respuesta esperada
{
  "version": "2.4.0",
  "download_url": "https://github.com/.../releases/.../plugin.zip",
  "requires": "5.5",
  "tested": "6.4",
  "requires_php": "7.4",
  "sections": {
    "description": "...",
    "changelog": "..."
  }
}
```

**Cache:**
- Transient: `mydpro-update-data`
- Duración: 12 horas
- Force check: `?force-check=1` en URL

#### `update($transient)`
Compara versiones y agrega update al transient:

```php
if (version_compare(CURRENT_VERSION, $new_version, '<')) {
    // Check license
    if (!$this->is_license_valid()) {
        return $transient; // Don't show update
    }

    // Add to update response
    $transient->response[$basename] = $plugin_data;
}
```

#### `info($result, $action, $args)`
Provee información para el modal de detalles:

```php
// WordPress calls this when user clicks "Ver detalles"
return $plugin_info; // Object with name, version, sections, etc.
```

#### `is_license_valid()`
Valida licencia antes de mostrar updates:

```php
// Check license transient
$license_data = License_Manage_Data::get_transient();

// Verify status
return $license_data['status'] === 'active';
```

## 🔐 Integración con Licencias

### Configuración

En `is_license_valid()`:

```php
// Permitir updates sin licencia (para testing)
if (empty($this->license_key)) {
    return true; // Cambiar a false para requerir licencia
}
```

### Estados de Licencia

| Estado | Updates Permitidos | Comportamiento |
|--------|-------------------|----------------|
| No configurado | ✅ Sí | Permite updates (testing) |
| Activa | ✅ Sí | Updates normales |
| Inactiva/Expirada | ❌ No | Oculta notificaciones |
| Inválida | ❌ No | Oculta notificaciones |

### Forzar Validación de Licencia

Para requerir licencia válida en producción:

```php
// En is_license_valid()
if (empty($this->license_key)) {
    return false; // Cambiado de true a false
}
```

## 🧪 Testing

### Test Manual

#### 1. Verificar que el endpoint funciona

```bash
curl https://hectorcanaimero.github.io/pidelo/update-info.json

# Debería retornar JSON válido con version, download_url, etc.
```

#### 2. Simular nueva versión

En `update-info.json`, incrementa la versión:

```json
{
  "version": "99.0.0",
  ...
}
```

Sube a GitHub Pages y espera 1-2 minutos.

#### 3. Forzar check de updates en WordPress

Opción A - Via URL:
```
http://tu-sitio.com/wp-admin/plugins.php?force-check=1
```

Opción B - Via transient:
```php
// En wp-admin o wp-cli
delete_transient('mydpro-update-data');
delete_site_transient('update_plugins');
```

Opción C - Via código:
```php
\MydPro\Includes\Plugin_Update\Plugin_Update::delete_plugin_update_transient();
wp_clean_plugins_cache();
```

#### 4. Verificar notificación

- Ve a **Plugins** en WordPress
- Deberías ver "Hay una nueva versión disponible"
- Deberías ver el botón "Actualizar ahora"

#### 5. Probar modal de detalles

- Click en "Ver detalles de versión"
- Verifica que se muestre:
  - Descripción correcta
  - Changelog
  - Versión requerida de WordPress/PHP

### Test de Licencias

#### Sin licencia configurada

```php
// Debería permitir updates (por defecto)
```

#### Con licencia inválida

```php
// 1. Configurar licencia inválida
// 2. Verificar que NO aparezca notificación de update
// 3. Forzar check: ?force-check=1
// 4. Confirmar que sigue sin aparecer
```

#### Con licencia válida

```php
// 1. Configurar licencia válida
// 2. Verificar que SÍ aparezca notificación
// 3. Debe poder actualizar normalmente
```

### Test de Cache

```php
// 1. Primera carga - Debe hacer request al servidor
// Log: "Fetching from GitHub Pages"

// 2. Segunda carga (dentro de 12 horas) - Debe usar cache
// Log: "Using cached update data"

// 3. Force check - Debe ignorar cache
// URL: ?force-check=1
// Log: "Forcing update check"
```

## 🐛 Debugging

### Habilitar Logs

Agregar al `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Logs se guardan en: `wp-content/debug.log`

### Buscar Errores de Update Check

```bash
tail -f wp-content/debug.log | grep "MyD Update"
```

### Errores Comunes

#### 1. No aparece notificación de update

**Causas:**
- Cache no ha expirado (esperar 12 horas o forzar)
- Versión en server no es mayor que la instalada
- Licencia inválida está bloqueando updates
- Error de red al consultar GitHub Pages

**Solución:**
```php
// Limpiar cache
delete_transient('mydpro-update-data');
delete_site_transient('update_plugins');

// Forzar check
// URL: ?force-check=1

// Verificar logs
tail -f wp-content/debug.log | grep "MyD Update"
```

#### 2. Modal de detalles vacío/con errores

**Causas:**
- Formato incorrecto en `sections` del JSON
- HTML mal formado en changelog
- Campos faltantes en update-info.json

**Solución:**
```bash
# Validar JSON
curl https://hectorcanaimero.github.io/pidelo/update-info.json | jq .

# Verificar campos requeridos
jq '{version, download_url, sections}' update-info.json
```

#### 3. Error al descargar update

**Causas:**
- URL de descarga inválida
- Release no publicado en GitHub
- ZIP no adjunto al release
- Permisos de GitHub

**Solución:**
```bash
# Verificar que el release existe
gh release view v2.4.0

# Verificar que el ZIP está adjunto
gh release view v2.4.0 --json assets

# Probar descarga manual
curl -L https://github.com/.../releases/download/v2.4.0/plugin.zip -o test.zip
```

#### 4. Update se descarga pero falla instalación

**Causas:**
- ZIP corrupto
- Estructura de carpetas incorrecta
- Permisos de archivos
- Conflicto con otro plugin

**Solución:**
```bash
# Verificar estructura del ZIP
unzip -l myd-delivery-pro.zip

# Debe contener:
# myd-delivery-pro/
# ├── myd-delivery-pro.php
# ├── includes/
# └── ...

# NO debe ser:
# myd-delivery-pro-main/  ❌
# src/myd-delivery-pro/   ❌
```

## 🔧 Configuración Avanzada

### Cambiar Frecuencia de Cache

```php
// En request()
set_transient('mydpro-update-data', $response, 12 * HOUR_IN_SECONDS);

// Cambiar a 6 horas:
set_transient('mydpro-update-data', $response, 6 * HOUR_IN_SECONDS);

// Cambiar a 1 día:
set_transient('mydpro-update-data', $response, DAY_IN_SECONDS);
```

### Agregar Header de Autenticación

Si migras a un servidor que requiere autenticación:

```php
$response = wp_remote_get(
    self::URL,
    array(
        'timeout' => 10,
        'headers' => array(
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $api_token,
        ),
    )
);
```

### Validar Signature del Package

Para mayor seguridad:

```php
// En el servidor, genera signature:
$signature = hash_hmac('sha256', file_get_contents('plugin.zip'), SECRET_KEY);

// En update-info.json:
{
  "download_url": "...",
  "package_signature": "abc123..."
}

// En el plugin, valida antes de instalar:
$downloaded_file = download_url(...);
$calculated_sig = hash_hmac('sha256', file_get_contents($downloaded_file), SECRET_KEY);

if ($calculated_sig !== $package_signature) {
    throw new Exception('Invalid package signature');
}
```

## 📚 Referencias

- [WordPress Plugin Update API](https://developer.wordpress.org/plugins/plugin-basics/determining-plugin-and-content-directories/)
- [Transients API](https://developer.wordpress.org/apis/handbook/transients/)
- [Plugin Update Checker Library](https://github.com/YahnisElsts/plugin-update-checker)
- [Update Server Documentation](UPDATE-SERVER.md)

## 🆘 Soporte

Si tienes problemas:

1. Revisar logs: `wp-content/debug.log`
2. Verificar cache: `?force-check=1`
3. Validar endpoint: `curl https://...update-info.json`
4. Revisar licencia: Admin → MyD License
5. Abrir issue en GitHub con logs completos

---

**Última actualización:** 2025-11-10
**Versión:** 1.0.0

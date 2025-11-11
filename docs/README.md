# Sistema de Auto-Actualización - MyD Delivery Pro

Documentación completa del sistema de actualizaciones automáticas para el plugin WordPress MyD Delivery Pro.

## 🎯 Descripción General

Este sistema permite que el plugin MyD Delivery Pro se actualice automáticamente desde el panel de administración de WordPress, sin necesidad de usar el repositorio oficial de WordPress.org. Utiliza GitHub Pages como servidor de updates y GitHub Actions para automatización.

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLUJO DE ACTUALIZACIÓN                        │
└─────────────────────────────────────────────────────────────────┘

1. Developer publica Release en GitHub
   │
   ├─> GitHub Actions se ejecuta automáticamente
   │   ├─> Extrae versión del tag (v2.4.0 → 2.4.0)
   │   ├─> Lee info del plugin (requires, tested, requires_php)
   │   ├─> Extrae changelog de release notes
   │   ├─> Valida que el ZIP exista en release
   │   ├─> Genera update-info.json
   │   ├─> Valida estructura JSON
   │   └─> Publica a branch gh-pages
   │
   └─> GitHub Pages sirve update-info.json públicamente
       │
       └─> WordPress consulta cada 12 horas
           ├─> Plugin_Update::request() fetch JSON
           ├─> Compara versiones (Semantic Versioning)
           ├─> Valida licencia activa
           └─> Muestra notificación si hay update
               │
               └─> Usuario click "Actualizar ahora"
                   ├─> WordPress descarga ZIP desde GitHub
                   ├─> Extrae y reemplaza archivos
                   └─> Plugin actualizado ✅
```

## 📦 Componentes del Sistema

### Core: Sistema de Actualización

### 1. Update Server (GitHub Pages)

**Endpoint**: `https://hectorcanaimero.github.io/pidelo/update-info.json`

**Características**:
- Hosting gratuito y confiable
- Sin necesidad de servidor dinámico
- CDN global de GitHub
- Actualización automática via GitHub Actions

**Documentación**: [UPDATE-SERVER.md](UPDATE-SERVER.md)

### 2. Update Checker (WordPress Plugin)

**Clase**: `includes/plugin-update/class-plugin-update.php`

**Funciones**:
- Integración con WordPress Update API
- Cache de 12 horas con transients
- Validación de licencias
- Admin notices según estado de licencia
- Modal de detalles con changelog

**Documentación**: [WORDPRESS-UPDATES.md](WORDPRESS-UPDATES.md)

### 3. License Integration

**Clase**: `includes/license/class-license-manage-data.php`

**Estados de Licencia**:
- ✅ `active` - Updates habilitados
- ⚠️ `inactive` - Necesita activación
- ❌ `expired` - Licencia vencida
- ❌ `invalid` - Licencia no válida para dominio
- ⚠️ No configurada - Sin license key

**Documentación**: [LICENSE-INTEGRATION.md](LICENSE-INTEGRATION.md)

### 4. Automation Workflow

**Archivo**: `.github/workflows/update-info.yml`

**Triggers**:
- Automático: Al publicar release
- Manual: Via workflow_dispatch

**Características**:
- Changelog inteligente (release notes → CHANGELOG.md → git log)
- Validación de ZIP
- Validación de JSON
- Notificaciones de fallos

**Documentación**: [AUTOMATION-WORKFLOW.md](AUTOMATION-WORKFLOW.md)

### Enhancement: Sistema de Notificaciones

#### 5. Dashboard Widget

**Ubicación:** WordPress Dashboard

**Características**:
- Estado visual del plugin (actualizado / update disponible / licencia requerida)
- Comparación de versiones (actual vs disponible)
- Lista de nuevas features
- Acciones rápidas (Actualizar / Ver Changelog)

**Documentación**: [UPDATE-NOTIFICATIONS.md](UPDATE-NOTIFICATIONS.md)

#### 6. Email Notifications (Opt-in)

**Características**:
- Alertas automáticas por email cuando hay updates
- Email HTML profesional con diseño responsive
- Changelog incluido en el email
- Envío a todos los administradores
- Configurable desde settings page
- Email de prueba para verificar configuración

#### 7. Auto-Updater (Opt-in)

**Características**:
- Actualización automática sin intervención manual
- Respeta configuración global de WordPress
- Seguro con rollback automático si falla
- ⚠️ Recomendado solo con backups automáticos

#### 8. Update History

**Características**:
- Log de todas las actualizaciones (exitosas y fallidas)
- Máximo 50 entradas guardadas
- Estadísticas (total, exitosas, fallidas, tasa de éxito)
- Export a CSV para análisis
- Información detallada (versión, usuario, entorno, errores)

#### 9. Menu Badge

**Características**:
- Badge visual en menú del plugin
- Solo aparece cuando hay update disponible
- Requiere licencia válida
- Usa estilos nativos de WordPress

#### 10. Settings Page

**Ubicación:** MyD Delivery → Actualizaciones

**Características**:
- Configuración centralizada de notificaciones
- Toggle para email notifications y auto-update
- Dashboard de estadísticas
- Visualización de historial completo
- Exportación de datos

**Documentación Completa**: [UPDATE-NOTIFICATIONS.md](UPDATE-NOTIFICATIONS.md)
**Testing Guide**: [UPDATE-NOTIFICATIONS-TESTING.md](UPDATE-NOTIFICATIONS-TESTING.md)

## 🚀 Quick Start

### Para Desarrolladores

#### 1. Setup Inicial

```bash
# Crear branch gh-pages
./scripts/setup-gh-pages.sh

# Habilitar GitHub Pages
# Settings → Pages → Source: gh-pages → Save
```

#### 2. Publicar Release

```bash
# Opción A: Via GitHub CLI
gh release create v2.4.0 \
  --title "Version 2.4.0" \
  --notes "## Features
- Nueva funcionalidad X
- Mejora en Y

## Bug Fixes
- Fix de Z" \
  myd-delivery-pro.zip

# Opción B: Via GitHub UI
# 1. Ir a Releases
# 2. Click "Draft a new release"
# 3. Elegir tag: v2.4.0
# 4. Llenar título y descripción
# 5. Adjuntar myd-delivery-pro.zip
# 6. Click "Publish release"
```

El workflow de GitHub Actions se ejecutará automáticamente y actualizará `update-info.json`.

#### 3. Verificar Publicación

```bash
# Esperar 2-3 minutos
sleep 180

# Verificar versión
curl https://hectorcanaimero.github.io/pidelo/update-info.json | jq .version

# Debería mostrar: "2.4.0"
```

### Para Usuarios Finales

#### 1. Activar Licencia

```
WordPress Admin → MyD License → Ingresar key → Activar
```

#### 2. Verificar Updates

WordPress verifica automáticamente cada 12 horas. Para forzar:

```
Plugins → MyD Delivery Pro → Check for updates
```

O via URL:
```
/wp-admin/plugins.php?force-check=1
```

#### 3. Actualizar Plugin

Cuando aparezca notificación "Nueva versión disponible":

1. Click en "Ver detalles de versión" (opcional)
2. Click en "Actualizar ahora"
3. WordPress descarga e instala automáticamente
4. Confirmar mensaje de éxito

## 📖 Documentación Completa

### Documentos por Tema

| Documento | Descripción | Audiencia |
|-----------|-------------|-----------|
| [UPDATE-SERVER.md](UPDATE-SERVER.md) | Arquitectura del servidor de updates | Developers |
| [WORDPRESS-UPDATES.md](WORDPRESS-UPDATES.md) | Integración con WordPress | Developers |
| [LICENSE-INTEGRATION.md](LICENSE-INTEGRATION.md) | Sistema de licencias | Developers |
| [AUTOMATION-WORKFLOW.md](AUTOMATION-WORKFLOW.md) | GitHub Actions workflow | DevOps |
| [RELEASE-PROCESS.md](RELEASE-PROCESS.md) | Proceso de release paso a paso | Developers |
| [TROUBLESHOOTING.md](TROUBLESHOOTING.md) | Solución de problemas | Developers/Support |
| [ERROR-CODES.md](api/ERROR-CODES.md) | Códigos de error API | Developers |

### Por Caso de Uso

#### "Quiero publicar una nueva versión"
→ Lee: [RELEASE-PROCESS.md](RELEASE-PROCESS.md)

#### "WordPress no detecta mi update"
→ Lee: [TROUBLESHOOTING.md](TROUBLESHOOTING.md) → "WordPress No Detecta Updates"

#### "Quiero entender cómo funciona el sistema"
→ Lee: [UPDATE-SERVER.md](UPDATE-SERVER.md) + [WORDPRESS-UPDATES.md](WORDPRESS-UPDATES.md)

#### "El workflow de GitHub falló"
→ Lee: [AUTOMATION-WORKFLOW.md](AUTOMATION-WORKFLOW.md) → "Debugging"

#### "Problemas con licencias"
→ Lee: [LICENSE-INTEGRATION.md](LICENSE-INTEGRATION.md) + [TROUBLESHOOTING.md](TROUBLESHOOTING.md) → "Problemas de Licencia"

#### "Quiero migrar a API dinámica"
→ Lee: [UPDATE-SERVER.md](UPDATE-SERVER.md) → "Migración a API Dinámica"

## 🧪 Testing

### Test Rápido

```bash
# 1. Verificar endpoint
curl https://hectorcanaimero.github.io/pidelo/update-info.json | jq .

# 2. Simular versión antigua en WordPress
# En myd-delivery-pro.php temporalmente:
# define('MYD_CURRENT_VERSION', '1.0.0');

# 3. Limpiar cache
wp transient delete mydpro-update-data
wp transient delete update_plugins

# 4. Verificar en WordPress
# Plugins → Debería aparecer notificación de update
```

### Test de Licencias

```bash
# Sin licencia
wp option delete fdm-license
wp transient delete myd_license_data
# → Esperar: Warning notice

# Con licencia activa
wp option update fdm-license "VALID-KEY"
# En admin, activar licencia
# → Esperar: Updates disponibles

# Con licencia expirada
# Configurar transient con status 'expired'
# → Esperar: Error notice rojo
```

## 🐛 Debugging

### Habilitar Logs

```php
// En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Ver Logs en Tiempo Real

```bash
tail -f wp-content/debug.log | grep -i "myd\|update\|license"
```

### Comandos Útiles

```bash
# Ver transients de updates
wp transient list | grep update

# Limpiar todo el cache
wp transient delete --all

# Ver versión actual del plugin
wp plugin get myd-delivery-pro --field=version

# Ver versión en servidor
curl -s https://hectorcanaimero.github.io/pidelo/update-info.json | jq -r '.version'

# Ver estado de licencia
wp transient get myd_license_data

# Forzar revalidación de licencia
wp transient delete myd_license_data
```

## 🔒 Seguridad

### Actual (GitHub Pages)

**✅ Ventajas**:
- Hosting confiable y gratuito
- CDN global
- HTTPS nativo
- Sin mantenimiento de servidor

**⚠️ Limitaciones**:
- Update info es pública
- Download URL es pública (GitHub Releases)
- No hay validación server-side de licencias
- No hay logs de descargas

**Mitigación**:
- Validación local de licencias antes de mostrar updates
- Admin notices bloquean acceso a updates sin licencia válida
- Monitoreo via GitHub Actions notifications

### Futuro (API Dinámica)

Cuando se necesite mayor control:

```php
// API endpoint con validación server-side
POST /api/check-update
{
  "license_key": "XXXXX",
  "domain": "https://example.com",
  "current_version": "2.3.8"
}

// Response con download token de un solo uso
{
  "has_update": true,
  "version": "2.4.0",
  "download_url": "https://api.pideai.com/download?token=...",
  "expires": 1699999999
}
```

Ver: [UPDATE-SERVER.md](UPDATE-SERVER.md) → "Migración a API Dinámica"

## 📊 Métricas

### Via GitHub

```bash
# Ver ejecuciones del workflow
gh run list --workflow=update-info.yml

# Ver releases publicados
gh release list

# Ver descargas de releases (aproximado)
gh release view v2.4.0 --json assets
```

### Via WordPress

```php
// Trackear updates exitosos
add_action('upgrader_process_complete', function($upgrader, $options) {
    if ($options['type'] === 'plugin' &&
        in_array('myd-delivery-pro/myd-delivery-pro.php', $options['plugins'])) {

        // Log update exitoso
        error_log("MyD Pro updated successfully to " . MYD_CURRENT_VERSION);

        // Opcional: Enviar a analytics
        // wp_remote_post('https://analytics.pideai.com/track', [...]);
    }
}, 10, 2);
```

## 🎯 Mejores Prácticas

### Releases

1. **Semantic Versioning**: Usar MAJOR.MINOR.PATCH (2.4.0, no 2.4)
2. **Tags con 'v' prefix**: v2.4.0 (el workflow lo limpia automáticamente)
3. **Release Notes Estructurados**: Usar formato markdown con headers (### Features, ### Bug Fixes)
4. **Adjuntar ZIP**: Siempre incluir myd-delivery-pro.zip en el release
5. **Releases Públicos**: No usar draft releases para producción

### Updates

1. **Cache de 12 horas**: Balance entre freshness y carga del servidor
2. **Force Check con Cuidado**: `?force-check=1` solo para debugging
3. **Backups**: Recomendar a usuarios hacer backup antes de actualizar
4. **Compatibilidad**: Probar con WordPress 5.5+ y PHP 7.4+
5. **Rollback Plan**: Mantener releases anteriores disponibles

### Licencias

1. **Mensajes Claros**: Admin notices con CTAs accionables
2. **Grace Period**: Considerar período de gracia después de expiración
3. **No Bloquear Funcionalidad**: Solo updates, no features del plugin
4. **Revalidación**: Transient de 30 días para balance UX/seguridad
5. **Soporte**: Proveer links claros a renovación y soporte

## 🆘 Soporte

### Problemas Comunes

| Síntoma | Solución Rápida | Documentación |
|---------|----------------|---------------|
| No aparece update | `?force-check=1` | [TROUBLESHOOTING.md](TROUBLESHOOTING.md) |
| Modal vacío | Validar JSON | [TROUBLESHOOTING.md](TROUBLESHOOTING.md) |
| Error al descargar | Verificar ZIP en release | [TROUBLESHOOTING.md](TROUBLESHOOTING.md) |
| Workflow falló | Ver logs de Action | [AUTOMATION-WORKFLOW.md](AUTOMATION-WORKFLOW.md) |
| License notice | Activar/renovar licencia | [LICENSE-INTEGRATION.md](LICENSE-INTEGRATION.md) |

### Contacto

- **Issues**: [GitHub Issues](https://github.com/hectorcanaimero/pidelo/issues)
- **Soporte**: https://pideai.com/soporte/
- **Documentación**: Este directorio `/docs/`

## 🔄 Changelog del Sistema

### 2025-11-10 - v1.0.0

**Implementado**:
- ✅ Update server en GitHub Pages
- ✅ WordPress Update Checker integrado
- ✅ Validación de licencias con admin notices
- ✅ GitHub Actions automation workflow
- ✅ Documentación completa
- ✅ Scripts de setup y testing

**Pendiente (Futuro)**:
- [ ] API dinámica con validación server-side
- [ ] Download URLs con tokens de un solo uso
- [ ] Dashboard de analytics de updates
- [ ] Tests automatizados (PHPUnit)
- [ ] Multi-environment support (staging/prod)
- [ ] Rollback automático en caso de fallo

## 📚 Referencias Externas

- [WordPress Plugin Update API](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)
- [GitHub Pages Documentation](https://docs.github.com/en/pages)
- [GitHub Actions Workflow Syntax](https://docs.github.com/en/actions/reference/workflow-syntax-for-github-actions)
- [Semantic Versioning](https://semver.org/)
- [WordPress Transients API](https://developer.wordpress.org/apis/handbook/transients/)
- [WordPress Admin Notices](https://developer.wordpress.org/reference/hooks/admin_notices/)

---

**Última actualización**: 2025-11-10
**Versión del Sistema**: 1.0.0
**Mantenido por**: PideAI Team

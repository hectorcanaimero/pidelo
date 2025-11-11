# Troubleshooting Guide

Guía completa de solución de problemas para el sistema de auto-actualización de MyD Delivery Pro.

## 🎯 Índice

- [WordPress No Detecta Updates](#wordpress-no-detecta-updates)
- [Modal de Detalles Vacío](#modal-de-detalles-vacío)
- [Error al Descargar Update](#error-al-descargar-update)
- [Update Falla al Instalar](#update-falla-al-instalar)
- [Problemas de Licencia](#problemas-de-licencia)
- [GitHub Actions Falló](#github-actions-falló)
- [update-info.json No se Actualiza](#update-infojson-no-se-actualiza)
- [Problemas con GitHub Pages](#problemas-con-github-pages)

---

## WordPress No Detecta Updates

### Síntomas
- No aparece notificación de "Nueva versión disponible"
- Badge de updates no se muestra
- Plugin aparece actualizado cuando no lo está

### Causas Posibles

#### 1. Cache No Ha Expirado

**Verificar:**
```bash
# Via wp-cli
wp transient get mydpro-update-data

# Via PHP (en wp-admin)
var_dump(get_transient('mydpro-update-data'));
```

**Solución:**
```bash
# Limpiar cache
wp transient delete mydpro-update-data
wp transient delete update_plugins

# O via URL
http://tu-sitio.com/wp-admin/plugins.php?force-check=1
```

#### 2. Versión en Servidor No es Mayor

**Verificar:**
```bash
# Ver versión actual instalada
wp plugin get myd-delivery-pro --field=version

# Ver versión en update server
curl -s https://hectorcanaimero.github.io/pidelo/update-info.json | jq -r '.version'
```

**Solución:**
- Asegurarse de que la versión en `update-info.json` sea mayor que la instalada
- Verificar que se usó Semantic Versioning correctamente (2.4.0 > 2.3.8)

#### 3. Licencia Inválida Bloque Updates

**Verificar:**
```php
// Check license data
$license = get_transient('myd_license_data');
var_dump($license);

// Expected:
// array('key' => 'XXX', 'status' => 'active', ...)
```

**Solución:**
```bash
# Opción 1: Activar licencia desde admin
# → MyD License página

# Opción 2: Temporalmente desactivar validación
# En includes/plugin-update/class-plugin-update.php
# Línea 207: return true; // Comentar resto del método
```

#### 4. Error de Red al Consultar Servidor

**Verificar logs:**
```bash
tail -f wp-content/debug.log | grep "MyD Update"
```

**Error común:**
```
MyD Update Check Error: cURL error 28: Connection timed out
```

**Solución:**
```bash
# Probar acceso directo
curl -v https://hectorcanaimero.github.io/pidelo/update-info.json

# Si falla, verificar:
# - Firewall del servidor
# - SSL certificates
# - DNS resolution
```

---

## Modal de Detalles Vacío

### Síntomas
- Click en "Ver detalles de versión" abre modal vacío
- Modal muestra "Loading..." infinitamente
- Error JavaScript en consola

### Causas Posibles

#### 1. Formato Incorrecto en `sections`

**Verificar:**
```bash
curl -s https://hectorcanaimero.github.io/pidelo/update-info.json | jq '.sections'
```

**Problema:** HTML mal formado
```json
{
  "changelog": "<h4>Version<ul><li>Item"  // ← Faltan tags de cierre
}
```

**Solución:**
```json
{
  "changelog": "<h4>Version</h4><ul><li>Item</li></ul>"
}
```

#### 2. Campos Faltantes

**Verificar:**
```bash
curl -s https://hectorcanaimero.github.io/pidelo/update-info.json | \
  jq '{description, installation, changelog: .sections | keys}'
```

**Solución:**
Asegurarse de que `sections` tiene al menos:
- `description`
- `changelog`

#### 3. JSON Inválido

**Verificar:**
```bash
curl -s https://hectorcanaimero.github.io/pidelo/update-info.json | jq empty
```

**Si falla:**
```bash
# Ver error exacto
curl -s https://hectorcanaimero.github.io/pidelo/update-info.json | jq .
# → parse error: Invalid string...
```

---

## Error al Descargar Update

### Síntomas
- Error: "Download failed"
- Error: "Could not download package"
- HTTP 404 en download URL

### Causas Posibles

#### 1. ZIP No Existe en Release

**Verificar:**
```bash
# Check release assets
gh release view v2.4.0 --json assets

# Test download URL
curl -I "https://github.com/hectorcanaimero/pidelo/releases/download/v2.4.0/myd-delivery-pro.zip"
# → Should return HTTP 302 → 200
```

**Solución:**
```bash
# Subir ZIP manualmente
gh release upload v2.4.0 myd-delivery-pro.zip

# O re-ejecutar GitHub Action
gh workflow run release.yml -f version=2.4.0
```

#### 2. Permisos de Descarga

**Síntoma:**
```
HTTP 403 Forbidden
```

**Solución:**
- Verificar que el release sea público (no draft)
- Verificar que el repositorio sea público

```bash
# Verificar visibilidad
gh release view v2.4.0 --json isDraft,isPrerelease
```

#### 3. URL Incorrecta

**Verificar:**
```bash
# Ver URL en update-info.json
curl -s https://hectorcanaimero.github.io/pidelo/update-info.json | \
  jq -r '.download_url'

# Formato correcto:
# https://github.com/USER/REPO/releases/download/vX.X.X/plugin.zip
```

---

## Update Falla al Instalar

### Síntomas
- Download OK pero falla al extraer
- Error: "The package could not be installed"
- WordPress muestra error genérico

### Causas Posibles

#### 1. Estructura del ZIP Incorrecta

**Verificar:**
```bash
unzip -l myd-delivery-pro.zip | head -20
```

**Estructura CORRECTA:**
```
myd-delivery-pro/
myd-delivery-pro/myd-delivery-pro.php
myd-delivery-pro/includes/
myd-delivery-pro/templates/
...
```

**Estructura INCORRECTA:**
```
myd-delivery-pro-main/           ❌ Nombre incorrecto
  myd-delivery-pro.php
...

src/myd-delivery-pro/            ❌ Carpeta extra
  myd-delivery-pro.php
...
```

**Solución:**
Regenerar ZIP con estructura correcta:
```bash
# Crear directorio temporal
mkdir -p /tmp/build/myd-delivery-pro

# Copiar archivos
cp -r . /tmp/build/myd-delivery-pro/

# Crear ZIP
cd /tmp/build
zip -r myd-delivery-pro.zip myd-delivery-pro/
```

#### 2. Permisos de Archivos

**Error:**
```
Warning: fopen(): failed to open stream: Permission denied
```

**Solución:**
```bash
# Verificar permisos en servidor
ls -la wp-content/plugins/myd-delivery-pro/

# Corregir si es necesario
chmod 755 wp-content/plugins/myd-delivery-pro
chmod 644 wp-content/plugins/myd-delivery-pro/myd-delivery-pro.php
```

#### 3. Plugin Activo Durante Update

**Solución:**
WordPress debería desactivar automáticamente, pero si falla:
```bash
# Desactivar antes de actualizar
wp plugin deactivate myd-delivery-pro

# Actualizar
wp plugin update myd-delivery-pro

# Reactivar
wp plugin activate myd-delivery-pro
```

---

## Problemas de Licencia

### Notice: "Necesitas activar una licencia"

**Causa:** No hay license key configurada

**Verificar:**
```bash
wp option get fdm-license
# → Should return license key
```

**Solución:**
```bash
# Configurar licencia
# Admin → MyD License → Ingresar key → Activar

# O via wp-cli
wp option update fdm-license "YOUR-LICENSE-KEY"
```

### Notice: "Tu licencia ha expirado"

**Causa:** License transient tiene status 'expired'

**Verificar:**
```php
var_dump(get_transient('myd_license_data'));
// ['status'] => 'expired'
```

**Solución:**
1. Renovar licencia en pideai.com/renovar-licencia
2. Reactivar en WordPress
3. Verificar transient actualizado

### Notice: "La licencia no es válida"

**Causa:** Licencia no coincide con dominio

**Verificar:**
```php
$license = get_transient('myd_license_data');
echo "Site URL: " . site_url() . "\n";
echo "License Site: " . $license['site_url'] . "\n";
```

**Solución:**
- Verificar que el dominio es correcto
- Re-generar licencia para el dominio correcto
- Contactar soporte si persiste

---

## GitHub Actions Falló

### Workflow "Update Info" Falló

**Ver logs:**
```bash
gh run list --workflow=update-info.yml --limit 1
gh run view {run-id} --log
```

#### Error: "Invalid JSON structure"

**Causa:** Changelog con caracteres especiales

**Solución:**
```yaml
# En .github/workflows/update-info.yml
# Asegurarse de que changelog usa jq -Rs
CHANGELOG=$(echo "$CHANGELOG" | jq -Rs .)
```

#### Error: "Missing required field"

**Causa:** Campo faltante en JSON

**Verificar:**
```bash
# Ver JSON generado en logs del workflow
# Buscar: "Generated update-info.json:"
```

**Solución:**
- Verificar que el plugin file tiene todos los headers
- Asegurarse de que GitHub Actions puede leerlos

#### Error: "gh-pages branch doesn't exist"

**Solución:**
```bash
# Crear branch gh-pages
./scripts/setup-gh-pages.sh

# O manualmente
git checkout --orphan gh-pages
git rm -rf .
echo '{"version": "1.0.0"}' > update-info.json
git add update-info.json
git commit -m "Initial gh-pages"
git push origin gh-pages
```

---

## update-info.json No se Actualiza

### GitHub Action OK pero JSON no cambia

**Causa:** GitHub Pages tarda en actualizar

**Solución:**
```bash
# Esperar 2-3 minutos
sleep 180

# Verificar con cache bypass
curl -H "Cache-Control: no-cache" \
  https://hectorcanaimero.github.io/pidelo/update-info.json | jq .version
```

### GitHub Pages No Está Habilitado

**Verificar:**
```
Settings → Pages → Source debe mostrar "gh-pages"
```

**Solución:**
1. Settings → Pages
2. Source: Deploy from a branch
3. Branch: gh-pages / (root)
4. Save

### JSON Tiene Versión Antigua

**Verificar:**
```bash
# Ver último commit en gh-pages
git log origin/gh-pages --oneline -1

# Ver contenido en GitHub
curl https://raw.githubusercontent.com/hectorcanaimero/pidelo/gh-pages/update-info.json | jq .version
```

**Solución:**
```bash
# Push manual a gh-pages
git checkout gh-pages
# Editar update-info.json manualmente
git add update-info.json
git commit -m "Update to version X.X.X"
git push origin gh-pages
```

---

## Problemas con GitHub Pages

### Error 404 en update-info.json

**Verificar:**
```bash
curl -I https://hectorcanaimero.github.io/pidelo/update-info.json
# → Should return 200, not 404
```

**Soluciones:**

1. **Verificar branch:**
```bash
git branch -r | grep gh-pages
# → Should show origin/gh-pages
```

2. **Verificar archivo existe:**
```bash
curl https://raw.githubusercontent.com/hectorcanaimero/pidelo/gh-pages/update-info.json
```

3. **Rebuild Pages:**
```bash
git checkout gh-pages
git commit --allow-empty -m "Trigger rebuild"
git push origin gh-pages
```

### Pages Deployment Falló

**Ver deployments:**
```
https://github.com/hectorcanaimero/pidelo/deployments
```

**Ver logs:**
Click en deployment fallido → Ver detalles

**Solución común:**
```bash
# Verificar CNAME no existe (causa conflictos)
git checkout gh-pages
rm CNAME
git commit -am "Remove CNAME"
git push origin gh-pages
```

---

## 🛠️ Comandos Útiles

### Debugging General

```bash
# Limpiar TODO el cache
wp transient delete --all

# Ver todos los transients de updates
wp transient list | grep update

# Forzar WordPress a recargar plugins
wp plugin list

# Ver debug log en tiempo real
tail -f wp-content/debug.log | grep -i "myd\|update\|license"
```

### Simular Update Check

```php
// En wp-admin o wp-shell
delete_transient('mydpro-update-data');
delete_site_transient('update_plugins');

// Trigger update check
wp_update_plugins();

// Ver resultado
$updates = get_site_transient('update_plugins');
var_dump($updates->response['myd-delivery-pro/myd-delivery-pro.php']);
```

### Testing con Versiones

```php
// Forzar versión "vieja" para testing
define('MYD_CURRENT_VERSION', '1.0.0'); // En myd-delivery-pro.php

// Refresh admin
// → Debería mostrar update disponible
```

---

## 📞 Obtener Ayuda

Si el problema persiste después de intentar estas soluciones:

1. **Revisar documentación:**
   - [UPDATE-SERVER.md](UPDATE-SERVER.md)
   - [WORDPRESS-UPDATES.md](WORDPRESS-UPDATES.md)
   - [LICENSE-INTEGRATION.md](LICENSE-INTEGRATION.md)

2. **Recopilar información:**
   ```bash
   # WordPress version
   wp core version

   # PHP version
   php -v

   # Plugin version
   wp plugin get myd-delivery-pro

   # Debug log (últimas 50 líneas)
   tail -50 wp-content/debug.log

   # Update server response
   curl https://hectorcanaimero.github.io/pidelo/update-info.json

   # License status
   wp transient get myd_license_data
   ```

3. **Crear issue en GitHub:**
   - Incluir toda la información recopilada
   - Describir pasos para reproducir
   - Adjuntar screenshots si es relevante

---

**Última actualización:** 2025-11-10
**Versión:** 1.0.0

# Update Server - Documentación

Esta documentación explica cómo funciona el servidor de actualizaciones del plugin MyD Delivery Pro utilizando GitHub Pages.

## 🎯 Objetivo

Proporcionar un endpoint público que WordPress pueda consultar para verificar si hay actualizaciones disponibles del plugin.

## 🏗️ Arquitectura

### Opción Implementada: GitHub Pages

- **Costo:** $0 (Gratis)
- **Complejidad:** Baja
- **Actualización:** Automática vía GitHub Actions
- **Disponibilidad:** 99.9%+

### Componentes

1. **update-info.json** - Archivo JSON con información de la última versión
2. **GitHub Pages** - Hosting estático del archivo JSON
3. **GitHub Actions** - Automatización de actualización del archivo

## 📡 Endpoint

```
https://hectorcanaimero.github.io/pidelo/update-info.json
```

### Estructura de Respuesta

```json
{
  "name": "MyD Delivery Pro",
  "slug": "myd-delivery-pro",
  "version": "2.3.8",
  "download_url": "https://github.com/hectorcanaimero/pidelo/releases/download/v2.3.8/myd-delivery-pro.zip",
  "requires": "5.5",
  "tested": "6.4",
  "requires_php": "7.4",
  "last_updated": "2025-11-10",
  "author": "PideAI",
  "author_profile": "https://pideai.com",
  "homepage": "https://pideai.com",
  "banners": {
    "low": "",
    "high": ""
  },
  "sections": {
    "description": "Sistema completo de gestión de delivery...",
    "installation": "<h4>Instalación</h4>...",
    "changelog": "<h4>2.3.8</h4><ul><li>Feature X</li></ul>"
  },
  "icons": {
    "1x": "",
    "2x": ""
  }
}
```

## 🔄 Flujo de Actualización

### 1. Creación de Release en GitHub

```bash
# 1. Crear tag y release
git tag v2.4.0
git push origin v2.4.0

# 2. Crear release en GitHub con el ZIP del plugin
gh release create v2.4.0 \
  --title "Version 2.4.0" \
  --notes "Changelog..." \
  myd-delivery-pro.zip
```

### 2. GitHub Actions (Automático)

Cuando se publica un release:

1. ✅ Se activa el workflow `update-info.yml`
2. ✅ Extrae la versión del release (v2.4.0 → 2.4.0)
3. ✅ Lee información del plugin (requires, tested, requires_php)
4. ✅ Genera changelog automático
5. ✅ Crea `update-info.json`
6. ✅ Publica en branch `gh-pages`
7. ✅ GitHub Pages actualiza automáticamente

### 3. WordPress Consulta el Endpoint

El plugin en WordPress consulta periódicamente:

```php
$response = wp_remote_get('https://hectorcanaimero.github.io/pidelo/update-info.json');
$update_info = json_decode(wp_remote_retrieve_body($response), true);

if (version_compare($current_version, $update_info['version'], '<')) {
    // Hay actualización disponible
    // Mostrar notificación en WordPress
}
```

## 🚀 Setup Inicial

### Paso 1: Habilitar GitHub Pages

1. Ve a **Settings** → **Pages** en tu repositorio
2. Selecciona **Source:** Deploy from a branch
3. Selecciona **Branch:** `gh-pages` / `(root)`
4. Click en **Save**

### Paso 2: Crear Branch gh-pages

```bash
# Crear branch orphan (sin historial)
git checkout --orphan gh-pages

# Limpiar archivos
git rm -rf .

# Crear archivo inicial
cat > update-info.json << 'EOF'
{
  "name": "MyD Delivery Pro",
  "slug": "myd-delivery-pro",
  "version": "2.3.8",
  "download_url": "https://github.com/hectorcanaimero/pidelo/releases/download/v2.3.8/myd-delivery-pro.zip",
  "requires": "5.5",
  "tested": "6.4",
  "requires_php": "7.4",
  "last_updated": "2025-11-10",
  "author": "PideAI",
  "author_profile": "https://pideai.com",
  "homepage": "https://pideai.com",
  "sections": {
    "description": "Sistema completo de gestión de delivery",
    "changelog": "<h4>2.3.8</h4><ul><li>Initial version</li></ul>"
  }
}
EOF

# Commit y push
git add update-info.json
git commit -m "Initial update-info.json"
git push origin gh-pages

# Volver a main
git checkout main
```

### Paso 3: Verificar que Funciona

```bash
# Espera 1-2 minutos y prueba:
curl https://hectorcanaimero.github.io/pidelo/update-info.json
```

## 🔧 Uso Manual (Sin GitHub Actions)

Si prefieres actualizar manualmente:

```bash
# 1. Checkout gh-pages
git checkout gh-pages

# 2. Editar update-info.json
vim update-info.json

# 3. Commit y push
git add update-info.json
git commit -m "Update to version 2.4.0"
git push origin gh-pages

# 4. Volver a main
git checkout main
```

## 🧪 Testing

### Probar el Endpoint

```bash
# 1. Verificar que responde
curl -I https://hectorcanaimero.github.io/pidelo/update-info.json

# 2. Ver contenido
curl https://hectorcanaimero.github.io/pidelo/update-info.json | jq .

# 3. Verificar versión específica
curl https://hectorcanaimero.github.io/pidelo/update-info.json | jq -r '.version'
```

### Probar desde WordPress

```php
// En wp-admin o wp-cli
$url = 'https://hectorcanaimero.github.io/pidelo/update-info.json';
$response = wp_remote_get($url);

if (is_wp_error($response)) {
    echo 'Error: ' . $response->get_error_message();
} else {
    $data = json_decode(wp_remote_retrieve_body($response), true);
    echo 'Latest version: ' . $data['version'];
}
```

## 🔐 CORS

GitHub Pages automáticamente incluye los headers CORS necesarios:

```
Access-Control-Allow-Origin: *
```

No se requiere configuración adicional.

## 📊 Monitoreo

### Verificar que GitHub Pages está activo

1. Ve a **Settings** → **Pages**
2. Deberías ver: "Your site is published at https://..."

### Ver Deployments

1. Ve a **Actions** → **pages-build-deployment**
2. Verifica que los deployments sean exitosos

### Logs del Workflow

1. Ve a **Actions** → **Update Plugin Info**
2. Click en el último run
3. Revisa los logs de cada step

## 🆘 Troubleshooting

### Problema: 404 en el endpoint

**Solución:**
```bash
# Verificar que gh-pages existe
git branch -r | grep gh-pages

# Verificar que update-info.json existe en gh-pages
git checkout gh-pages
ls -la update-info.json
```

### Problema: GitHub Action no se ejecuta

**Solución:**
1. Verificar permisos en **Settings** → **Actions** → **General**
2. Asegurarse de que "Allow GitHub Actions to create and approve pull requests" está habilitado
3. Verificar que el workflow está en `.github/workflows/`

### Problema: JSON inválido

**Solución:**
```bash
# Validar JSON localmente
cat update-info.json | jq .

# Si hay error, corregir y volver a commitear
```

### Problema: Download URL inválida

**Verificar:**
1. El release existe en GitHub
2. El ZIP está adjunto al release
3. El nombre del archivo coincide con el esperado

## 🚀 Mejoras Futuras

### Opción 1: API Serverless

Si necesitas funcionalidad dinámica:

```javascript
// Vercel Serverless Function
export default async function handler(req, res) {
  // Consultar GitHub Releases API
  const release = await fetch('https://api.github.com/repos/hectorcanaimero/pidelo/releases/latest');
  const data = await release.json();

  // Generar respuesta dinámica
  res.json({
    version: data.tag_name.replace('v', ''),
    download_url: data.assets[0].browser_download_url,
    // ...
  });
}
```

### Opción 2: Validación de Licencias

Si el plugin es comercial:

```php
// Endpoint que valida licencia antes de dar URL de descarga
POST /api/check-update
{
  "license_key": "xxxx-xxxx-xxxx",
  "domain": "example.com"
}
```

### Opción 3: Estadísticas de Uso

Tracking de cuántas instalaciones consultan updates:

```javascript
// Log cada request (serverless)
await analytics.track({
  event: 'update_check',
  version: currentVersion,
  domain: req.headers.referer
});
```

## 📚 Referencias

- [GitHub Pages Documentation](https://docs.github.com/en/pages)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [WordPress Plugin Update API](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)

## 📞 Soporte

Si tienes problemas:

1. Revisa los logs de GitHub Actions
2. Verifica que GitHub Pages está habilitado
3. Consulta esta documentación
4. Abre un issue en el repositorio

---

**Última actualización:** 2025-11-10
**Versión:** 1.0.0

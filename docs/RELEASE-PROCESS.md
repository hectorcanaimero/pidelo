# Release Process

Este documento describe el proceso para crear releases del plugin MyD Delivery Pro con empaquetado automático.

## Pre-requisitos

Antes de crear un release, asegúrate de:

1. ✅ Actualizar el número de versión en `myd-delivery-pro.php`:
   - Línea 8: `Version: X.X.X`
   - Línea 28: `define( 'MYD_CURRENT_VERSION', 'X.X.X' );`

2. ✅ Actualizar el `CHANGELOG.md` con:
   - Fecha del release
   - Nuevas características
   - Bugs corregidos
   - Cambios importantes

3. ✅ Verificar que todos los tests pasen (si existen)

4. ✅ Hacer commit de los cambios:
   ```bash
   git add myd-delivery-pro.php CHANGELOG.md
   git commit -m "Bump version to X.X.X"
   git push origin main
   ```

## Proceso de Release

### Opción 1: Desde la interfaz de GitHub (Recomendado)

1. Ve a https://github.com/hectorcanaimero/pidelo/releases

2. Click en "Draft a new release"

3. En "Choose a tag":
   - Escribe el tag de la nueva versión: `v2.3.9` (incluye la "v")
   - Click en "Create new tag: v2.3.9 on publish"

4. Título del release: `Version 2.3.9` o descripción corta

5. En la descripción, agrega las notas del release:
   ```markdown
   ## 🎉 What's New

   - Nueva funcionalidad X
   - Mejora en Y

   ## 🐛 Bug Fixes

   - Corrección de error Z

   ## 📦 Installation

   Download `myd-delivery-pro-2.3.9.zip` below and install via WordPress admin.
   ```

6. Click en "Publish release"

7. **Automático**: GitHub Actions se ejecutará y:
   - ✅ Creará el package ZIP del plugin
   - ✅ Excluirá archivos de desarrollo
   - ✅ Subirá el ZIP como asset del release

8. En 1-2 minutos, verás el archivo `myd-delivery-pro-2.3.9.zip` en los assets del release

### Opción 2: Desde la línea de comandos

```bash
# 1. Crear el tag
git tag -a v2.3.9 -m "Version 2.3.9"

# 2. Push el tag
git push origin v2.3.9

# 3. Crear el release en GitHub
gh release create v2.3.9 \
  --title "Version 2.3.9" \
  --notes "Release notes aquí"

# 4. GitHub Actions se ejecuta automáticamente
```

## Verificación del Release

Después de crear el release, verifica:

1. ✅ El workflow de GitHub Actions completó exitosamente:
   - Ve a: https://github.com/hectorcanaimero/pidelo/actions
   - Verifica que el workflow "Build and Release Plugin" tenga ✓ verde

2. ✅ El ZIP fue generado y subido:
   - Ve al release en https://github.com/hectorcanaimero/pidelo/releases
   - Verifica que existe el asset `myd-delivery-pro-X.X.X.zip`

3. ✅ Descarga y prueba el ZIP:
   - Descarga el ZIP del release
   - Descomprime y verifica que contiene los archivos correctos
   - Instala en un WordPress de prueba para verificar funcionamiento

## Contenido del ZIP

El workflow **incluye** estos archivos:
- `myd-delivery-pro.php` - Archivo principal
- `includes/` - Código del plugin
- `assets/` - CSS, JS, imágenes
- `templates/` - Plantillas PHP
- `languages/` - Traducciones
- `LICENSE` - Licencia
- `README.txt` - Documentación WordPress
- `CHANGELOG.md` - Historial de cambios

El workflow **excluye** estos archivos:
- `.git/`, `.github/`, `.gitignore` - Git
- `.claude/`, `CLAUDE.md` - Claude Code
- `tests/`, `phpunit.xml.dist` - Tests
- `docs/` - Documentación de desarrollo
- `TODO.md`, `REVIEW.md` - Notas de desarrollo
- `api.http` - Testing de API
- `composer.json`, `composer.lock` - Composer
- `.DS_Store` - Archivos de sistema

## Troubleshooting

### El workflow no se ejecuta

- Verifica que el release está en estado "published", no "draft"
- Revisa los permisos de GitHub Actions en: Settings → Actions → General
- Verifica que el archivo `.github/workflows/release.yml` existe en la rama main

### El ZIP no contiene los archivos correctos

- Revisa el log del workflow en la pestaña "Actions"
- Modifica las exclusiones en `.github/workflows/release.yml` si es necesario
- Re-ejecuta el workflow desde la interfaz de Actions

### Error al subir el ZIP

- Verifica que `GITHUB_TOKEN` tiene permisos adecuados
- Re-crea el release si es necesario
- Contacta a un administrador del repositorio

## Rollback

Si necesitas revertir un release:

```bash
# 1. Eliminar el release (mantiene el tag)
gh release delete v2.3.9

# 2. Eliminar el tag
git tag -d v2.3.9
git push --delete origin v2.3.9

# 3. Crear nuevo release con versión corregida
```

## Próximos Pasos

Una vez que el sistema de releases funcione correctamente:

1. Implementar API de updates (Issue #28)
2. Integrar update checker en el plugin (Issue #29)
3. Configurar validación de licencias (Issue #30)

## Referencias

- [GitHub Actions Workflow Syntax](https://docs.github.com/en/actions/reference/workflow-syntax-for-github-actions)
- [GitHub Releases](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository)
- [WordPress Plugin Header](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)

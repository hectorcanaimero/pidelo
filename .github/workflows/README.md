# GitHub Actions Workflows

Este directorio contiene los workflows de GitHub Actions para automatizar procesos del plugin.

## Workflows Disponibles

### `release.yml` - Build and Release Plugin

Workflow que se ejecuta automáticamente al publicar un release en GitHub.

**Trigger**: Cuando se publica un release (no drafts)

**Funciones**:
1. Extrae la versión del tag del release
2. Crea un paquete ZIP del plugin excluyendo archivos de desarrollo
3. Sube el ZIP como asset del release

**Uso**:
```bash
# Crear release desde CLI
gh release create v2.3.9 --title "Version 2.3.9" --notes "Release notes"

# O desde la interfaz web de GitHub
# https://github.com/hectorcanaimero/pidelo/releases/new
```

**Output**:
- `myd-delivery-pro-{version}.zip` - Paquete instalable del plugin

**Archivos Incluidos**:
- Código del plugin (`includes/`, `templates/`, etc.)
- Assets (CSS, JS, imágenes)
- Traducciones (`languages/`)
- Archivos necesarios (`myd-delivery-pro.php`, `LICENSE`, `README.txt`, `CHANGELOG.md`)

**Archivos Excluidos**:
- Control de versiones (`.git/`, `.github/`, `.gitignore`)
- Tests (`tests/`, `phpunit.xml.dist`)
- Documentación de desarrollo (`docs/`, `CLAUDE.md`, etc.)
- Archivos de configuración (`composer.json`, `api.http`, etc.)

**Logs**:
Ver ejecuciones en: https://github.com/hectorcanaimero/pidelo/actions

## Permisos Requeridos

Los workflows necesitan permisos para:
- `contents: read` - Leer código del repositorio
- `contents: write` - Subir assets a releases

Estos permisos están configurados automáticamente mediante `GITHUB_TOKEN`.

## Debugging

Si un workflow falla:

1. Ve a https://github.com/hectorcanaimero/pidelo/actions
2. Click en el workflow fallido
3. Revisa los logs de cada step
4. Re-ejecuta el workflow si es necesario (botón "Re-run jobs")

## Próximos Workflows

Workflows planificados para el futuro:

- **`update-info.yml`** - Actualizar archivo JSON de updates automáticamente
- **`tests.yml`** - Ejecutar tests en PRs y commits
- **`deploy.yml`** - Deploy a servidor de staging/producción

## Referencias

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Workflow Syntax](https://docs.github.com/en/actions/reference/workflow-syntax-for-github-actions)
- [GitHub Releases](https://docs.github.com/en/repositories/releasing-projects-on-github)

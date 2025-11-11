# Automation Workflow - Documentación

Esta documentación explica cómo funciona el workflow de GitHub Actions que automatiza la actualización de `update-info.json` cuando se publica un release.

## 🎯 Objetivo

Actualizar automáticamente el archivo `update-info.json` en GitHub Pages cada vez que se publica un nuevo release, para que WordPress pueda detectar actualizaciones disponibles.

## 🔄 Flujo del Workflow

### Triggers

El workflow se ejecuta en dos situaciones:

1. **Automático:** Cuando se publica un release (`release.published`)
2. **Manual:** Via `workflow_dispatch` con input de versión

### Pasos del Workflow

```
1. Checkout main branch
   ↓
2. Extract version from release tag (v2.4.0 → 2.4.0)
   ↓
3. Extract plugin info (requires, tested, requires_php)
   ↓
4. Extract changelog from release notes
   ↓
5. Validate ZIP exists (for releases)
   ↓
6. Generate update-info.json
   ↓
7. Validate JSON structure and required fields
   ↓
8. Checkout gh-pages branch
   ↓
9. Copy update-info.json to gh-pages
   ↓
10. Commit and push to gh-pages
    ↓
11. Update workflow summary
    ↓
12. Notify on failure (if any step fails)
```

## 📝 Detalles de Implementación

### 1. Extract Version

```yaml
- name: Get version from release or input
  run: |
    if [ "${{ github.event_name }}" == "release" ]; then
      VERSION="${{ github.event.release.tag_name }}"
      VERSION="${VERSION#v}"  # Remove 'v' prefix
    else
      VERSION="${{ github.event.inputs.version }}"
    fi
```

**Input:** `v2.4.0` (tag)
**Output:** `2.4.0` (cleaned version)

### 2. Extract Plugin Info

Lee el archivo `myd-delivery-pro.php` para extraer:

```yaml
REQUIRES=$(grep "Requires at least:" myd-delivery-pro.php | sed 's/.*: //' | tr -d ' \r\n')
TESTED=$(grep "Tested up to:" myd-delivery-pro.php | sed 's/.*: //' | tr -d ' \r\n')
REQUIRES_PHP=$(grep "Requires PHP:" myd-delivery-pro.php | sed 's/.*: //' | tr -d ' \r\n')
```

**Ejemplo de output:**
```
requires=5.5
tested=6.4
requires_php=7.4
```

### 3. Extract Changelog

Extrae el changelog con 3 opciones:

#### Opción A: Desde Release Notes (Recomendado)

Si es un release event, usa `github.event.release.body`:

```markdown
### Features
- Add new API endpoint
- Improve performance

### Bug Fixes
- Fix login issue
- Resolve cache problem
```

Se convierte a HTML:

```html
<h4>2.4.0</h4>
<h4>Features</h4>
<ul>
  <li>Add new API endpoint</li>
  <li>Improve performance</li>
</ul>
<h4>Bug Fixes</h4>
<ul>
  <li>Fix login issue</li>
  <li>Resolve cache problem</li>
</ul>
```

#### Opción B: Desde CHANGELOG.md

Si existe archivo `CHANGELOG.md`:

```markdown
## 2.4.0
- Feature X
- Bug fix Y
```

Se parsea y convierte a HTML.

#### Opción C: Desde Git Log (Fallback)

Si no hay release notes ni CHANGELOG.md:

```bash
git log --pretty=format:"<li>%s</li>" -10 | head -5
```

### 4. Validate ZIP Exists

Solo para release events, verifica que el ZIP esté disponible:

```bash
HTTP_CODE=$(curl -o /dev/null -s -w "%{http_code}" -L "$DOWNLOAD_URL")

if [ "$HTTP_CODE" != "200" ]; then
  echo "⚠️ Warning: ZIP file not found"
fi
```

**No falla el workflow**, solo advierte en logs.

### 5. Generate JSON

Crea `update-info.json` con datos recopilados:

```json
{
  "version": "2.4.0",
  "download_url": "https://github.com/.../releases/download/v2.4.0/myd-delivery-pro.zip",
  "requires": "5.5",
  "tested": "6.4",
  "requires_php": "7.4",
  "last_updated": "2025-11-10",
  "sections": {
    "changelog": "<h4>2.4.0</h4><ul>..."
  }
}
```

### 6. Validate JSON

Valida estructura y campos requeridos:

```bash
# Validar sintaxis
jq empty update-info.json

# Validar campos requeridos
REQUIRED_FIELDS=("version" "download_url" "requires" "tested" "requires_php")
for field in "${REQUIRED_FIELDS[@]}"; do
  jq -e ".$field" update-info.json
done
```

Si falla, el workflow se detiene con exit code 1.

### 7. Publish to gh-pages

```bash
git checkout gh-pages
cp update-info.json gh-pages/
git add update-info.json
git commit -m "Update plugin info to version 2.4.0"
git push origin gh-pages
```

GitHub Pages actualiza automáticamente en 1-2 minutos.

### 8. Workflow Summary

Genera resumen visual en GitHub Actions UI:

```markdown
## Plugin Update Info Generated ✅

**Version:** 2.4.0
**Update URL:** https://hectorcanaimero.github.io/pidelo/update-info.json
**Download URL:** https://github.com/.../releases/.../myd-delivery-pro.zip

### Testing:
curl https://hectorcanaimero.github.io/pidelo/update-info.json | jq .

### Content:
{json content here}
```

### 9. Failure Notification

Si el workflow falla:

1. **Crea issue automático** (solo para release events):
```markdown
## ❌ Update Info Workflow Failed

Version: 2.4.0
Workflow Run: {link}

### Possible Causes:
- Invalid JSON structure
- Missing required fields
- ZIP file not uploaded
- gh-pages branch doesn't exist
```

2. **Agrega comentario en release** con link a logs

## 🧪 Testing

### Test Manual del Workflow

```bash
# 1. Trigger workflow manualmente
gh workflow run update-info.yml -f version=2.4.0-test

# 2. Ver status
gh run list --workflow=update-info.yml

# 3. Ver logs
gh run view {run-id} --log
```

### Test con Release

```bash
# 1. Crear tag
git tag v2.4.0-test
git push origin v2.4.0-test

# 2. Crear release
gh release create v2.4.0-test \
  --title "Version 2.4.0 Test" \
  --notes "### Features
- Test feature 1
- Test feature 2" \
  myd-delivery-pro.zip

# 3. Workflow se ejecuta automáticamente

# 4. Verificar resultado
curl https://hectorcanaimero.github.io/pidelo/update-info.json | jq .version
```

### Validar JSON Local

```bash
# Simular generación local
VERSION="2.4.0"
REQUIRES="5.5"
TESTED="6.4"
REQUIRES_PHP="7.4"
CHANGELOG="<h4>2.4.0</h4><ul><li>Test</li></ul>"

cat > update-info.json << EOF
{
  "version": "$VERSION",
  "download_url": "https://github.com/user/repo/releases/download/v$VERSION/plugin.zip",
  "requires": "$REQUIRES",
  "tested": "$TESTED",
  "requires_php": "$REQUIRES_PHP",
  "sections": {
    "changelog": "$CHANGELOG"
  }
}
EOF

# Validar
jq . update-info.json

# Validar campos requeridos
jq -e '.version, .download_url, .requires' update-info.json
```

## 🐛 Debugging

### Ver Logs de Workflow

```bash
# Listar runs
gh run list --workflow=update-info.yml

# Ver logs de un run específico
gh run view {run-id} --log

# Ver solo errores
gh run view {run-id} --log | grep -i error
```

### Errores Comunes

#### 1. "Invalid JSON structure"

**Causa:** Error en generación de JSON (comillas, comas, etc.)

**Solución:**
```bash
# Ver el JSON generado en logs
# Copiar a archivo local
# Validar con jq
jq . update-info.json
```

#### 2. "ZIP file not found"

**Causa:** Release no tiene ZIP adjunto

**Solución:**
```bash
# Verificar assets del release
gh release view v2.4.0 --json assets

# Subir ZIP manualmente
gh release upload v2.4.0 myd-delivery-pro.zip
```

#### 3. "gh-pages branch doesn't exist"

**Causa:** Branch gh-pages no está creado

**Solución:**
```bash
# Crear branch gh-pages
./scripts/setup-gh-pages.sh

# O manualmente:
git checkout --orphan gh-pages
git rm -rf .
echo '{"version": "1.0.0"}' > update-info.json
git add update-info.json
git commit -m "Initial gh-pages"
git push origin gh-pages
```

#### 4. "Permission denied"

**Causa:** Workflow no tiene permisos de escritura

**Solución:**
```yaml
# Verificar permisos en workflow
permissions:
  contents: write  # Necesario para push a gh-pages
```

#### 5. "Failed to push"

**Causa:** Alguien más actualizó gh-pages simultáneamente

**Solución:**
```bash
# Re-ejecutar workflow manualmente
gh workflow run update-info.yml -f version=2.4.0
```

## 🔧 Configuración

### Variables de Entorno

No se requieren secrets, pero se pueden configurar:

```yaml
# .github/workflows/update-info.yml
env:
  AUTHOR: "PideAI"
  HOMEPAGE: "https://pideai.com"
```

### Customización

#### Cambiar formato de changelog

Editar step "Extract changelog":

```yaml
# Para incluir más commits
git log --pretty=format:"<li>%s</li>" -20 | head -10

# Para formato diferente
git log --pretty=format:"<li><strong>%s</strong> (%an)</li>"
```

#### Agregar campos adicionales

En step "Generate update-info.json":

```json
{
  "custom_field": "value",
  "rating": 5,
  "active_installs": 1000
}
```

#### Notificaciones a Slack/Discord

Agregar step al final:

```yaml
- name: Notify Slack
  uses: slackapi/slack-github-action@v1
  with:
    payload: |
      {
        "text": "New version ${{ steps.version.outputs.version }} published!"
      }
  env:
    SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
```

## 📊 Monitoreo

### Verificar Última Ejecución

```bash
# Via GitHub CLI
gh run list --workflow=update-info.yml --limit 1

# Via API
curl https://api.github.com/repos/user/repo/actions/workflows/update-info.yml/runs

# Via Web
https://github.com/user/repo/actions/workflows/update-info.yml
```

### Métricas

**Success Rate:**
```bash
gh run list --workflow=update-info.yml --status=success --limit 100 | wc -l
```

**Average Duration:**
```bash
gh run list --workflow=update-info.yml --json durationMs --jq 'map(.durationMs) | add / length'
```

## 🚀 Mejoras Futuras

### 1. Validación de Versión Semántica

```yaml
- name: Validate version format
  run: |
    if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
      echo "Invalid version format: $VERSION"
      exit 1
    fi
```

### 2. Changelog desde Commits

```yaml
- name: Generate changelog from commits
  run: |
    git log --oneline v2.3.8..v2.4.0 | \
      sed 's/^[a-f0-9]* /<li>/; s/$/<\/li>/' | \
      sed '1s/^/<ul>\n/; $s/$/<\/ul>/'
```

### 3. Rollback Automático

```yaml
- name: Rollback on failure
  if: failure()
  run: |
    git checkout gh-pages
    git revert HEAD
    git push origin gh-pages
```

### 4. Multi-Environment

```yaml
# Diferentes URLs para staging/production
- name: Set environment
  run: |
    if [[ "$VERSION" == *"-beta"* ]]; then
      ENV="staging"
      URL="https://staging.pideai.com/updates"
    else
      ENV="production"
      URL="https://pideai.com/updates"
    fi
```

## 📚 Referencias

- [GitHub Actions Docs](https://docs.github.com/en/actions)
- [GitHub Script Action](https://github.com/actions/github-script)
- [Workflow Syntax](https://docs.github.com/en/actions/reference/workflow-syntax-for-github-actions)
- [GitHub Pages](https://docs.github.com/en/pages)

---

**Última actualización:** 2025-11-10
**Versión:** 1.0.0

#!/bin/bash

# Script para configurar GitHub Pages para el servidor de actualizaciones
# Uso: ./scripts/setup-gh-pages.sh

set -e

echo "🚀 Configurando GitHub Pages para Update Server..."
echo ""

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar que estamos en la raíz del plugin
if [ ! -f "myd-delivery-pro.php" ]; then
    echo "❌ Error: Este script debe ejecutarse desde la raíz del plugin"
    exit 1
fi

# Obtener información del plugin
VERSION=$(grep "Version:" myd-delivery-pro.php | sed 's/.*Version: //' | tr -d ' \r\n')
REQUIRES=$(grep "Requires at least:" myd-delivery-pro.php | sed 's/.*: //' | tr -d ' \r\n')
REQUIRES_PHP=$(grep "Requires PHP:" myd-delivery-pro.php | sed 's/.*: //' | tr -d ' \r\n')
TESTED=$(grep "Tested up to:" myd-delivery-pro.php | sed 's/.*: //' | tr -d ' \r\n' || echo "6.4")

echo -e "${BLUE}Información del plugin:${NC}"
echo "  Version: $VERSION"
echo "  Requires: $REQUIRES"
echo "  Tested: $TESTED"
echo "  Requires PHP: $REQUIRES_PHP"
echo ""

# Guardar el branch actual
CURRENT_BRANCH=$(git branch --show-current)
echo -e "${BLUE}Branch actual:${NC} $CURRENT_BRANCH"
echo ""

# Verificar si gh-pages ya existe
if git show-ref --verify --quiet refs/heads/gh-pages; then
    echo -e "${YELLOW}⚠️  El branch gh-pages ya existe${NC}"
    read -p "¿Deseas recrearlo? (esto eliminará el branch existente) [y/N]: " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Eliminando branch gh-pages..."
        git branch -D gh-pages 2>/dev/null || true
        git push origin --delete gh-pages 2>/dev/null || true
    else
        echo "Actualizando branch gh-pages existente..."
        git checkout gh-pages

        # Crear update-info.json actualizado
        cat > update-info.json << EOF
{
  "name": "MyD Delivery Pro",
  "slug": "myd-delivery-pro",
  "version": "$VERSION",
  "download_url": "https://github.com/hectorcanaimero/pidelo/releases/download/v$VERSION/myd-delivery-pro.zip",
  "requires": "$REQUIRES",
  "tested": "$TESTED",
  "requires_php": "$REQUIRES_PHP",
  "last_updated": "$(date +%Y-%m-%d)",
  "author": "PideAI",
  "author_profile": "https://pideai.com",
  "homepage": "https://pideai.com",
  "banners": {
    "low": "",
    "high": ""
  },
  "sections": {
    "description": "Sistema completo de gestión de delivery con productos, órdenes, clientes, integración con WhatsApp y procesamiento de pagos.",
    "installation": "<h4>Instalación</h4><ol><li>Sube el plugin a la carpeta /wp-content/plugins/</li><li>Activa el plugin desde el menú 'Plugins' en WordPress</li><li>Configura las opciones desde MyD Delivery Pro</li></ol>",
    "changelog": "<h4>$VERSION</h4><ul><li>See GitHub releases for changelog</li></ul>"
  },
  "icons": {
    "1x": "",
    "2x": ""
  }
}
EOF

        git add update-info.json
        git commit -m "Update to version $VERSION" || echo "No changes to commit"
        git push origin gh-pages

        git checkout $CURRENT_BRANCH

        echo ""
        echo -e "${GREEN}✅ Branch gh-pages actualizado${NC}"
        exit 0
    fi
fi

echo -e "${BLUE}Creando branch gh-pages...${NC}"

# Crear branch orphan (sin historial)
git checkout --orphan gh-pages

# Limpiar todos los archivos
git rm -rf . 2>/dev/null || true
rm -rf .* 2>/dev/null || true

# Crear update-info.json
echo -e "${BLUE}Generando update-info.json...${NC}"
cat > update-info.json << EOF
{
  "name": "MyD Delivery Pro",
  "slug": "myd-delivery-pro",
  "version": "$VERSION",
  "download_url": "https://github.com/hectorcanaimero/pidelo/releases/download/v$VERSION/myd-delivery-pro.zip",
  "requires": "$REQUIRES",
  "tested": "$TESTED",
  "requires_php": "$REQUIRES_PHP",
  "last_updated": "$(date +%Y-%m-%d)",
  "author": "PideAI",
  "author_profile": "https://pideai.com",
  "homepage": "https://pideai.com",
  "banners": {
    "low": "",
    "high": ""
  },
  "sections": {
    "description": "Sistema completo de gestión de delivery con productos, órdenes, clientes, integración con WhatsApp y procesamiento de pagos.",
    "installation": "<h4>Instalación</h4><ol><li>Sube el plugin a la carpeta /wp-content/plugins/</li><li>Activa el plugin desde el menú 'Plugins' en WordPress</li><li>Configura las opciones desde MyD Delivery Pro</li></ol>",
    "changelog": "<h4>$VERSION</h4><ul><li>Initial version on update server</li></ul>"
  },
  "icons": {
    "1x": "",
    "2x": ""
  }
}
EOF

# Validar JSON
if command -v jq &> /dev/null; then
    echo -e "${BLUE}Validando JSON...${NC}"
    cat update-info.json | jq . > /tmp/valid.json
    mv /tmp/valid.json update-info.json
    echo -e "${GREEN}✅ JSON válido${NC}"
else
    echo -e "${YELLOW}⚠️  jq no está instalado, saltando validación JSON${NC}"
fi

# Crear README.md
cat > README.md << 'EOF'
# MyD Delivery Pro - Update Server

Este branch contiene el archivo `update-info.json` que WordPress consulta para verificar actualizaciones.

## Endpoint

```
https://hectorcanaimero.github.io/pidelo/update-info.json
```

## Actualización Automática

Este archivo se actualiza automáticamente cuando se publica un nuevo release mediante GitHub Actions.

## Actualización Manual

Si necesitas actualizar manualmente:

```bash
git checkout gh-pages
# Editar update-info.json
git add update-info.json
git commit -m "Update to version X.X.X"
git push origin gh-pages
```
EOF

# Commit
echo -e "${BLUE}Commiteando archivos...${NC}"
git add update-info.json README.md
git commit -m "Initial update server setup - version $VERSION"

# Push
echo -e "${BLUE}Publicando en GitHub...${NC}"
git push origin gh-pages

# Volver al branch original
echo -e "${BLUE}Volviendo a $CURRENT_BRANCH...${NC}"
git checkout $CURRENT_BRANCH

echo ""
echo -e "${GREEN}✅ Setup completado!${NC}"
echo ""
echo -e "${BLUE}Próximos pasos:${NC}"
echo ""
echo "1. Habilitar GitHub Pages:"
echo "   - Ve a: https://github.com/hectorcanaimero/pidelo/settings/pages"
echo "   - Source: Deploy from a branch"
echo "   - Branch: gh-pages / (root)"
echo "   - Click Save"
echo ""
echo "2. Espera 1-2 minutos y verifica:"
echo "   curl https://hectorcanaimero.github.io/pidelo/update-info.json"
echo ""
echo "3. El endpoint estará disponible en:"
echo -e "   ${GREEN}https://hectorcanaimero.github.io/pidelo/update-info.json${NC}"
echo ""
echo -e "${BLUE}Documentación completa en:${NC} docs/UPDATE-SERVER.md"
echo ""

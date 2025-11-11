# MyD Delivery Pro - Documentación de API REST

## 🎯 Acceso Rápido

### 📱 Swagger UI (Recomendado)

Una vez que actives el plugin, tendrás acceso al Swagger UI integrado:

**URL del Admin**:
```
https://tu-dominio.com/wp-admin/admin.php?page=myd-api-docs
```

**Busca en el menú lateral de WordPress**:
- Ve al menú de WordPress (lado izquierdo)
- Busca "**API Docs**" (con icono de libro)
- Haz clic para ver toda la documentación interactiva

### 🌐 Endpoints de la API

**Base URL**: `https://tu-dominio.com/wp-json/myd-delivery/v1`

**Especificación OpenAPI**:
```
https://tu-dominio.com/wp-json/myd-delivery/v1/swagger.json
```

**YAML directo**:
```
https://tu-dominio.com/wp-content/plugins/myd-delivery-pro/docs/api/openapi.yaml
```

---

## 📚 Documentación Disponible

### 1. OpenAPI Specification (`openapi.yaml`)
- Especificación completa en formato OpenAPI 3.0
- 50+ endpoints documentados
- Schemas de validación
- Ejemplos de request/response
- Compatible con Swagger, Postman, Insomnia

### 2. Guía de Integración (`API-INTEGRATION-GUIDE.md`)
- Tutorial paso a paso
- Ejemplos de código en JavaScript
- Flujos completos de trabajo
- Manejo de errores
- Mejores prácticas
- FAQ

### 3. Plan de Desarrollo (`GITHUB-ISSUES-PLAN.md`)
- Roadmap de desarrollo
- Issues priorizados
- Asignaciones de tareas
- 3 sprints planificados

### 4. Resumen del Proyecto (`PROJECT-SUMMARY.md`)
- Resumen ejecutivo
- Estadísticas del proyecto
- Próximos pasos
- Equipo requerido

---

## 🚀 Inicio Rápido

### Paso 1: Autenticación

```bash
curl -X POST https://tu-dominio.com/wp-json/myd-delivery/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "tu_password"
  }'
```

**Respuesta**:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "expires_in": 86400
}
```

### Paso 2: Usar el Token

```bash
curl https://tu-dominio.com/wp-json/myd-delivery/v1/products \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

---

## 📋 APIs Disponibles

### Autenticación
- `POST /auth/login` - Iniciar sesión
- `POST /auth/refresh` - Renovar token
- `GET /auth/validate` - Validar token
- `GET /auth/me` - Usuario actual

### Productos
- `GET /products` - Listar productos
- `POST /products` - Crear producto
- `GET /products/{id}` - Obtener producto
- `PUT /products/{id}` - Actualizar producto
- `DELETE /products/{id}` - Eliminar producto

### Categorías
- `GET /categories` - Listar categorías
- `POST /categories` - Crear categoría
- `PUT /categories/{id}` - Actualizar categoría
- `DELETE /categories/{id}` - Eliminar categoría
- `PUT /categories/reorder` - Reordenar

### Órdenes
- `GET /orders` - Listar órdenes
- `POST /orders` - Crear orden
- `GET /orders/{id}` - Obtener orden
- `PUT /orders/{id}` - Actualizar orden
- `DELETE /orders/{id}` - Eliminar orden

### Carrito
- `GET /cart` - Obtener carrito
- `POST /cart` - Actualizar carrito
- `POST /cart/items` - Agregar item
- `PUT /cart/items/{id}` - Actualizar item
- `DELETE /cart/items/{id}` - Remover item
- `POST /cart/calculate` - Calcular totales

### Clientes
- `GET /customers` - Listar clientes
- `GET /customers/{phone}` - Obtener cliente
- `GET /customers/{phone}/orders` - Órdenes del cliente
- `GET /customers/{phone}/addresses` - Direcciones

### Cupones
- `GET /coupons` - Listar cupones
- `POST /coupons` - Crear cupón
- `GET /coupons/validate/{code}` - Validar cupón
- `PUT /coupons/{id}` - Actualizar
- `DELETE /coupons/{id}` - Eliminar

### Reportes
- `GET /reports/sales` - Reporte de ventas
- `GET /reports/products` - Reporte de productos
- `GET /reports/customers` - Reporte de clientes
- `GET /reports/overview` - Resumen general

### Configuración
- `GET /settings` - Obtener configuración
- `PUT /settings` - Actualizar configuración
- `GET /settings/{key}` - Configuración específica

### Media
- `POST /media/upload` - Subir imagen (base64)

### WhatsApp
- `POST /whatsapp/send-order` - Enviar orden

---

## 🛠️ Herramientas para Testing

### Postman
1. Importa `openapi.yaml` en Postman
2. Configura el ambiente con tu URL base
3. Prueba los endpoints

### Swagger Editor Online
1. Ve a https://editor.swagger.io/
2. Importa `openapi.yaml`
3. Prueba directamente desde el navegador

### cURL
```bash
# Ver todas las rutas disponibles
curl https://tu-dominio.com/wp-json/myd-delivery/v1/
```

---

## 🔒 Seguridad

- ✅ Autenticación JWT
- ✅ Tokens con expiración (24h)
- ✅ Permisos por capacidad de WordPress
- ✅ Validación de datos
- ✅ Sanitización de inputs
- ⚠️ Requiere HTTPS en producción
- ⚠️ Implementar rate limiting

---

## 📞 Soporte

**Repositorio**: https://github.com/hectorcanaimero/pidelo

**Issues Abiertos**:
- #21 - Integrar nuevas APIs
- #22 - Testing Categories
- #23 - Testing Cart
- #24 - Testing Auth
- #25 - Rate limiting
- #26 - Mejorar docs

**Email**: support@pideai.com

---

## 📝 Changelog

### v2.4.0 (En desarrollo)
- ✅ Nueva API de Categories
- ✅ Nueva API de Cart
- ✅ Nueva API de Authentication (JWT)
- ✅ Swagger UI integrado
- ✅ Documentación OpenAPI completa

### v2.3.8 (Actual)
- API de Products
- API de Orders
- API de Customers
- API de Coupons
- API de Reports
- API de Settings
- API de Media

---

**© 2025 PideAI - MyD Delivery Pro**

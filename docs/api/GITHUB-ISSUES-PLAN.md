# GitHub Issues Plan - MyD Delivery Pro API

Este documento contiene todos los issues que deben crearse en GitHub para el desarrollo de la API REST completa.

## Issues a Crear

### 1. [API] Integrar nuevas APIs al plugin principal
**Labels**: `enhancement`, `api`, `priority:high`
**Milestone**: v2.4.0

**Descripción**:
Integrar las nuevas clases API (Categories, Cart, Auth) al sistema de carga del plugin principal.

**Tareas**:
- [ ] Agregar require de `class-categories-api.php` en `class-plugin.php`
- [ ] Agregar require de `class-cart-api.php` en `class-plugin.php`
- [ ] Agregar require de `class-auth-api.php` en `class-plugin.php`
- [ ] Verificar que todos los endpoints se registren correctamente
- [ ] Probar cada endpoint manualmente

**Archivos afectados**:
- `includes/class-plugin.php`
- `includes/api/categories/class-categories-api.php`
- `includes/api/cart/class-cart-api.php`
- `includes/api/auth/class-auth-api.php`

---

### 2. [API] Testing de endpoints de Categories
**Labels**: `testing`, `api`
**Milestone**: v2.4.0

**Descripción**:
Crear tests automatizados para los endpoints de Categories API.

**Tareas**:
- [ ] Test GET /categories - listar categorías
- [ ] Test POST /categories - crear categoría
- [ ] Test PUT /categories/{id} - actualizar categoría
- [ ] Test DELETE /categories/{id} - eliminar categoría
- [ ] Test PUT /categories/reorder - reordenar categorías
- [ ] Verificar permisos de admin
- [ ] Verificar validaciones de datos

**Archivos afectados**:
- `tests/api/test-categories-api.php` (nuevo)

---

### 3. [API] Testing de endpoints de Cart
**Labels**: `testing`, `api`
**Milestone**: v2.4.0

**Descripción**:
Crear tests automatizados para los endpoints de Cart API.

**Tareas**:
- [ ] Test GET /cart - obtener carrito
- [ ] Test POST /cart - actualizar carrito
- [ ] Test DELETE /cart - limpiar carrito
- [ ] Test POST /cart/items - agregar item
- [ ] Test PUT /cart/items/{id} - actualizar item
- [ ] Test DELETE /cart/items/{id} - remover item
- [ ] Test POST /cart/calculate - calcular totales
- [ ] Verificar cálculo de descuentos con cupones
- [ ] Verificar persistencia del carrito en sesión

**Archivos afectados**:
- `tests/api/test-cart-api.php` (nuevo)

---

### 4. [API] Testing de endpoints de Authentication
**Labels**: `testing`, `api`, `security`
**Milestone**: v2.4.0

**Descripción**:
Crear tests automatizados para los endpoints de Authentication API.

**Tareas**:
- [ ] Test POST /auth/login - login exitoso
- [ ] Test POST /auth/login - credenciales inválidas
- [ ] Test POST /auth/refresh - renovar token
- [ ] Test GET /auth/validate - validar token válido
- [ ] Test GET /auth/validate - validar token expirado
- [ ] Test GET /auth/me - obtener usuario actual
- [ ] Verificar firma JWT
- [ ] Verificar expiración de tokens
- [ ] Probar con diferentes roles de usuario

**Archivos afectados**:
- `tests/api/test-auth-api.php` (nuevo)

---

### 5. [API] Mejorar sistema de sesiones para Cart API
**Labels**: `enhancement`, `api`
**Milestone**: v2.4.0

**Descripción**:
El Cart API actualmente usa transients con IP del cliente. Mejorar a un sistema más robusto.

**Tareas**:
- [ ] Investigar alternativas: cookies, JWT payload, WordPress sessions
- [ ] Implementar sistema de sesiones mejorado
- [ ] Agregar limpieza automática de carritos abandonados
- [ ] Documentar el sistema elegido
- [ ] Actualizar tests

**Consideraciones**:
- Para apps móviles, considerar usar session ID en header custom
- Para web, usar cookies seguras (httpOnly, SameSite)
- Agregar expiración automática (ej: 24 horas)

---

### 6. [API] Implementar rate limiting
**Labels**: `enhancement`, `api`, `security`
**Milestone**: v2.4.1

**Descripción**:
Agregar rate limiting a la API para prevenir abuso.

**Tareas**:
- [ ] Implementar sistema de rate limiting (100 req/min por usuario)
- [ ] Usar transients de WordPress para almacenar contadores
- [ ] Agregar headers de rate limit (X-RateLimit-*)
- [ ] Retornar 429 Too Many Requests cuando se excede
- [ ] Agregar whitelist para IPs confiables
- [ ] Documentar en la guía de integración

**Archivos afectados**:
- `includes/api/class-rate-limiter.php` (nuevo)
- Todos los archivos API (agregar middleware)

---

### 7. [API] Agregar webhook para eventos de órdenes
**Labels**: `feature`, `api`
**Milestone**: v2.5.0

**Descripción**:
Implementar sistema de webhooks para notificar cambios de estado en órdenes.

**Tareas**:
- [ ] Crear endpoint para registrar webhooks
- [ ] Implementar disparador de webhooks en eventos de órdenes
- [ ] Agregar retry logic para webhooks fallidos
- [ ] Agregar firma HMAC para seguridad
- [ ] Documentar en la guía de integración
- [ ] Crear UI en admin para configurar webhooks

**Eventos soportados**:
- `order.created`
- `order.updated`
- `order.status_changed`
- `order.payment_completed`

---

### 8. [API] Mejorar documentación OpenAPI
**Labels**: `documentation`, `api`
**Milestone**: v2.4.0

**Descripción**:
Agregar más detalles a la especificación OpenAPI.

**Tareas**:
- [ ] Agregar ejemplos de request/response para todos los endpoints
- [ ] Agregar descripciones detalladas de parámetros
- [ ] Documentar códigos de error específicos
- [ ] Agregar schemas de validación completos
- [ ] Incluir ejemplos de errores comunes
- [ ] Generar documentación HTML con Swagger UI

**Archivos afectados**:
- `docs/api/openapi.yaml`

---

### 9. [API] Implementar versionado de API
**Labels**: `enhancement`, `api`
**Milestone**: v2.5.0

**Descripción**:
Preparar la API para soportar múltiples versiones simultáneas.

**Tareas**:
- [ ] Refactorizar estructura para soportar v1, v2, etc.
- [ ] Crear sistema de routing por versión
- [ ] Implementar deprecation warnings
- [ ] Documentar política de versionado
- [ ] Mantener v1 como actual
- [ ] Preparar migración a v2 para futuras mejoras

---

### 10. [API] Crear SDK de JavaScript para developers
**Labels**: `feature`, `developer-experience`
**Milestone**: v2.5.0

**Descripción**:
Crear una librería JavaScript/TypeScript para facilitar integración.

**Tareas**:
- [ ] Crear package npm `@pideai/myd-delivery-sdk`
- [ ] Implementar cliente API con TypeScript
- [ ] Agregar manejo automático de autenticación
- [ ] Agregar retry logic y error handling
- [ ] Incluir tipos TypeScript completos
- [ ] Publicar en npm
- [ ] Documentar uso en la guía de integración

**Funcionalidades**:
```typescript
import MyDDelivery from '@pideai/myd-delivery-sdk';

const client = new MyDDelivery({
  baseUrl: 'https://mi-tienda.com',
  username: 'admin',
  password: 'password'
});

// Auto-maneja login y tokens
const products = await client.products.list();
const order = await client.orders.create({ ... });
```

---

### 11. [API] Optimización de queries de reportes
**Labels**: `performance`, `api`
**Milestone**: v2.4.1

**Descripción**:
Los endpoints de reportes pueden ser lentos con muchas órdenes. Optimizar queries.

**Tareas**:
- [ ] Agregar índices a tablas relevantes
- [ ] Implementar caché de reportes (transients)
- [ ] Optimizar queries SQL complejos
- [ ] Agregar paginación a reportes grandes
- [ ] Medir performance antes/después
- [ ] Documentar mejoras

**Archivos afectados**:
- `includes/api/reports/class-reports-api.php`

---

### 12. [API] Agregar soporte para filtros avanzados en Products
**Labels**: `enhancement`, `api`
**Milestone**: v2.4.1

**Descripción**:
Agregar más opciones de filtrado y ordenamiento para productos.

**Tareas**:
- [ ] Filtro por rango de precios (min_price, max_price)
- [ ] Filtro por múltiples categorías
- [ ] Ordenar por precio, popularidad, fecha
- [ ] Búsqueda por tags/keywords
- [ ] Agregar faceted search
- [ ] Actualizar documentación OpenAPI

**Archivos afectados**:
- `includes/api/products/class-products-api.php`

---

### 13. [Mobile App] Crear app móvil de administración (React Native)
**Labels**: `mobile`, `feature`
**Milestone**: v3.0.0

**Descripción**:
Crear aplicación móvil para iOS y Android que use la API REST.

**Tareas**:
- [ ] Configurar proyecto React Native
- [ ] Implementar autenticación JWT
- [ ] Pantalla de dashboard con métricas
- [ ] Gestión de órdenes (listar, ver, actualizar estado)
- [ ] Notificaciones push para nuevas órdenes
- [ ] Gestión de productos y categorías
- [ ] Ver reportes y estadísticas
- [ ] Configuración de la tienda
- [ ] Publicar en App Store y Google Play

**Stack tecnológico**:
- React Native
- React Navigation
- Redux Toolkit
- React Query
- Push Notifications (Firebase)

---

### 14. [Testing] Configurar CI/CD para tests automatizados
**Labels**: `testing`, `devops`
**Milestone**: v2.4.0

**Descripción**:
Configurar GitHub Actions para correr tests automáticamente.

**Tareas**:
- [ ] Crear workflow de GitHub Actions
- [ ] Configurar WordPress test environment
- [ ] Correr PHPUnit tests en cada PR
- [ ] Agregar code coverage reports
- [ ] Configurar badges de estado en README
- [ ] Agregar pre-commit hooks

**Archivos afectados**:
- `.github/workflows/tests.yml` (nuevo)
- `composer.json`
- `phpunit.xml`

---

### 15. [Documentation] Crear video tutorials de integración
**Labels**: `documentation`, `developer-experience`
**Milestone**: v2.4.1

**Descripción**:
Crear videos tutoriales mostrando cómo integrar la API.

**Tareas**:
- [ ] Video: Autenticación y primeros pasos
- [ ] Video: Crear orden desde app móvil
- [ ] Video: Gestión de carrito
- [ ] Video: Subir imágenes de productos
- [ ] Video: Usar reportes y analytics
- [ ] Publicar en YouTube
- [ ] Agregar links en documentación

---

## Priorización

### Sprint 1 (v2.4.0) - Esencial
1. #1 - Integrar nuevas APIs
2. #2 - Testing Categories
3. #3 - Testing Cart
4. #4 - Testing Auth
5. #14 - CI/CD

### Sprint 2 (v2.4.1) - Mejoras
6. #5 - Mejorar sesiones Cart
7. #6 - Rate limiting
8. #11 - Optimizar reportes
9. #12 - Filtros avanzados Products

### Sprint 3 (v2.5.0) - Avanzado
10. #7 - Webhooks
11. #9 - Versionado API
12. #10 - SDK JavaScript

### Futuro (v3.0.0)
13. #13 - App móvil
14. #15 - Video tutorials

---

## Asignación de Issues

- **Backend Developer**: Issues #1-7, #11-12
- **QA/Testing**: Issues #2-4, #14
- **Frontend Developer**: Issues #10, #13
- **DevOps**: Issues #6, #14
- **Technical Writer**: Issues #8, #15

---

**Generado automáticamente por Claude Code**
**Fecha**: 2025-01-09

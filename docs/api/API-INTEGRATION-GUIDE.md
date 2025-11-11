# MyD Delivery Pro - Guía de Integración de API

Esta guía proporciona información completa para integrar la API REST de MyD Delivery Pro en tu aplicación móvil o sistema externo.

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Autenticación](#autenticación)
3. [Rate Limiting](#rate-limiting)
4. [Endpoints Disponibles](#endpoints-disponibles)
5. [Ejemplos de Integración](#ejemplos-de-integración)
6. [Manejo de Errores](#manejo-de-errores)
7. [Mejores Prácticas](#mejores-prácticas)
8. [FAQ](#faq)

## Introducción

La API de MyD Delivery Pro es una API RESTful que permite:

- Gestionar productos y categorías
- Procesar órdenes de delivery
- Administrar clientes y sus direcciones
- Aplicar cupones de descuento
- Generar reportes y estadísticas
- Enviar notificaciones por WhatsApp

**URL Base**: `https://tu-dominio.com/wp-json/myd-delivery/v1`

**Formatos soportados**: JSON

**Protocolo**: HTTPS (requerido para producción)

**Rate Limiting**: 100 requests por minuto por usuario/IP

## Autenticación

### Método 1: JWT (Recomendado para Apps Móviles)

La API utiliza JSON Web Tokens (JWT) para autenticación. Este método es ideal para aplicaciones móviles.

#### Paso 1: Obtener Token

```http
POST /auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "tu_password"
}
```

**Respuesta**:
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "display_name": "Administrator",
    "roles": ["administrator"]
  },
  "expires_in": 86400
}
```

#### Paso 2: Usar Token en Requests

```http
GET /products
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

#### Paso 3: Renovar Token

```http
POST /auth/refresh
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Método 2: WordPress Application Passwords

Para integraciones de servidor a servidor, puedes usar Application Passwords de WordPress.

```http
GET /products
Authorization: Basic base64(username:app_password)
```

## Rate Limiting

La API implementa rate limiting para prevenir abuso y garantizar un servicio estable para todos los usuarios.

### Límites

- **100 requests por minuto** por usuario autenticado o dirección IP
- La ventana de tiempo se reinicia automáticamente cada 60 segundos
- Los límites son independientes por usuario/IP

### Headers de Rate Limit

Cada respuesta de la API incluye headers informativos:

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 85
X-RateLimit-Reset: 1640995200
```

| Header | Descripción |
|--------|-------------|
| `X-RateLimit-Limit` | Número máximo de requests permitidos en la ventana |
| `X-RateLimit-Remaining` | Requests restantes en la ventana actual |
| `X-RateLimit-Reset` | Timestamp Unix cuando se reinicia el contador |

### Respuesta 429 (Too Many Requests)

Cuando se excede el límite, la API retorna un error 429:

```http
HTTP/1.1 429 Too Many Requests
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1640995260
Retry-After: 45
Content-Type: application/json

{
  "code": "rate_limit_exceeded",
  "message": "Rate limit exceeded. Maximum 100 requests per minute allowed.",
  "data": {
    "status": 429
  }
}
```

El header `Retry-After` indica cuántos segundos debes esperar antes de reintentar.

### Mejores Prácticas

1. **Monitorear headers**: Verifica `X-RateLimit-Remaining` en cada respuesta
2. **Implementar backoff**: Si recibes 429, espera el tiempo indicado en `Retry-After`
3. **Caché local**: Almacena datos que no cambianfrecuentemente
4. **Batch requests**: Agrupa múltiples operaciones cuando sea posible
5. **Usar webhooks**: En lugar de polling constante

### Ejemplo de Implementación

```javascript
class ApiClient {
  constructor(baseUrl, token) {
    this.baseUrl = baseUrl;
    this.token = token;
    this.rateLimitRemaining = 100;
  }

  async request(endpoint, options = {}) {
    // Check if we should wait
    if (this.rateLimitRemaining === 0) {
      const waitTime = this.rateLimitReset - Date.now();
      if (waitTime > 0) {
        await new Promise(resolve => setTimeout(resolve, waitTime));
      }
    }

    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      ...options,
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        ...options.headers,
      },
    });

    // Update rate limit info from headers
    this.rateLimitRemaining = parseInt(
      response.headers.get('X-RateLimit-Remaining') || '100'
    );
    this.rateLimitReset = parseInt(
      response.headers.get('X-RateLimit-Reset') || '0'
    ) * 1000;

    // Handle 429
    if (response.status === 429) {
      const retryAfter = parseInt(response.headers.get('Retry-After') || '60');
      console.warn(`Rate limited. Retrying after ${retryAfter}s`);

      await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
      return this.request(endpoint, options); // Retry
    }

    return response.json();
  }
}
```

### Whitelist

Para aplicaciones de confianza que requieren mayor capacidad:

1. Contactar soporte: support@pideai.com
2. Proporcionar IP estática o user ID
3. Se agregará a la whitelist (sin límites)

**Nota**: La whitelist solo se otorga para casos justificados.

## Endpoints Disponibles

### Autenticación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/auth/login` | Iniciar sesión y obtener JWT |
| POST | `/auth/refresh` | Renovar token JWT |
| GET | `/auth/validate` | Validar token JWT |
| GET | `/auth/me` | Obtener información del usuario actual |

### Productos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/products` | Listar productos (con paginación) |
| POST | `/products` | Crear nuevo producto |
| GET | `/products/{id}` | Obtener producto específico |
| PUT | `/products/{id}` | Actualizar producto |
| DELETE | `/products/{id}` | Eliminar producto |

### Categorías

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/categories` | Listar todas las categorías |
| POST | `/categories` | Crear categoría |
| PUT | `/categories/{id}` | Actualizar categoría |
| DELETE | `/categories/{id}` | Eliminar categoría |
| PUT | `/categories/reorder` | Reordenar categorías |

### Órdenes

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/orders` | Listar órdenes (con filtros) |
| POST | `/orders` | Crear nueva orden |
| GET | `/orders/{id}` | Obtener orden específica |
| PUT | `/orders/{id}` | Actualizar orden |
| DELETE | `/orders/{id}` | Eliminar orden |

### Clientes

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/customers` | Listar clientes |
| GET | `/customers/{phone}` | Obtener cliente por teléfono |
| PUT | `/customers/{phone}` | Actualizar cliente |
| GET | `/customers/{phone}/orders` | Órdenes del cliente |
| GET | `/customers/{phone}/addresses` | Direcciones del cliente |

### Carrito

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/cart` | Obtener carrito actual |
| POST | `/cart` | Actualizar carrito completo |
| DELETE | `/cart` | Vaciar carrito |
| POST | `/cart/items` | Agregar item al carrito |
| PUT | `/cart/items/{id}` | Actualizar cantidad de item |
| DELETE | `/cart/items/{id}` | Remover item del carrito |
| POST | `/cart/calculate` | Calcular totales del carrito |

### Cupones

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/coupons` | Listar cupones |
| POST | `/coupons` | Crear cupón |
| GET | `/coupons/{id}` | Obtener cupón |
| PUT | `/coupons/{id}` | Actualizar cupón |
| DELETE | `/coupons/{id}` | Eliminar cupón |
| GET | `/coupons/validate/{code}` | Validar código de cupón |

### Configuración

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/settings` | Obtener configuración |
| PUT | `/settings` | Actualizar múltiples configuraciones |
| GET | `/settings/{key}` | Obtener configuración específica |
| PUT | `/settings/{key}` | Actualizar configuración específica |

### Reportes

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/reports/sales` | Reporte de ventas |
| GET | `/reports/products` | Reporte de productos |
| GET | `/reports/customers` | Reporte de clientes |
| GET | `/reports/overview` | Resumen general |

### Media

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/media/upload` | Subir imagen (base64) |

### WhatsApp

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/whatsapp/send-order` | Enviar orden por WhatsApp |

## Ejemplos de Integración

### Ejemplo 1: Flujo de Login en App Móvil

```javascript
// Login
async function login(username, password) {
  const response = await fetch('https://tu-dominio.com/wp-json/myd-delivery/v1/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ username, password })
  });

  const data = await response.json();

  if (data.success) {
    // Guardar token en almacenamiento local
    localStorage.setItem('jwt_token', data.token);
    localStorage.setItem('user', JSON.stringify(data.user));
    return data.user;
  } else {
    throw new Error('Login failed');
  }
}

// Usar token en requests subsecuentes
async function getProducts() {
  const token = localStorage.getItem('jwt_token');

  const response = await fetch('https://tu-dominio.com/wp-json/myd-delivery/v1/products', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    }
  });

  return await response.json();
}
```

### Ejemplo 2: Crear Orden desde App

```javascript
async function createOrder(orderData) {
  const token = localStorage.getItem('jwt_token');

  const response = await fetch('https://tu-dominio.com/wp-json/myd-delivery/v1/orders', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      customer_name: orderData.customerName,
      customer_phone: orderData.customerPhone,
      customer_address: orderData.address,
      customer_address_number: orderData.addressNumber,
      customer_neighborhood: orderData.neighborhood,
      customer_zipcode: orderData.zipcode,
      order_status: 'new',
      ship_method: orderData.deliveryMethod,
      payment_status: 'waiting',
      payment_method: orderData.paymentMethod,
      payment_type: orderData.paymentType,
      delivery_price: orderData.deliveryPrice,
      coupon: orderData.coupon,
      subtotal: orderData.subtotal,
      total: orderData.total,
      items: orderData.items,
      notes: orderData.notes
    })
  });

  return await response.json();
}
```

### Ejemplo 3: Gestión de Carrito

```javascript
// Agregar producto al carrito
async function addToCart(productId, quantity, extras = []) {
  const response = await fetch('https://tu-dominio.com/wp-json/myd-delivery/v1/cart/items', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      product_id: productId,
      quantity: quantity,
      extras: extras
    })
  });

  return await response.json();
}

// Obtener carrito actual
async function getCart() {
  const response = await fetch('https://tu-dominio.com/wp-json/myd-delivery/v1/cart');
  return await response.json();
}

// Calcular totales
async function calculateCart(items, coupon, deliveryMethod, deliveryPrice) {
  const response = await fetch('https://tu-dominio.com/wp-json/myd-delivery/v1/cart/calculate', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      items: items,
      coupon: coupon,
      delivery_method: deliveryMethod,
      delivery_price: deliveryPrice
    })
  });

  return await response.json();
}
```

### Ejemplo 4: Validar Cupón

```javascript
async function validateCoupon(code) {
  const response = await fetch(
    `https://tu-dominio.com/wp-json/myd-delivery/v1/coupons/validate/${code}`
  );

  const data = await response.json();

  if (data.valid) {
    console.log('Cupón válido:', data.coupon);
    return data.coupon;
  } else {
    console.log('Cupón inválido:', data.message);
    return null;
  }
}
```

### Ejemplo 5: Subir Imagen de Producto

```javascript
async function uploadProductImage(file) {
  const token = localStorage.getItem('jwt_token');

  // Convertir imagen a base64
  const reader = new FileReader();
  reader.readAsDataURL(file);

  return new Promise((resolve, reject) => {
    reader.onload = async () => {
      const base64Image = reader.result;

      const response = await fetch('https://tu-dominio.com/wp-json/myd-delivery/v1/media/upload', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          filename: file.name,
          file_data: base64Image,
          title: file.name,
          alt_text: 'Product image'
        })
      });

      const data = await response.json();
      resolve(data.url);
    };

    reader.onerror = reject;
  });
}
```

### Ejemplo 6: Obtener Reportes

```javascript
async function getSalesReport(dateFrom, dateTo) {
  const token = localStorage.getItem('jwt_token');

  const params = new URLSearchParams({
    date_from: dateFrom, // YYYY-MM-DD
    date_to: dateTo
  });

  const response = await fetch(
    `https://tu-dominio.com/wp-json/myd-delivery/v1/reports/sales?${params}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
      }
    }
  );

  return await response.json();
}
```

## Manejo de Errores

La API utiliza códigos de estado HTTP estándar:

| Código | Significado | Descripción |
|--------|-------------|-------------|
| 200 | OK | Solicitud exitosa |
| 201 | Created | Recurso creado exitosamente |
| 400 | Bad Request | Solicitud malformada o parámetros inválidos |
| 401 | Unauthorized | No autenticado o token inválido |
| 403 | Forbidden | Autenticado pero sin permisos |
| 404 | Not Found | Recurso no encontrado |
| 500 | Internal Server Error | Error del servidor |

### Ejemplo de Respuesta de Error

```json
{
  "code": "invalid_credentials",
  "message": "Invalid username or password",
  "data": {
    "status": 401
  }
}
```

### Manejo de Errores en JavaScript

```javascript
async function apiRequest(endpoint, options = {}) {
  try {
    const response = await fetch(`https://tu-dominio.com/wp-json/myd-delivery/v1${endpoint}`, options);

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'API request failed');
    }

    return await response.json();
  } catch (error) {
    console.error('API Error:', error);

    // Manejar errores específicos
    if (error.message.includes('token')) {
      // Token expirado, renovar o redirigir a login
      await refreshToken();
    }

    throw error;
  }
}
```

## Mejores Prácticas

### 1. Seguridad

- **Siempre usa HTTPS** en producción
- **No guardes credenciales** en el código
- **Almacena el JWT de forma segura** (Keychain en iOS, EncryptedSharedPreferences en Android)
- **Implementa renovación automática de tokens** antes de que expiren
- **Valida datos** antes de enviarlos a la API

### 2. Performance

- **Usa paginación** para listas grandes de datos
- **Implementa caché local** para productos y categorías
- **Comprime imágenes** antes de subirlas (max 1MB)
- **Debounce búsquedas** para evitar múltiples requests

### 3. Manejo de Estado

```javascript
// Ejemplo de interceptor para renovar token automáticamente
let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach(prom => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve(token);
    }
  });

  failedQueue = [];
};

async function fetchWithTokenRefresh(url, options = {}) {
  try {
    const response = await fetch(url, options);

    if (response.status === 401) {
      if (!isRefreshing) {
        isRefreshing = true;

        try {
          const newToken = await refreshToken();
          processQueue(null, newToken);

          // Reintentar request original
          options.headers.Authorization = `Bearer ${newToken}`;
          return await fetch(url, options);
        } catch (error) {
          processQueue(error, null);
          throw error;
        } finally {
          isRefreshing = false;
        }
      }

      // Esperar a que se renueve el token
      return new Promise((resolve, reject) => {
        failedQueue.push({ resolve, reject });
      }).then(token => {
        options.headers.Authorization = `Bearer ${token}`;
        return fetch(url, options);
      });
    }

    return response;
  } catch (error) {
    throw error;
  }
}
```

### 4. Testing

```javascript
// Ejemplo de tests con Jest
describe('MyD Delivery API', () => {
  test('Login should return token', async () => {
    const response = await login('testuser', 'testpass');
    expect(response).toHaveProperty('token');
    expect(response).toHaveProperty('user');
  });

  test('Get products should return array', async () => {
    const response = await getProducts();
    expect(response).toHaveProperty('products');
    expect(Array.isArray(response.products)).toBe(true);
  });

  test('Create order should return order ID', async () => {
    const orderData = {
      customer_name: 'Test Customer',
      customer_phone: '+1234567890',
      items: [
        { product_id: 1, quantity: 2 }
      ],
      total: 50.00
    };

    const response = await createOrder(orderData);
    expect(response).toHaveProperty('id');
  });
});
```

## FAQ

### ¿Cómo manejo la autenticación en una app móvil?

Usa JWT tokens. Guarda el token de forma segura en el device (Keychain/Keystore) y envíalo en el header Authorization de cada request.

### ¿Qué hago cuando el token expira?

Implementa renovación automática usando el endpoint `/auth/refresh` cuando recibas un error 401. Guarda el nuevo token y reintenta el request.

### ¿Puedo usar la API sin autenticación?

Algunos endpoints son públicos (como validar cupón o ver productos), pero la mayoría requieren autenticación para proteger datos sensibles.

### ¿Cómo filtro y pagino los resultados?

Usa los parámetros de query string:
```
GET /products?page=2&per_page=20&search=pizza&category=comidas
GET /orders?status=finished&date_from=2024-01-01&date_to=2024-01-31
```

### ¿Cómo subo imágenes de productos?

Convierte la imagen a base64 y envíala al endpoint `/media/upload`. La respuesta incluirá la URL de la imagen subida.

### ¿La API tiene rate limiting?

Sí, la API está limitada a 100 requests por minuto por usuario autenticado. Implementa retry logic con exponential backoff.

### ¿Cómo pruebo la API?

1. Usa Postman o Insomnia para probar endpoints
2. Importa la especificación OpenAPI desde `docs/api/openapi.yaml`
3. Configura el ambiente con tu URL base y token JWT

### ¿Dónde está la documentación OpenAPI?

La especificación OpenAPI 3.0 completa está en:
`/wp-content/plugins/myd-delivery-pro/docs/api/openapi.yaml`

Puedes importarla en Swagger UI, Postman o cualquier herramienta compatible con OpenAPI.

## Soporte

Para reportar problemas o solicitar ayuda:

- **Email**: support@pideai.com
- **GitHub Issues**: [github.com/pideai/myd-delivery-pro/issues](https://github.com/pideai/myd-delivery-pro/issues)
- **Documentación**: [docs.pideai.com](https://docs.pideai.com)

## Changelog

### v2.3.9 (2025-01-XX)
- ✅ Nueva API de autenticación JWT
- ✅ Endpoint de categorías
- ✅ API de carrito de compras
- ✅ Documentación OpenAPI completa

### v2.3.8 (Actual)
- API de productos, órdenes, clientes
- API de cupones con validación
- API de reportes
- API de configuración
- Upload de imágenes

---

**© 2025 PideAI - MyD Delivery Pro**

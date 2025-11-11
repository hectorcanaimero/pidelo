# API Error Codes

Esta guía documenta todos los códigos de error que puede devolver la API de MyD Delivery Pro.

## Formato de Error

Todos los errores siguen este formato:

```json
{
  "code": "error_code",
  "message": "Descripción del error",
  "data": {
    "status": 400
  }
}
```

## Códigos de Error HTTP

- **200**: Operación exitosa
- **400**: Solicitud incorrecta (error de validación)
- **401**: No autenticado
- **403**: Prohibido (sin permisos)
- **404**: Recurso no encontrado
- **429**: Demasiadas peticiones (rate limit excedido)
- **500**: Error interno del servidor

---

## Authentication Errors

### `invalid_credentials`
**HTTP 401**
```json
{
  "code": "invalid_credentials",
  "message": "Usuario o contraseña incorrectos",
  "data": {
    "status": 401
  }
}
```

### `token_expired`
**HTTP 401**
```json
{
  "code": "token_expired",
  "message": "El token ha expirado",
  "data": {
    "status": 401
  }
}
```

### `invalid_token`
**HTTP 401**
```json
{
  "code": "invalid_token",
  "message": "Token inválido o malformado",
  "data": {
    "status": 401
  }
}
```

### `token_signature_invalid`
**HTTP 401**
```json
{
  "code": "token_signature_invalid",
  "message": "La firma del token no es válida",
  "data": {
    "status": 401
  }
}
```

---

## Rate Limiting Errors

### `rate_limit_exceeded`
**HTTP 429**
```json
{
  "code": "rate_limit_exceeded",
  "message": "Has excedido el límite de 100 peticiones por minuto",
  "data": {
    "status": 429,
    "retry_after": 45
  }
}
```

**Headers incluidos:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1704729600
Retry-After: 45
```

---

## Categories Errors

### `duplicate_category`
**HTTP 400**
```json
{
  "code": "duplicate_category",
  "message": "Ya existe una categoría con ese nombre",
  "data": {
    "status": 400,
    "field": "name"
  }
}
```

### `category_not_found`
**HTTP 404**
```json
{
  "code": "category_not_found",
  "message": "La categoría no existe",
  "data": {
    "status": 404,
    "category_id": 123
  }
}
```

### `category_has_products`
**HTTP 400**
```json
{
  "code": "category_has_products",
  "message": "No se puede eliminar una categoría que contiene productos",
  "data": {
    "status": 400,
    "category_id": 5,
    "product_count": 12
  }
}
```

### `missing_category_name`
**HTTP 400**
```json
{
  "code": "missing_category_name",
  "message": "El nombre de la categoría es requerido",
  "data": {
    "status": 400,
    "field": "name"
  }
}
```

---

## Cart Errors

### `invalid_coupon`
**HTTP 400**
```json
{
  "code": "invalid_coupon",
  "message": "El cupón no existe o ha expirado",
  "data": {
    "status": 400,
    "coupon_code": "DESCUENTO10"
  }
}
```

### `coupon_not_applicable`
**HTTP 400**
```json
{
  "code": "coupon_not_applicable",
  "message": "El cupón no cumple las condiciones mínimas",
  "data": {
    "status": 400,
    "coupon_code": "DESCUENTO10",
    "minimum_amount": 50.00,
    "current_amount": 30.00
  }
}
```

### `product_not_available`
**HTTP 400**
```json
{
  "code": "product_not_available",
  "message": "El producto no está disponible",
  "data": {
    "status": 400,
    "product_id": 15,
    "product_name": "Pizza Margarita"
  }
}
```

### `invalid_quantity`
**HTTP 400**
```json
{
  "code": "invalid_quantity",
  "message": "La cantidad debe ser mayor a 0",
  "data": {
    "status": 400,
    "field": "quantity",
    "value": 0
  }
}
```

### `product_not_found`
**HTTP 404**
```json
{
  "code": "product_not_found",
  "message": "El producto no existe",
  "data": {
    "status": 404,
    "product_id": 999
  }
}
```

### `cart_empty`
**HTTP 400**
```json
{
  "code": "cart_empty",
  "message": "El carrito está vacío",
  "data": {
    "status": 400
  }
}
```

---

## Products Errors

### `product_not_found`
**HTTP 404**
```json
{
  "code": "product_not_found",
  "message": "El producto no existe",
  "data": {
    "status": 404,
    "product_id": 123
  }
}
```

### `missing_product_name`
**HTTP 400**
```json
{
  "code": "missing_product_name",
  "message": "El nombre del producto es requerido",
  "data": {
    "status": 400,
    "field": "name"
  }
}
```

### `invalid_price`
**HTTP 400**
```json
{
  "code": "invalid_price",
  "message": "El precio debe ser mayor a 0",
  "data": {
    "status": 400,
    "field": "price",
    "value": -5.00
  }
}
```

---

## Orders Errors

### `order_not_found`
**HTTP 404**
```json
{
  "code": "order_not_found",
  "message": "La orden no existe",
  "data": {
    "status": 404,
    "order_id": 456
  }
}
```

### `invalid_status_transition`
**HTTP 400**
```json
{
  "code": "invalid_status_transition",
  "message": "No se puede cambiar de 'canceled' a 'in-process'",
  "data": {
    "status": 400,
    "current_status": "canceled",
    "new_status": "in-process"
  }
}
```

### `order_already_paid`
**HTTP 400**
```json
{
  "code": "order_already_paid",
  "message": "Esta orden ya ha sido pagada",
  "data": {
    "status": 400,
    "order_id": 123,
    "payment_status": "paid"
  }
}
```

### `minimum_order_amount`
**HTTP 400**
```json
{
  "code": "minimum_order_amount",
  "message": "El monto mínimo de orden es $10.00",
  "data": {
    "status": 400,
    "minimum": 10.00,
    "current": 7.50
  }
}
```

---

## Permission Errors

### `rest_forbidden`
**HTTP 403**
```json
{
  "code": "rest_forbidden",
  "message": "No tienes permisos para realizar esta acción",
  "data": {
    "status": 403
  }
}
```

### `rest_cannot_edit`
**HTTP 403**
```json
{
  "code": "rest_cannot_edit",
  "message": "No tienes permisos para editar este recurso",
  "data": {
    "status": 403
  }
}
```

### `rest_cannot_delete`
**HTTP 403**
```json
{
  "code": "rest_cannot_delete",
  "message": "No tienes permisos para eliminar este recurso",
  "data": {
    "status": 403
  }
}
```

---

## Validation Errors

### `rest_invalid_param`
**HTTP 400**
```json
{
  "code": "rest_invalid_param",
  "message": "Parámetro inválido",
  "data": {
    "status": 400,
    "params": {
      "email": "El formato del email no es válido"
    }
  }
}
```

### `rest_missing_callback_param`
**HTTP 400**
```json
{
  "code": "rest_missing_callback_param",
  "message": "Falta un parámetro requerido",
  "data": {
    "status": 400,
    "param": "name"
  }
}
```

---

## General Errors

### `rest_no_route`
**HTTP 404**
```json
{
  "code": "rest_no_route",
  "message": "Endpoint no encontrado",
  "data": {
    "status": 404
  }
}
```

### `internal_server_error`
**HTTP 500**
```json
{
  "code": "internal_server_error",
  "message": "Ha ocurrido un error interno del servidor",
  "data": {
    "status": 500
  }
}
```

---

## Manejo de Errores

### Ejemplo de manejo en JavaScript:

```javascript
try {
  const response = await fetch('https://tu-sitio.com/wp-json/myd-delivery/v1/cart', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      items: [{ product_id: 15, quantity: 2 }]
    })
  });

  const data = await response.json();

  if (!response.ok) {
    // Manejar errores específicos
    switch (data.code) {
      case 'rate_limit_exceeded':
        const retryAfter = response.headers.get('Retry-After');
        console.log(`Espera ${retryAfter} segundos antes de reintentar`);
        break;

      case 'invalid_token':
        // Redirigir a login
        window.location.href = '/login';
        break;

      case 'product_not_available':
        alert(`El producto ${data.data.product_name} no está disponible`);
        break;

      default:
        alert(data.message);
    }
    return;
  }

  // Éxito
  console.log('Carrito actualizado:', data);

} catch (error) {
  console.error('Error de red:', error);
}
```

### Ejemplo en PHP:

```php
$response = wp_remote_post('https://tu-sitio.com/wp-json/myd-delivery/v1/cart', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
    ],
    'body' => json_encode([
        'items' => [
            ['product_id' => 15, 'quantity' => 2]
        ]
    ])
]);

if (is_wp_error($response)) {
    // Error de red
    error_log('Error de red: ' . $response->get_error_message());
    return;
}

$status_code = wp_remote_retrieve_response_code($response);
$body = json_decode(wp_remote_retrieve_body($response), true);

if ($status_code !== 200) {
    // Manejar error
    switch ($body['code']) {
        case 'rate_limit_exceeded':
            $retry_after = wp_remote_retrieve_header($response, 'retry-after');
            error_log("Espera {$retry_after} segundos");
            break;

        case 'invalid_token':
            // Refrescar token o redirigir a login
            break;

        default:
            error_log('Error API: ' . $body['message']);
    }
    return;
}

// Éxito
$cart = $body['data'];
```

---

## Mejores Prácticas

1. **Siempre verifica el código HTTP** antes de parsear la respuesta
2. **Implementa reintentos automáticos** para errores 429 (respetando Retry-After)
3. **Registra los errores** para debugging pero nunca expongas detalles sensibles al usuario
4. **Valida datos en el cliente** antes de enviarlos a la API para reducir errores 400
5. **Maneja tokens expirados** refrescándolos automáticamente o redirigiendo a login
6. **Muestra mensajes amigables** al usuario basados en `message`, no en `code`

---

## Testing de Errores

Para testing, puedes forzar ciertos errores:

```bash
# Token inválido
curl -X GET "https://tu-sitio.com/wp-json/myd-delivery/v1/cart" \
  -H "Authorization: Bearer token_invalido"

# Rate limit (hacer 101 requests en menos de 1 minuto)
for i in {1..101}; do
  curl -X GET "https://tu-sitio.com/wp-json/myd-delivery/v1/categories" \
    -H "Authorization: Bearer $TOKEN"
done

# Cupón inválido
curl -X POST "https://tu-sitio.com/wp-json/myd-delivery/v1/cart" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"coupon": "CUPON_NO_EXISTE"}'
```

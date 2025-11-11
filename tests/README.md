# MyD Delivery Pro - Tests

Este directorio contiene los tests automatizados para el plugin MyD Delivery Pro.

## Requisitos

- PHP 7.4 o superior
- PHPUnit 9.x
- WordPress Test Suite
- Composer (opcional, pero recomendado)

## Instalación del Entorno de Tests

### 1. Instalar PHPUnit

```bash
composer require --dev phpunit/phpunit ^9.0
```

### 2. Configurar WordPress Test Suite

```bash
# Instalar el script de instalación
cd /tmp
svn co https://develop.svn.wordpress.org/trunk/tests/phpunit/includes/
svn co https://develop.svn.wordpress.org/trunk/tests/phpunit/data/

# Crear base de datos de tests
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS wordpress_test;"

# Configurar variables de entorno
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
export WP_CORE_DIR=/path/to/wordpress
```

### 3. Instalar dependencias (opcional)

Si usas Composer, crea un archivo `composer.json` en la raíz del plugin:

```json
{
  "require-dev": {
    "phpunit/phpunit": "^9.0",
    "yoast/phpunit-polyfills": "^1.0"
  },
  "autoload": {
    "psr-4": {
      "MydPro\\": "includes/"
    }
  }
}
```

Luego ejecuta:

```bash
composer install
```

## Ejecutar Tests

### Ejecutar todos los tests

```bash
./vendor/bin/phpunit
```

o si PHPUnit está instalado globalmente:

```bash
phpunit
```

### Ejecutar tests específicos

```bash
# Solo tests de Categories API
./vendor/bin/phpunit tests/api/test-categories-api.php

# Solo un test específico
./vendor/bin/phpunit --filter test_get_categories_empty tests/api/test-categories-api.php
```

### Ejecutar con cobertura de código

```bash
./vendor/bin/phpunit --coverage-html coverage/
```

Esto generará un reporte HTML en el directorio `coverage/`.

### Ejecutar en modo verbose

```bash
./vendor/bin/phpunit --verbose
```

## Estructura de Tests

```
tests/
├── bootstrap.php          # Archivo de inicialización
├── README.md             # Esta guía
└── api/
    └── test-categories-api.php  # Tests de Categories API
```

## Tests Disponibles

### Categories API (`test-categories-api.php`)

✅ **GET /categories**
- Listar categorías vacías
- Listar categorías con datos
- Acceso público sin autenticación
- Conteo de productos por categoría

✅ **POST /categories**
- Crear categoría como administrador
- Crear categoría sin permisos (403)
- Crear categoría sin nombre (400)
- Crear categoría duplicada (400)
- Validación y sanitización de datos

✅ **PUT /categories/{id}**
- Actualizar categoría existente
- Actualizar categoría inexistente (404)
- Actualizar sin permisos (403)

✅ **DELETE /categories/{id}**
- Eliminar categoría existente
- Eliminar categoría inexistente (404)
- Eliminar sin permisos (403)

✅ **PUT /categories/reorder**
- Reordenar categorías correctamente
- Reordenar con orden inválido (400)
- Reordenar sin permisos (403)

## Variables de Entorno

Puedes configurar las siguientes variables de entorno:

```bash
# Directorio de WordPress tests
export WP_TESTS_DIR=/tmp/wordpress-tests-lib

# Configuración de base de datos
export DB_NAME=wordpress_test
export DB_USER=root
export DB_PASSWORD=password
export DB_HOST=localhost
```

## Integración Continua (CI)

Para integrar con GitHub Actions, crea `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:5.7
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
        ports:
          - 3306:3306

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          extensions: mysqli
          coverage: xdebug

      - name: Install dependencies
        run: composer install

      - name: Setup WordPress test suite
        run: |
          bash bin/install-wp-tests.sh wordpress_test root root localhost latest

      - name: Run tests
        run: ./vendor/bin/phpunit --coverage-text
```

## Tips para Escribir Tests

### 1. Usar factories para crear datos de prueba

```php
$product_id = $this->factory->post->create([
    'post_type' => 'mydelivery-produtos',
    'post_status' => 'publish',
]);
```

### 2. Limpiar datos después de cada test

```php
public function tearDown(): void {
    parent::tearDown();
    delete_option('fdm-list-menu-categories');
}
```

### 3. Usar assertions descriptivos

```php
$this->assertEquals(200, $response->get_status(), 'La respuesta debería ser 200 OK');
$this->assertArrayHasKey('categories', $data, 'La respuesta debe incluir el array de categorías');
```

### 4. Probar casos edge

- Datos vacíos
- Datos inválidos
- Permisos insuficientes
- Recursos no encontrados
- Datos duplicados

## Debugging

### Ver salida de tests

```bash
./vendor/bin/phpunit --debug
```

### Ver queries SQL ejecutadas

Agrega esto en tu test:

```php
add_filter('query', function($query) {
    error_log($query);
    return $query;
});
```

### Usar var_dump en tests

```php
public function test_something() {
    $data = $response->get_data();
    var_dump($data); // Solo se muestra en modo --debug

    $this->assertTrue(true);
}
```

## Recursos

- [WordPress Plugin Unit Tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WP REST API Testing](https://developer.wordpress.org/rest-api/using-the-rest-api/testing/)
- [Best Practices for WordPress Plugin Testing](https://developer.wordpress.org/plugins/testing/)

## Soporte

Para reportar problemas con los tests:

- **Email**: support@pideai.com
- **GitHub Issues**: [github.com/pideai/myd-delivery-pro/issues](https://github.com/pideai/myd-delivery-pro/issues)

---

**© 2025 PideAI - MyD Delivery Pro**

# Testing - Sistema de Notificaciones de Updates

Guía completa de testing para el sistema mejorado de notificaciones de actualización.

## 🎯 Objetivo

Verificar que todas las funcionalidades del sistema de notificaciones funcionan correctamente:
- Dashboard Widget
- Email notifications
- Auto-update
- Update history
- Settings page
- Menu badge

## 📋 Pre-requisitos

### Entorno de Testing

```bash
# WordPress
- Versión: 5.5+
- PHP: 7.4+
- Base de datos: MySQL 5.6+ o MariaDB 10.0+

# Plugin Requirements
- MyD Delivery Pro instalado
- Licencia activada (para testing de updates)
- Email configurado en WordPress (para email testing)
```

### Setup Inicial

```bash
# 1. Habilitar debug mode
# En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

# 2. Limpiar cache
wp transient delete --all

# 3. Verificar licencia activa
wp transient get myd_license_data
```

## 🧪 Test Cases

### 1. Dashboard Widget

#### Test 1.1: Widget aparece en dashboard

**Pasos:**
1. Login como administrador
2. Ir a Dashboard (wp-admin)
3. Verificar que aparece widget "MyD Delivery Pro - Estado de Actualización"

**Resultado esperado:**
✅ Widget visible en dashboard
✅ Muestra versión actual
✅ Muestra estado (actualizado/update disponible)
✅ Tiene diseño responsive

#### Test 1.2: Widget muestra update disponible

**Setup:**
```bash
# Simular versión antigua
# En myd-delivery-pro.php temporalmente:
define('MYD_CURRENT_VERSION', '1.0.0');

# Limpiar cache
wp transient delete mydpro-update-data
```

**Pasos:**
1. Refrescar dashboard
2. Ver contenido del widget

**Resultado esperado:**
✅ Muestra "Actualización Disponible"
✅ Muestra versión actual (1.0.0)
✅ Muestra nueva versión disponible
✅ Muestra lista de features nuevas
✅ Botón "Actualizar Ahora" funciona
✅ Link a changelog funciona

#### Test 1.3: Widget con licencia inválida

**Setup:**
```bash
wp transient delete myd_license_data
```

**Pasos:**
1. Refrescar dashboard
2. Ver contenido del widget

**Resultado esperado:**
✅ Muestra warning de licencia requerida
✅ Link a página de licencia funciona
✅ No muestra botón de actualizar

#### Test 1.4: Widget cuando está actualizado

**Setup:**
```bash
# Restaurar versión real
# Limpiar cache
wp transient delete mydpro-update-data
```

**Pasos:**
1. Refrescar dashboard

**Resultado esperado:**
✅ Muestra "Plugin Actualizado" con ✅
✅ Muestra solo versión actual
✅ Botón "Verificar Actualizaciones" funciona

---

### 2. Email Notifications

#### Test 2.1: Configurar email notifications

**Pasos:**
1. Ir a MyD Delivery → Actualizaciones
2. Marcar checkbox "Enviar notificaciones por email"
3. Click "Guardar Configuración"

**Resultado esperado:**
✅ Mensaje "Configuración guardada exitosamente"
✅ Checkbox permanece marcado después de guardar
✅ Opción guardada en base de datos

**Verificar:**
```bash
wp option get myd_update_email_enabled
# Debe retornar: 1
```

#### Test 2.2: Email de prueba

**Pasos:**
1. En página de Actualizaciones
2. Click botón "📧 Enviar Email de Prueba"
3. Revisar bandeja de entrada del admin

**Resultado esperado:**
✅ Mensaje "Email de prueba enviado exitosamente"
✅ Email recibido en bandeja de admin
✅ Email tiene formato correcto (HTML)
✅ Links en email funcionan

**Contenido del email debe incluir:**
- Asunto: "[Sitio] Email de prueba - Notificaciones de actualización"
- Versión actual del plugin
- Link a configuración
- Diseño profesional

#### Test 2.3: Email automático cuando hay update

**Setup:**
```bash
# Simular nueva versión
# En update-info.json en gh-pages, cambiar versión a 99.0.0

# Limpiar cache
wp transient delete mydpro-update-data
wp transient delete update_plugins

# Verificar email habilitado
wp option get myd_update_email_enabled
```

**Pasos:**
1. Forzar check de updates: `/wp-admin/plugins.php?force-check=1`
2. Esperar procesamiento
3. Revisar bandeja de entrada

**Resultado esperado:**
✅ Email recibido automáticamente
✅ Email muestra versión actual vs nueva
✅ Email muestra changelog
✅ Botón "Actualizar Ahora" redirige a plugins
✅ Link a changelog completo funciona

#### Test 2.4: Email no se envía duplicado

**Pasos:**
1. Después del test anterior, forzar check de nuevo
2. Revisar bandeja de entrada

**Resultado esperado:**
✅ No se recibe segundo email (ya notificado sobre v99.0.0)

**Verificar:**
```bash
wp option get myd_update_email_last_sent
# Debe ser: 99.0.0
```

#### Test 2.5: Email solo con licencia válida

**Setup:**
```bash
# Invalidar licencia
wp transient delete myd_license_data
```

**Pasos:**
1. Forzar check de updates
2. Revisar bandeja de entrada

**Resultado esperado:**
✅ NO se envía email (licencia inválida)

---

### 3. Auto-Update

#### Test 3.1: Habilitar auto-update

**Pasos:**
1. Ir a MyD Delivery → Actualizaciones
2. Marcar "Habilitar actualizaciones automáticas"
3. Click "Guardar Configuración"

**Resultado esperado:**
✅ Configuración guardada
✅ Checkbox permanece marcado

**Verificar:**
```bash
wp option get myd_auto_update_enabled
# Debe retornar: 1
```

#### Test 3.2: Auto-update funciona

**⚠️ IMPORTANTE:** Este test puede actualizar el plugin. Hacer backup primero.

**Setup:**
```bash
# Backup completo del plugin
cp -r /path/to/myd-delivery-pro /path/to/backup/

# Simular versión antigua
# En myd-delivery-pro.php
define('MYD_CURRENT_VERSION', '2.3.0');
```

**Pasos:**
1. Esperar cron de WordPress (o forzar: `wp cron event run --due-now`)
2. Verificar si se actualizó automáticamente

**Resultado esperado:**
✅ Plugin se actualiza automáticamente
✅ Sin errores en el proceso
✅ Entrada en historial de updates

**Verificar:**
```bash
wp plugin get myd-delivery-pro --field=version
# Debe mostrar nueva versión

# Ver historial
wp option get myd_update_history --format=json
```

#### Test 3.3: Auto-update respeta configuración

**Setup:**
```bash
# Deshabilitar auto-update
wp option update myd_auto_update_enabled '0'
```

**Pasos:**
1. Forzar cron event
2. Verificar que NO se actualiza

**Resultado esperado:**
✅ Plugin NO se actualiza automáticamente

---

### 4. Update History

#### Test 4.1: Historial se muestra correctamente

**Pasos:**
1. Ir a MyD Delivery → Actualizaciones
2. Scroll a sección "Historial de Actualizaciones"

**Resultado esperado:**
✅ Tabla de historial visible
✅ Muestra columnas: Versión, Fecha, Estado, Usuario, Entorno
✅ Entradas ordenadas por fecha (más reciente primero)
✅ Estados con badges de colores (✓ verde, ✗ rojo)

#### Test 4.2: Actualización se registra en historial

**Setup:**
```bash
# Realizar una actualización manual
```

**Pasos:**
1. Ir a Plugins
2. Actualizar MyD Delivery Pro
3. Ir a MyD Delivery → Actualizaciones
4. Ver historial

**Resultado esperado:**
✅ Nueva entrada en historial
✅ Muestra versión actualizada
✅ Estado: "Exitosa"
✅ Muestra usuario que actualizó
✅ Muestra versión de WP y PHP

#### Test 4.3: Exportar historial como CSV

**Pasos:**
1. En página de Actualizaciones
2. Click "📥 Exportar como CSV"
3. Abrir archivo descargado

**Resultado esperado:**
✅ Archivo CSV descargado
✅ Nombre: `myd-update-history-YYYY-MM-DD.csv`
✅ Contiene todas las columnas
✅ Formato correcto (puede abrirse en Excel)

**Verificar contenido CSV:**
```csv
Version,Date,Time,Success,User,Error,WP Version,PHP Version
"2.4.0","2025-11-10","14:30:00","Yes","admin","","6.4","8.1"
```

#### Test 4.4: Limpiar historial

**Pasos:**
1. Click "🗑️ Limpiar Historial"
2. Confirmar en popup
3. Verificar tabla

**Resultado esperado:**
✅ Confirmación solicitada
✅ Mensaje "Historial limpiado exitosamente"
✅ Tabla muestra "No hay historial de actualizaciones aún"

**Verificar:**
```bash
wp option get myd_update_history
# Debe retornar: empty array o false
```

#### Test 4.5: Estadísticas se calculan correctamente

**Setup:**
```bash
# Agregar datos de prueba
wp option update myd_update_history '[
  {"version":"2.4.0","timestamp":1699999999,"success":true,"user_login":"admin"},
  {"version":"2.3.0","timestamp":1699999998,"success":true,"user_login":"admin"},
  {"version":"2.2.0","timestamp":1699999997,"success":false,"error":"Connection timeout","user_login":"admin"}
]'
```

**Pasos:**
1. Refrescar página de Actualizaciones
2. Ver sección de Estadísticas

**Resultado esperado:**
✅ Total Actualizaciones: 3
✅ Exitosas: 2
✅ Fallidas: 1
✅ Tasa de Éxito: 66.67%
✅ Última actualización muestra v2.4.0

---

### 5. Settings Page

#### Test 5.1: Página de settings accesible

**Pasos:**
1. Ir a MyD Delivery → Actualizaciones

**Resultado esperado:**
✅ Página carga sin errores
✅ Muestra 3 secciones principales:
  - Configuración de Notificaciones
  - Estadísticas
  - Historial

#### Test 5.2: Permisos correctos

**Pasos:**
1. Logout
2. Login como usuario sin rol de administrador (ej: editor)
3. Intentar acceder a página de Actualizaciones

**Resultado esperado:**
✅ Página no accesible
✅ Error de permisos o redirect

#### Test 5.3: Configuraciones persisten

**Pasos:**
1. Habilitar email notifications
2. Habilitar auto-update
3. Guardar
4. Refrescar página

**Resultado esperado:**
✅ Ambos checkboxes siguen marcados
✅ Configuración guardada en base de datos

---

### 6. Menu Badge

#### Test 6.1: Badge aparece cuando hay update

**Setup:**
```bash
# Simular update disponible
define('MYD_CURRENT_VERSION', '1.0.0');
wp transient delete mydpro-update-data
```

**Pasos:**
1. Refrescar admin
2. Ver menú lateral "MyD Delivery"

**Resultado esperado:**
✅ Badge rojo con "1" aparece al lado del menú
✅ Badge usa estilos estándar de WordPress
✅ Badge visible en menú expandido y colapsado

#### Test 6.2: Badge no aparece cuando está actualizado

**Setup:**
```bash
# Restaurar versión real
wp transient delete mydpro-update-data
```

**Pasos:**
1. Refrescar admin
2. Ver menú lateral

**Resultado esperado:**
✅ NO hay badge en menú

#### Test 6.3: Badge no aparece sin licencia válida

**Setup:**
```bash
# Invalidar licencia
wp transient delete myd_license_data

# Simular update
define('MYD_CURRENT_VERSION', '1.0.0');
```

**Pasos:**
1. Refrescar admin
2. Ver menú

**Resultado esperado:**
✅ NO hay badge (licencia inválida, no debe ver updates)

---

## 🔍 Tests de Integración

### Integration Test 1: Flujo completo de actualización

**Scenario:** Usuario con licencia válida recibe y aplica update

**Pasos:**
1. Configurar email notifications habilitadas
2. Configurar auto-update deshabilitado
3. Simular nueva versión disponible (update-info.json)
4. WordPress detecta update (cada 12 horas o force-check)
5. Usuario recibe email de notificación
6. Usuario ve badge en menú
7. Usuario ve notificación en dashboard widget
8. Usuario ve notificación en plugins page
9. Usuario click "Actualizar Ahora"
10. Actualización se completa exitosamente
11. Entrada se guarda en historial
12. Badge desaparece
13. Dashboard widget muestra "Actualizado"

**Resultado esperado:**
✅ Todo el flujo funciona sin errores
✅ Email recibido
✅ Update aplicado correctamente
✅ Historial guardado
✅ UI actualizada

### Integration Test 2: Actualización automática

**Scenario:** Plugin se actualiza automáticamente sin intervención

**Pasos:**
1. Habilitar auto-update
2. Habilitar email notifications
3. Simular nueva versión
4. Esperar cron de WordPress (o forzar)
5. Verificar update aplicado
6. Verificar email enviado
7. Verificar historial

**Resultado esperado:**
✅ Plugin actualizado automáticamente
✅ Email notificación recibido post-update
✅ Historial registra update automático

### Integration Test 3: Licencia expira durante update

**Scenario:** Licencia expira, updates dejan de mostrarse

**Pasos:**
1. Configurar licencia activa
2. Simular update disponible
3. Verificar que update se muestra
4. Cambiar licencia a "expired"
5. Limpiar cache
6. Verificar UI

**Resultado esperado:**
✅ Update deja de mostrarse
✅ Admin notice aparece sobre licencia expirada
✅ Dashboard widget muestra warning
✅ Badge desaparece

---

## 🐛 Tests de Error Handling

### Error Test 1: Email server falla

**Setup:**
```bash
# Forzar fallo de email (temporalmente romper configuración SMTP)
```

**Pasos:**
1. Enviar email de prueba
2. Ver resultado

**Resultado esperado:**
✅ Mensaje de error: "Error al enviar email de prueba"
✅ No crash del plugin
✅ Error logged en debug.log

### Error Test 2: GitHub Pages down

**Setup:**
```bash
# Cambiar URL temporalmente a endpoint inválido
# En class-plugin-update.php:
const URL = 'https://invalid-url-that-does-not-exist.com/update.json';
```

**Pasos:**
1. Forzar check de updates
2. Ver dashboard widget
3. Ver logs

**Resultado esperado:**
✅ No crash del plugin
✅ Dashboard widget muestra "Verificar Actualizaciones"
✅ Error logged: "MyD Update Check Error: ..."
✅ No emails enviados

### Error Test 3: Actualización falla

**Setup:**
```bash
# Simular fallo de actualización (permisos incorrectos)
chmod 000 /path/to/myd-delivery-pro/
```

**Pasos:**
1. Intentar actualizar
2. Ver resultado
3. Ver historial

**Resultado esperado:**
✅ WordPress muestra error de actualización
✅ Entrada en historial con status "Fallida"
✅ Error message guardado en historial
✅ Plugin queda en estado anterior (no corrupto)

**Cleanup:**
```bash
chmod 755 /path/to/myd-delivery-pro/
```

---

## 📊 Performance Tests

### Performance Test 1: Dashboard widget carga rápido

**Pasos:**
1. Abrir Dashboard con widget visible
2. Medir tiempo de carga

**Resultado esperado:**
✅ Widget carga en < 500ms
✅ No queries lentas en DB
✅ Usa cache apropiadamente

**Verificar con Query Monitor:**
- Queries relacionadas a updates
- Uso de transients
- No queries N+1

### Performance Test 2: Check de updates no bloquea

**Pasos:**
1. Forzar check de updates
2. Navegar rápidamente en admin

**Resultado esperado:**
✅ Check de updates no bloquea UI
✅ Otras páginas cargan normalmente
✅ Timeout configurado correctamente (10 segundos)

---

## ✅ Checklist Final

Antes de marcar issue como completo, verificar:

### Funcionalidad
- [ ] Dashboard widget funciona correctamente
- [ ] Email notifications se envían cuando están habilitadas
- [ ] Auto-update funciona cuando está habilitado
- [ ] Historial guarda todas las actualizaciones
- [ ] Settings page guarda configuración correctamente
- [ ] Menu badge aparece/desaparece apropiadamente

### UI/UX
- [ ] Todos los textos están traducibles
- [ ] Diseño es responsive
- [ ] Colores son consistentes con WordPress admin
- [ ] No hay elementos rotos visualmente
- [ ] Mensajes son claros y útiles

### Seguridad
- [ ] Capability checks en todas las acciones
- [ ] Nonces en todos los forms
- [ ] Input sanitization
- [ ] Output escaping
- [ ] No SQL injection posible

### Performance
- [ ] Cache implementado correctamente
- [ ] No queries lentas
- [ ] Transients usados apropiadamente
- [ ] No memory leaks

### Compatibilidad
- [ ] Funciona en WordPress 5.5+
- [ ] Funciona en PHP 7.4+
- [ ] No conflictos con otros plugins
- [ ] No errores en debug.log

### Documentación
- [ ] Código comentado apropiadamente
- [ ] README actualizado
- [ ] Documentación de usuario creada
- [ ] Documentación de developer creada

---

## 🆘 Troubleshooting Testing

### Dashboard widget no aparece

**Check:**
```bash
# Verificar usuario tiene permisos
wp user get admin --field=roles

# Verificar widget está registrado
wp eval "global $wp_meta_boxes; print_r($wp_meta_boxes['dashboard']);"
```

### Email no se recibe

**Check:**
```bash
# Test email básico de WordPress
wp eval "wp_mail('test@example.com', 'Test', 'Test');"

# Ver logs de PHP
tail -f /var/log/php-fpm.log | grep mail

# Verificar opción habilitada
wp option get myd_update_email_enabled
```

### Historial no se guarda

**Check:**
```bash
# Verificar hook está registrado
wp hook list upgrader_process_complete

# Ver opción directamente
wp option get myd_update_history --format=json

# Test manual
wp eval "
use MydPro\Includes\Plugin_Update\Update_History;
\$history = new Update_History();
\$history->add_entry(['version' => '9.9.9', 'success' => true, 'user_login' => 'test']);
"
```

---

**Última actualización:** 2025-11-10
**Versión:** 1.0.0

# Guía de Implementación - Plantillas HSM WhatsApp

## 📋 Resumen

Este sistema resuelve el problema de las notificaciones WhatsApp que dejan de funcionar después de 24 horas. Las plantillas HSM (Highly Structured Messages) permiten enviar mensajes a los técnicos sin importar cuándo fue la última conversación.

## 🎯 Problema Resuelto

- **Antes**: Los técnicos dejaban de recibir notificaciones WhatsApp después de 24 horas sin conversación
- **Después**: Los técnicos siempre reciben notificaciones usando plantillas pre-aprobadas por META

## 📁 Archivos Creados

1. **Plantillas JSON** (para enviar a META):
   - `nuevo_ticket.json` - Notificación de nuevo ticket sin cita
   - `nuevo_ticket_con_cita.json` - Notificación de nuevo ticket con cita programada
   - `reprogramacion_cita.json` - Notificación de reprogramación de cita
   - `bienvenida_tecnico.json` - Mensaje de bienvenida para nuevos técnicos

2. **Código modificado**:
   - `includes/WhatsAppNotifier.php` - Soporte para plantillas HSM
   - `config/whatsapp.php` - Nueva configuración
   - `admin/tickets.php` - Detección de reprogramaciones
   - `test_hsm_templates.php` - Script de pruebas

## 🚀 Pasos de Implementación

### Paso 1: Enviar Plantillas a META

1. Acceder al Business Manager de Facebook
2. Ir a WhatsApp Business API > Plantillas de Mensajes
3. Crear nuevas plantillas usando los archivos JSON de este directorio
4. Esperar aprobación de META (puede tomar 24-48 horas)

### Paso 2: Configurar el Sistema

Una vez que META apruebe las plantillas:

```php
// En config/whatsapp.php, cambiar:
'use_hsm_templates' => true
```

### Paso 3: Probar el Sistema

Ejecutar el script de pruebas:

```bash
php test_hsm_templates.php
```

## 🔧 Funcionamiento Técnico

### Detección Automática

El sistema detecta automáticamente cuándo usar cada plantilla:

- **nuevo_ticket_techonway**: Tickets nuevos sin cita programada
- **nuevo_ticket_con_cita_techonway**: Tickets nuevos con cita programada
- **reprogramacion_cita_techonway**: Cuando se cambia fecha/hora de una cita
- **bienvenida_tecnico_techonway**: Mensaje de bienvenida para nuevos técnicos

### Variables Dinámicas

Las plantillas usan estas variables del sistema:

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `{{1}}` | ID del ticket | "12345" |
| `{{2}}` | Nombre del cliente | "Juan Pérez" |
| `{{3}}` | Dirección del cliente | "Av. Corrientes 1234, CABA" |
| `{{4}}` | Descripción del ticket | "Problema con conexión" |
| `{{5}}` | Fecha de la cita | "HOY (27/03/2025)" |
| `{{6}}` | Hora de la cita | "14:30" |
| `{{7}}` | Código de seguridad | "ABC123" |

### Modo de Compatibilidad

- **HSM deshabilitado** (`use_hsm_templates = false`): Usa mensajes de texto simples (modo actual)
- **HSM habilitado** (`use_hsm_templates = true`): Usa plantillas HSM automáticamente

## 🔍 Monitoreo

### Logs de WhatsApp

Los logs se guardan en:
- `logs/whatsapp_YYYY-MM-DD.log`

### Verificación de Envíos

1. Revisar logs de WhatsApp para errores
2. Confirmar recepción con técnicos
3. Verificar métricas en Business Manager de META

## 🚨 Solución de Problemas

### Error: "Template not found"
- **Causa**: La plantilla no está aprobada por META
- **Solución**: Verificar estado en Business Manager

### Error: "Invalid parameter"
- **Causa**: Variables no coinciden con la plantilla
- **Solución**: Verificar formato de variables en el código

### Error: "Rate limit exceeded"
- **Causa**: Demasiados mensajes enviados muy rápido
- **Solución**: Implementar delay entre mensajes

## 📊 Beneficios

1. **Fiabilidad**: Los técnicos siempre reciben notificaciones
2. **Cumplimiento**: Respeta las políticas de WhatsApp Business
3. **Profesional**: Mensajes con formato consistente
4. **Escalabilidad**: Soporte para miles de notificaciones diarias

## 🔄 Migración Sin Interrupción

El sistema permite migración gradual:

1. **Fase 1**: Ejecutar con `use_hsm_templates = false` (actual)
2. **Fase 2**: Enviar plantillas a META para aprobación
3. **Fase 3**: Activar con `use_hsm_templates = true`
4. **Fase 4**: Monitorear y ajustar según sea necesario

## 📞 Soporte

Para problemas técnicos:
1. Revisar logs en `logs/whatsapp_*.log`
2. Ejecutar `test_hsm_templates.php` para diagnóstico
3. Verificar configuración en `config/whatsapp.php`

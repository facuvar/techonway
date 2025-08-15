# Configuración de Variables de Entorno en Railway

Este documento te guía para configurar las variables de entorno de SendGrid en Railway.

## 📧 Variables de SendGrid para Railway

Sigue estos pasos para configurar SendGrid en tu proyecto de Railway:

### 1. Accede a tu proyecto en Railway
1. Ve a [railway.app](https://railway.app)
2. Selecciona tu proyecto TechonWay
3. Ve a la pestaña "Variables"

### 2. Configura las variables de SendGrid

Agrega las siguientes variables de entorno:

```bash
# SendGrid Configuration
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
SENDGRID_FROM_EMAIL=no-reply@techonway.com
FROM_EMAIL=no-reply@techonway.com
FROM_NAME=TechonWay - Sistema de Gestión
REPLY_TO_EMAIL=info@techonway.com

# SMTP Configuration (uses SendGrid)
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USERNAME=apikey
SMTP_PASSWORD=$SENDGRID_API_KEY
SMTP_SECURE=tls
EMAIL_DEBUG=false
```

### 3. Obtener tu API Key de SendGrid

1. Ve a [SendGrid Dashboard](https://app.sendgrid.com/)
2. Navega a **Settings** → **API Keys**
3. Haz clic en **Create API Key**
4. Selecciona **Full Access** o configura permisos específicos para Mail Send
5. Copia la API Key generada
6. Úsala como valor para `SENDGRID_API_KEY`

### 4. Verificar el dominio del remitente

Para evitar que los emails vayan a spam:

1. En SendGrid, ve a **Settings** → **Sender Authentication**
2. Configura **Domain Authentication** o **Single Sender Verification**
3. Si usas un dominio personalizado como `@techonway.com`, verifica el dominio
4. Si usas un email personal, verifica el sender único

### 5. Variables adicionales recomendadas

```bash
# General Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demo.techonway.com

# Database (ya configurada anteriormente)
DB_HOST=tu_host_mysql_railway
DB_PORT=3306
DB_NAME=railway
DB_USER=root
DB_PASSWORD=tu_password_mysql_railway
```

## 🚀 Deploy y Testing

### 1. Deploy automático
Después de configurar las variables, Railway hará el deploy automáticamente.

### 2. Probar la configuración
1. Accede a tu sistema en https://demo.techonway.com
2. Crea una nueva cita programada
3. Verifica que el email de notificación se envíe correctamente

### 3. Verificar logs
En Railway, ve a la pestaña "Logs" para verificar si hay errores de email.

## 🔧 Script de configuración local

Para desarrollo local, puedes usar el script interactivo:

```bash
php setup_sendgrid.php
```

Este script te ayudará a configurar SendGrid tanto para desarrollo como para producción.

## 📊 Monitoreo de emails

### En SendGrid:
- **Activity Feed**: https://app.sendgrid.com/email_activity
- **Statistics**: https://app.sendgrid.com/stats
- **Suppressions**: https://app.sendgrid.com/suppressions

### En Railway:
- Revisa los logs de la aplicación en la pestaña "Logs"
- Los errores de email aparecerán con el prefijo `[EmailNotifier ERROR]`

## ⚠️ Troubleshooting

### Error: "The from address does not match a verified Sender Identity"
- **Solución**: Verifica el sender en SendGrid (Settings → Sender Authentication)

### Error: "Forbidden"
- **Solución**: Verifica que tu API Key tenga permisos de Mail Send

### Los emails van a spam
- **Solución**: Configura Domain Authentication en SendGrid
- **Alternativa**: Usa Single Sender Verification

### Error: "Host not found"
- **Solución**: Verifica que `SMTP_HOST=smtp.sendgrid.net`

## 📝 Notas importantes

1. **Nunca subas tu API Key a Git** - Úsala solo en variables de entorno
2. **Usa emails verificados** - SendGrid requiere verificación del remitente
3. **Monitorea tu cuota** - SendGrid tiene límites según tu plan
4. **Configura webhooks** si necesitas tracking avanzado de emails

## 🔗 Enlaces útiles

- [SendGrid Dashboard](https://app.sendgrid.com/)
- [Railway Dashboard](https://railway.app)
- [Documentación SendGrid PHP](https://docs.sendgrid.com/for-developers/sending-email/php)
- [API Keys SendGrid](https://app.sendgrid.com/settings/api_keys)

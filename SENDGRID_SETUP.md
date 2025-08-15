# ⚡ Configuración Rápida de SendGrid para TechonWay

## 🚀 Setup Express (5 minutos)

### 1. Obtener API Key de SendGrid
1. Ve a https://app.sendgrid.com/
2. **Settings** → **API Keys** → **Create API Key**
3. Selecciona **Full Access**
4. Copia la API Key generada

### 2. Configuración Automática

#### Para desarrollo local:
```bash
php setup_sendgrid.php
```

#### Para Railway (con Railway CLI):
```bash
# Windows PowerShell:
./railway-sendgrid-config.ps1

# Linux/Mac:
./railway-sendgrid-config.sh
```

### 3. Probar configuración
```bash
php test_sendgrid.php
```

## ⚙️ Configuración Manual Rápida

### Railway (Variables de entorno):
```bash
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxx
SENDGRID_FROM_EMAIL=no-reply@techonway.com
FROM_EMAIL=no-reply@techonway.com
FROM_NAME=TechonWay - Sistema de Gestión
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USERNAME=apikey
SMTP_PASSWORD=$SENDGRID_API_KEY
SMTP_SECURE=tls
```

### Local (`config/local.php`):
```php
<?php
return [
    'email' => [
        'smtp_host' => 'smtp.sendgrid.net',
        'smtp_port' => 587,
        'smtp_username' => 'apikey',
        'smtp_password' => 'TU_API_KEY_AQUI',
        'from_email' => 'no-reply@techonway.com',
        'from_name' => 'TechonWay'
    ]
];
```

## ✅ Verificación

1. **Dominio verificado**: Ve a SendGrid → Settings → Sender Authentication
2. **Email enviado**: Crea una cita en el sistema y verifica que llegue el email
3. **Logs**: Revisa Railway logs o archivo local de errores

## 🔗 Enlaces importantes

- **SendGrid Dashboard**: https://app.sendgrid.com/
- **Activity Feed**: https://app.sendgrid.com/email_activity
- **Tu aplicación**: https://demo.techonway.com
- **Railway Dashboard**: https://railway.app

## 🆘 Problemas comunes

| Error | Solución |
|-------|----------|
| "Sender not verified" | Verifica el email en SendGrid → Sender Authentication |
| "Forbidden" | Verifica que tu API Key tenga permisos Mail Send |
| "Host not found" | Asegúrate que SMTP_HOST=smtp.sendgrid.net |
| Emails van a spam | Configura Domain Authentication en SendGrid |

---

💡 **¿Necesitas ayuda?** Ejecuta `php test_sendgrid.php` para diagnóstico completo.

# Sistema de Gestión de Tickets para Ascensores - TechOnWay

## 📋 Descripción

Sistema web completo para la gestión de tickets de mantenimiento de ascensores, con roles diferenciados para administradores y técnicos.

## ✨ Características

- 🔐 **Sistema de autenticación** con roles (Admin/Técnico)
- 🎫 **Gestión completa de tickets** de mantenimiento
- 👥 **Administración de clientes** y técnicos
- 🌍 **Sistema multiidioma** (Español/Inglés)
- 📊 **Dashboard con estadísticas** en tiempo real
- 📧 **Notificaciones por email** y WhatsApp
- 📱 **Interfaz responsive** con Bootstrap
- 📥 **Importación/exportación** de datos Excel

## 🛠️ Tecnologías

- **Backend:** PHP 8.1+ con PDO
- **Frontend:** Bootstrap 5, JavaScript vanilla
- **Base de datos:** MySQL
- **Dependencias:** Composer (PHPSpreadsheet, PHPMailer)
- **Deploy:** Railway

## 🚀 Instalación Local

### Prerrequisitos
- PHP 8.1 o superior
- MySQL
- Composer
- XAMPP (recomendado para Windows)

### Pasos

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/facuvar/techonway.git
   cd techonway
   ```

2. **Instalar dependencias:**
   ```bash
   composer install
   ```

3. **Configurar base de datos:**
   - Crear base de datos MySQL llamada `techonway`
   - Importar las tablas desde `database/`
   - La configuración de DB para localhost ya está incluida

4. **Configurar XAMPP:**
   - Colocar el proyecto en `C:/xampp/htdocs/`
   - Iniciar Apache y MySQL
   - Acceder a `http://localhost/techonway/`

## 🌐 Deploy en Railway

### Variables de entorno requeridas:
- `DB_HOST` - Host de la base de datos
- `DB_NAME` - Nombre de la base de datos  
- `DB_USER` - Usuario de la base de datos
- `DB_PASSWORD` - Contraseña de la base de datos

### Pasos para deploy:

1. **Conectar Railway con GitHub:**
   - Ir a [Railway](https://railway.app)
   - Conectar este repositorio
   - Railway detectará automáticamente que es un proyecto PHP

2. **Configurar base de datos:**
   - Agregar servicio MySQL en Railway
   - Configurar las variables de entorno listadas arriba

3. **Deploy automático:**
   - Railway ejecutará `composer install` automáticamente
   - El servidor se iniciará en el puerto asignado

## 📁 Estructura del Proyecto

```
/
├── admin/              # Panel de administración
├── technician/         # Panel de técnicos
├── includes/           # Clases y funciones principales
├── templates/          # Plantillas HTML compartidas
├── config/             # Configuración de DB y APIs
├── lang/               # Archivos de traducción
├── assets/             # CSS, JS, imágenes
├── database/           # Scripts SQL
├── vendor/             # Dependencias de Composer
├── composer.json       # Configuración de dependencias
├── railway.toml        # Configuración Railway
└── nixpacks.toml       # Configuración del buildpack
```

## 👥 Usuarios por Defecto

**Administrador:**
- Usuario: `admin@techonway.com`
- Contraseña: `admin123`

**Técnico:**
- Usuario: `tecnico@techonway.com`  
- Contraseña: `tecnico123`

## 🔧 Configuración

### Base de datos
- La configuración se adapta automáticamente según el entorno
- Localhost: configuración básica de XAMPP
- Producción: variables de entorno de Railway

### APIs externas
- **SendGrid** para envío de emails
- **WhatsApp API** configurada en `config/whatsapp.php`

### Configuración de SendGrid
Para configurar el envío de emails con SendGrid:

#### 🚀 Configuración Automática
```bash
# Para desarrollo local:
php setup_sendgrid.php

# Para Railway (requiere Railway CLI):
./railway-sendgrid-config.sh  # Linux/Mac
./railway-sendgrid-config.ps1 # Windows PowerShell
```

#### ⚙️ Configuración Manual
1. **Obtener API Key de SendGrid:**
   - Ve a [SendGrid Dashboard](https://app.sendgrid.com/)
   - Settings → API Keys → Create API Key
   - Selecciona "Full Access" o permisos de "Mail Send"

2. **Variables de entorno (Railway):**
   ```bash
   SENDGRID_API_KEY=tu_api_key_aqui
   SENDGRID_FROM_EMAIL=no-reply@techonway.com
   FROM_EMAIL=no-reply@techonway.com
   FROM_NAME=TechonWay - Sistema de Gestión
   SMTP_HOST=smtp.sendgrid.net
   SMTP_PORT=587
   SMTP_USERNAME=apikey
   SMTP_PASSWORD=$SENDGRID_API_KEY
   ```

3. **Configuración local (`config/local.php`):**
   ```php
   return [
       'email' => [
           'smtp_host' => 'smtp.sendgrid.net',
           'smtp_port' => 587,
           'smtp_username' => 'apikey',
           'smtp_password' => 'tu_api_key_aqui',
           'from_email' => 'no-reply@techonway.com',
           'from_name' => 'TechonWay'
       ]
   ];
   ```

#### 🧪 Probar configuración
```bash
php test_sendgrid.php
```

#### 📚 Documentación adicional
- [Guía completa de Railway + SendGrid](./railway-env-setup.md)
- [SendGrid Dashboard](https://app.sendgrid.com/)
- [Verificación de dominio](https://app.sendgrid.com/settings/sender_auth)

## 📝 Funcionalidades Principales

### Para Administradores:
- ✅ Gestión completa de clientes y técnicos
- ✅ Asignación y seguimiento de tickets
- ✅ Reportes y estadísticas
- ✅ Configuración del sistema

### Para Técnicos:
- ✅ Ver tickets asignados
- ✅ Actualizar estado de trabajos
- ✅ Subir fotos y reportes
- ✅ Comunicación con clientes

## 🌍 Soporte Multiidioma

- Español (por defecto)
- Inglés
- Sistema fácilmente extensible para más idiomas

## 📞 Soporte

Para soporte técnico o consultas, contactar a través del sistema de tickets integrado.

---

Desarrollado con ❤️ para TechOnWay
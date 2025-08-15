#!/bin/bash

# Script para configurar SendGrid en Railway
# Este script configura automáticamente las variables de entorno necesarias

echo "================================================"
echo "🚀 CONFIGURACIÓN DE SENDGRID PARA RAILWAY      "
echo "   Sistema TechonWay                           "
echo "================================================"
echo ""

# Verificar si Railway CLI está instalado
if ! command -v railway &> /dev/null; then
    echo "❌ Railway CLI no está instalado."
    echo ""
    echo "Para instalar Railway CLI:"
    echo "npm install -g @railway/cli"
    echo "O visita: https://docs.railway.app/develop/cli"
    echo ""
    exit 1
fi

# Verificar si está logueado en Railway
if ! railway whoami &> /dev/null; then
    echo "❌ No estás logueado en Railway."
    echo "Ejecuta: railway login"
    echo ""
    exit 1
fi

echo "✅ Railway CLI detectado y configurado"
echo ""

# Pedir información de SendGrid
echo "📧 CONFIGURACIÓN DE SENDGRID"
echo "══════════════════════════════"
echo ""

read -p "SendGrid API Key: " -s SENDGRID_API_KEY
echo ""

if [ -z "$SENDGRID_API_KEY" ]; then
    echo "❌ La API Key es obligatoria"
    exit 1
fi

read -p "Email del remitente [no-reply@techonway.com]: " FROM_EMAIL
FROM_EMAIL=${FROM_EMAIL:-"no-reply@techonway.com"}

read -p "Nombre del remitente [TechonWay - Sistema de Gestión]: " FROM_NAME
FROM_NAME=${FROM_NAME:-"TechonWay - Sistema de Gestión"}

read -p "Email de respuesta [$FROM_EMAIL]: " REPLY_TO_EMAIL
REPLY_TO_EMAIL=${REPLY_TO_EMAIL:-$FROM_EMAIL}

echo ""
echo "🔧 CONFIGURANDO VARIABLES EN RAILWAY..."
echo ""

# Configurar variables de SendGrid
echo "Configurando SendGrid..."
railway variables set SENDGRID_API_KEY="$SENDGRID_API_KEY"
railway variables set SENDGRID_FROM_EMAIL="$FROM_EMAIL"
railway variables set FROM_EMAIL="$FROM_EMAIL"
railway variables set FROM_NAME="$FROM_NAME"
railway variables set REPLY_TO_EMAIL="$REPLY_TO_EMAIL"

echo "Configurando SMTP..."
railway variables set SMTP_HOST="smtp.sendgrid.net"
railway variables set SMTP_PORT="587"
railway variables set SMTP_USERNAME="apikey"
railway variables set SMTP_PASSWORD="$SENDGRID_API_KEY"
railway variables set SMTP_SECURE="tls"
railway variables set EMAIL_DEBUG="false"

echo ""
echo "✅ Variables de SendGrid configuradas en Railway"
echo ""

# Mostrar resumen
echo "📋 RESUMEN DE CONFIGURACIÓN"
echo "═══════════════════════════"
echo "• SMTP Host: smtp.sendgrid.net"
echo "• SMTP Port: 587"
echo "• From Email: $FROM_EMAIL"
echo "• From Name: $FROM_NAME"
echo "• Reply To: $REPLY_TO_EMAIL"
echo ""

echo "🚀 PRÓXIMOS PASOS"
echo "═══════════════════"
echo "1. Railway hará el redeploy automáticamente"
echo "2. Verifica en SendGrid que el dominio esté verificado"
echo "3. Prueba enviando una cita desde el sistema"
echo "4. Revisa los logs en Railway si hay problemas"
echo ""

echo "🔗 ENLACES ÚTILES"
echo "═══════════════════"
echo "• SendGrid Dashboard: https://app.sendgrid.com/"
echo "• Railway Dashboard: https://railway.app"
echo "• Tu aplicación: https://demo.techonway.com"
echo ""

echo "✅ ¡Configuración completada!"

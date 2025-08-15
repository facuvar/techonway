# Script PowerShell para configurar SendGrid en Railway
# Sistema TechonWay

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "🚀 CONFIGURACIÓN DE SENDGRID PARA RAILWAY      " -ForegroundColor Cyan
Write-Host "   Sistema TechonWay                           " -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Verificar si Railway CLI está instalado
try {
    $railwayVersion = railway --version 2>$null
    Write-Host "✅ Railway CLI detectado: $railwayVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Railway CLI no está instalado." -ForegroundColor Red
    Write-Host ""
    Write-Host "Para instalar Railway CLI:" -ForegroundColor Yellow
    Write-Host "npm install -g @railway/cli" -ForegroundColor White
    Write-Host "O visita: https://docs.railway.app/develop/cli" -ForegroundColor White
    Write-Host ""
    exit 1
}

# Verificar si está logueado en Railway
try {
    $railwayUser = railway whoami 2>$null
    if (-not $railwayUser) {
        throw "Not logged in"
    }
    Write-Host "✅ Logueado en Railway como: $railwayUser" -ForegroundColor Green
} catch {
    Write-Host "❌ No estás logueado en Railway." -ForegroundColor Red
    Write-Host "Ejecuta: railway login" -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

Write-Host ""

# Pedir información de SendGrid
Write-Host "📧 CONFIGURACIÓN DE SENDGRID" -ForegroundColor Yellow
Write-Host "══════════════════════════════" -ForegroundColor Yellow
Write-Host ""

# API Key (oculta)
$sendgridApiKey = Read-Host "SendGrid API Key" -AsSecureString
$sendgridApiKeyPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($sendgridApiKey))

if ([string]::IsNullOrEmpty($sendgridApiKeyPlain)) {
    Write-Host "❌ La API Key es obligatoria" -ForegroundColor Red
    exit 1
}

# Otros datos
$fromEmail = Read-Host "Email del remitente [no-reply@techonway.com]"
if ([string]::IsNullOrEmpty($fromEmail)) {
    $fromEmail = "no-reply@techonway.com"
}

$fromName = Read-Host "Nombre del remitente [TechonWay - Sistema de Gestión]"
if ([string]::IsNullOrEmpty($fromName)) {
    $fromName = "TechonWay - Sistema de Gestión"
}

$replyToEmail = Read-Host "Email de respuesta [$fromEmail]"
if ([string]::IsNullOrEmpty($replyToEmail)) {
    $replyToEmail = $fromEmail
}

Write-Host ""
Write-Host "🔧 CONFIGURANDO VARIABLES EN RAILWAY..." -ForegroundColor Yellow
Write-Host ""

try {
    # Configurar variables de SendGrid
    Write-Host "Configurando SendGrid..." -ForegroundColor White
    railway variables set "SENDGRID_API_KEY=$sendgridApiKeyPlain" | Out-Null
    railway variables set "SENDGRID_FROM_EMAIL=$fromEmail" | Out-Null
    railway variables set "FROM_EMAIL=$fromEmail" | Out-Null
    railway variables set "FROM_NAME=$fromName" | Out-Null
    railway variables set "REPLY_TO_EMAIL=$replyToEmail" | Out-Null

    Write-Host "Configurando SMTP..." -ForegroundColor White
    railway variables set "SMTP_HOST=smtp.sendgrid.net" | Out-Null
    railway variables set "SMTP_PORT=587" | Out-Null
    railway variables set "SMTP_USERNAME=apikey" | Out-Null
    railway variables set "SMTP_PASSWORD=$sendgridApiKeyPlain" | Out-Null
    railway variables set "SMTP_SECURE=tls" | Out-Null
    railway variables set "EMAIL_DEBUG=false" | Out-Null

    Write-Host ""
    Write-Host "✅ Variables de SendGrid configuradas en Railway" -ForegroundColor Green
    Write-Host ""

    # Mostrar resumen
    Write-Host "📋 RESUMEN DE CONFIGURACIÓN" -ForegroundColor Yellow
    Write-Host "═══════════════════════════" -ForegroundColor Yellow
    Write-Host "• SMTP Host: smtp.sendgrid.net"
    Write-Host "• SMTP Port: 587"
    Write-Host "• From Email: $fromEmail"
    Write-Host "• From Name: $fromName"
    Write-Host "• Reply To: $replyToEmail"
    Write-Host ""

    Write-Host "🚀 PRÓXIMOS PASOS" -ForegroundColor Yellow
    Write-Host "═══════════════════" -ForegroundColor Yellow
    Write-Host "1. Railway hará el redeploy automáticamente"
    Write-Host "2. Verifica en SendGrid que el dominio esté verificado"
    Write-Host "3. Prueba enviando una cita desde el sistema"
    Write-Host "4. Revisa los logs en Railway si hay problemas"
    Write-Host ""

    Write-Host "🔗 ENLACES ÚTILES" -ForegroundColor Yellow
    Write-Host "═══════════════════" -ForegroundColor Yellow
    Write-Host "• SendGrid Dashboard: https://app.sendgrid.com/"
    Write-Host "• Railway Dashboard: https://railway.app"
    Write-Host "• Tu aplicación: https://demo.techonway.com"
    Write-Host ""

    Write-Host "✅ ¡Configuración completada!" -ForegroundColor Green

} catch {
    Write-Host "❌ Error configurando variables: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Limpiar la variable con la API key de la memoria
$sendgridApiKeyPlain = $null

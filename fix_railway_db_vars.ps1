# Script para configurar variables de DB faltantes en Railway
Write-Host "🔧 Configurando variables de DB en Railway..." -ForegroundColor Yellow

# Configurar DB_USERNAME
Write-Host "📝 Configurando DB_USERNAME=root..." -ForegroundColor Green
railway variables --service techonway --set DB_USERNAME=root

# Verificar que DB_NAME tenga el valor correcto
Write-Host "📝 Verificando DB_NAME..." -ForegroundColor Green
railway variables --service techonway --set DB_NAME=railway

Write-Host "✅ Variables de DB configuradas!" -ForegroundColor Green
Write-Host "🔄 Ahora prueba: https://demo.techonway.com/check_db_vars.php" -ForegroundColor Cyan

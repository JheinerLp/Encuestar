# Script para iniciar SIRCE Cali (Encuestar) con XAMPP
$ErrorActionPreference = "Stop"

$projectDir = "C:\Users\dana-\Downloads\Encuestar-master"
$htdocsTarget = "C:\xampp\htdocs\Encuesta"
$mysqlExe = "C:\xampp\mysql\bin\mysql.exe"
$mysqldExe = "C:\xampp\mysql\bin\mysqld.exe"
$apacheExe = "C:\xampp\apache\bin\httpd.exe"

Write-Host "====================================================" -ForegroundColor Cyan
Write-Host "    INICIANDO SIRCE CALI - SISTEMA DE ENCUESTAS     " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan

# 1. Vincular el proyecto a C:\xampp\htdocs\Encuesta
if (-not (Test-Path $htdocsTarget)) {
    Write-Host "`n[1/5] Creando vínculo del proyecto en htdocs..." -ForegroundColor Yellow
    New-Item -ItemType Junction -Path $htdocsTarget -Target $projectDir | Out-Null
    Write-Host "      Enlace creado en $htdocsTarget" -ForegroundColor Green
} else {
    Write-Host "`n[1/5] El directorio $htdocsTarget ya existe." -ForegroundColor Green
}

# 2. Iniciar MySQL si no está en ejecución
$mysqlProc = Get-Process -Name mysqld -ErrorAction SilentlyContinue
if (-not $mysqlProc) {
    Write-Host "`n[2/5] Iniciando servicio MySQL (MariaDB)..." -ForegroundColor Yellow
    Start-Process -FilePath $mysqldExe -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini", "--standalone" -WindowStyle Hidden
    Start-Sleep -Seconds 3
    Write-Host "      MySQL iniciado correctamente." -ForegroundColor Green
} else {
    Write-Host "`n[2/5] MySQL ya se encuentra en ejecución." -ForegroundColor Green
}

# 3. Inicializar base de datos si no existe o falta
Write-Host "`n[3/5] Verificando base de datos 'encuesta_db'..." -ForegroundColor Yellow
$dbCheck = & $mysqlExe -u root -e "SHOW DATABASES LIKE 'encuesta_db';" 2>$null
if (-not ($dbCheck -match "encuesta_db")) {
    Write-Host "      Creando base de datos e importando esquema y datos iniciales..." -ForegroundColor Yellow
    & $mysqlExe -u root --default-character-set=utf8mb4 -e "source C:/Users/dana-/Downloads/Encuestar-master/database/schema.sql"
    & $mysqlExe -u root --default-character-set=utf8mb4 -e "source C:/Users/dana-/Downloads/Encuestar-master/database/seed.sql"
    Write-Host "      Base de datos inicializada con éxito." -ForegroundColor Green
} else {
    Write-Host "      Base de datos 'encuesta_db' ya existe y está lista." -ForegroundColor Green
}

# 4. Iniciar Apache si no está en ejecución
$apacheProc = Get-Process -Name httpd -ErrorAction SilentlyContinue
if (-not $apacheProc) {
    Write-Host "`n[4/5] Iniciando servidor web Apache..." -ForegroundColor Yellow
    Start-Process -FilePath $apacheExe -WindowStyle Hidden
    Start-Sleep -Seconds 2
    Write-Host "      Apache iniciado correctamente." -ForegroundColor Green
} else {
    Write-Host "`n[4/5] Apache ya se encuentra en ejecución." -ForegroundColor Green
}

# 5. Comprobar URL y abrir en el navegador
Write-Host "`n[5/5] Comprobando acceso a la aplicación..." -ForegroundColor Yellow
$appUrl = "http://localhost/Encuesta/"
try {
    $response = Invoke-WebRequest -Uri $appUrl -UseBasicParsing -TimeoutSec 5
    Write-Host "      Conexión exitosa (Código HTTP $($response.StatusCode))." -ForegroundColor Green
} catch {
    Write-Host "      Aviso al verificar conexión: $_" -ForegroundColor Yellow
}

Write-Host "`nAbriendo la aplicación en su navegador: $appUrl" -ForegroundColor Cyan
Start-Process $appUrl

Write-Host "`n¡Sistema listo! Puede iniciar sesión con cualquiera de los perfiles disponibles." -ForegroundColor Green


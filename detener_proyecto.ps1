# Script para detener los servicios de Apache y MySQL de XAMPP
Write-Host "Deteniendo servicios de Apache y MySQL..." -ForegroundColor Yellow

$apacheProc = Get-Process -Name httpd -ErrorAction SilentlyContinue
if ($apacheProc) {
    Stop-Process -Name httpd -Force
    Write-Host "Apache detenido." -ForegroundColor Green
} else {
    Write-Host "Apache no está en ejecución."
}

$mysqlProc = Get-Process -Name mysqld -ErrorAction SilentlyContinue
if ($mysqlProc) {
    Stop-Process -Name mysqld -Force
    Write-Host "MySQL detenido." -ForegroundColor Green
} else {
    Write-Host "MySQL no está en ejecución."
}

Write-Host "Todos los servicios han sido detenidos correctamente." -ForegroundColor Cyan


# Script para subir el proyecto a GitHub con GitFlow y Tags
param(
    [Parameter(Mandatory=$false)]
    [string]$RepoUrl
)

Write-Host "====================================================" -ForegroundColor Cyan
Write-Host "        SUBIR PROYECTO SIRCE CALI A GITHUB          " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan

if (-not $RepoUrl) {
    $RepoUrl = Read-Host "Ingrese la URL de su repositorio GitHub (ej: https://github.com/usuario/SIRCE-Cali.git)"
}

if (-not $RepoUrl -or $RepoUrl.Trim() -eq "") {
    Write-Host "Error: No se proporcionó ninguna URL de repositorio." -ForegroundColor Red
    exit 1
}

$RepoUrl = $RepoUrl.Trim()

Write-Host "`n[1/4] Configurando remote 'origin'..." -ForegroundColor Yellow
$currentRemote = git remote get-url origin 2>$null
if ($currentRemote) {
    git remote set-url origin $RepoUrl
} else {
    git remote add origin $RepoUrl
}
Write-Host "      Origen configurado: $RepoUrl" -ForegroundColor Green

Write-Host "`n[2/4] Subiendo rama 'main'..." -ForegroundColor Yellow
git push -u origin main

Write-Host "`n[3/4] Subiendo rama 'develop' (Estrategia GitFlow)..." -ForegroundColor Yellow
git push -u origin develop

Write-Host "`n[4/4] Subiendo etiquetas de versión (Tag v1.0.0)..." -ForegroundColor Yellow
git push --tags

Write-Host "`n====================================================" -ForegroundColor Green
Write-Host " ¡PROYECTO SUBIDO EXITOSAMENTE A GITHUB!            " -ForegroundColor Green
Write-Host "====================================================" -ForegroundColor Green

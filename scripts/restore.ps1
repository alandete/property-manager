<#
.SYNOPSIS
    Restaura la BD y (opcionalmente) el codigo desde un checkpoint.

.DESCRIPTION
    Uso:
        .\scripts\restore.ps1
        .\scripts\restore.ps1 -File "checkpoints\fase-1-ok-20260607-1430.sql"
        .\scripts\restore.ps1 -Tag "fase-1-ok"

    Sin parametros muestra la lista de checkpoints disponibles para seleccionar.
    Con -Tag hace checkout del tag git y busca el SQL mas reciente de ese nombre.
    ATENCION: borra y recrea la BD. Los datos actuales se pierden.

.PARAMETER File
    Ruta al archivo SQL. Si se omite, se muestra la lista interactiva.

.PARAMETER Tag
    Nombre del checkpoint (tag git). Restaura el codigo y busca el SQL correspondiente.

.PARAMETER OnlyDB
    Restaura solo la BD, sin tocar el codigo git.
#>
param(
    [string]$File   = "",
    [string]$Tag    = "",
    [switch]$OnlyDB = $false
)

. "$PSScriptRoot\env.ps1"

$mysql     = "$MYSQL_BIN\mysql.exe"
$mysqldump = "$MYSQL_BIN\mysqldump.exe"

# --- Verificar binarios ---
if (-not (Test-Path $mysql)) {
    Write-Host "ERROR: No se encontro mysql en: $mysql" -ForegroundColor Red
    exit 1
}

# --- Determinar el archivo SQL a restaurar ---
if ($Tag -and -not $File) {
    # Buscar el SQL mas reciente que coincida con el tag
    $matches = Get-ChildItem "$PROJECT_ROOT\checkpoints\$Tag-*.sql" -ErrorAction SilentlyContinue |
               Sort-Object LastWriteTime -Descending
    if ($matches) {
        $File = $matches[0].FullName
        Write-Host "Usando: $($matches[0].Name)" -ForegroundColor Cyan
    } else {
        Write-Host "No se encontro archivo SQL para el tag '$Tag' en checkpoints/" -ForegroundColor Yellow
        Write-Host "Puedes hacer checkout del tag manualmente y seleccionar el archivo." -ForegroundColor Yellow
    }
}

if (-not $File) {
    # Lista interactiva
    $sqlFiles = Get-ChildItem "$PROJECT_ROOT\checkpoints\*.sql" -ErrorAction SilentlyContinue |
                Sort-Object LastWriteTime -Descending
    if (-not $sqlFiles) {
        Write-Host "No hay checkpoints en checkpoints/" -ForegroundColor Yellow
        Write-Host "Crea uno primero con: .\scripts\backup.ps1" -ForegroundColor Cyan
        exit 0
    }

    Write-Host ""
    Write-Host "Checkpoints disponibles:" -ForegroundColor Cyan
    Write-Host ""
    $i = 1
    foreach ($f in $sqlFiles) {
        $sizeMB = [math]::Round($f.Length / 1MB, 2)
        $fecha  = $f.LastWriteTime.ToString("yyyy-MM-dd HH:mm")
        Write-Host ("  [{0,2}]  {1,-50}  {2} MB  ({3})" -f $i, $f.Name, $sizeMB, $fecha)
        $i++
    }
    Write-Host ""
    $sel = Read-Host "Numero del checkpoint a restaurar (Enter para cancelar)"
    if (-not $sel) { Write-Host "Cancelado." -ForegroundColor Gray; exit 0 }
    $idx = [int]$sel - 1
    if ($idx -lt 0 -or $idx -ge $sqlFiles.Count) {
        Write-Host "Numero invalido." -ForegroundColor Red; exit 1
    }
    $File = $sqlFiles[$idx].FullName
}

if (-not (Test-Path $File)) {
    Write-Host "Archivo no encontrado: $File" -ForegroundColor Red
    exit 1
}

$fileName = Split-Path $File -Leaf
$sizeMB   = [math]::Round((Get-Item $File).Length / 1MB, 2)

# --- Confirmacion ---
Write-Host ""
Write-Host "======================================================" -ForegroundColor Yellow
Write-Host " RESTAURACION DE CHECKPOINT" -ForegroundColor Yellow
Write-Host "======================================================" -ForegroundColor Yellow
Write-Host "  Archivo:  $fileName ($sizeMB MB)"
Write-Host "  Base de datos:  $DB_NAME en $DB_HOST"
Write-Host ""
Write-Host "ATENCION: Se borraran TODOS los datos actuales de '$DB_NAME'." -ForegroundColor Red
Write-Host ""
$confirm = Read-Host "Escribi 'si' para confirmar"
if ($confirm -ne 'si' -and $confirm -ne 'SI' -and $confirm -ne 'Si') {
    Write-Host "Cancelado." -ForegroundColor Gray
    exit 0
}

# --- Restaurar codigo git (si corresponde) ---
if (-not $OnlyDB -and $Tag) {
    Write-Host ""
    Write-Host "Restaurando codigo desde tag '$Tag'..." -ForegroundColor Cyan
    git -C $PROJECT_ROOT checkout $Tag
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ADVERTENCIA: No se pudo hacer checkout del tag '$Tag'." -ForegroundColor Yellow
        Write-Host "Continuando con la restauracion de la BD..." -ForegroundColor Yellow
    }
}

# --- Recrear la BD ---
Write-Host ""
Write-Host "Recreando base de datos '$DB_NAME'..." -ForegroundColor Cyan

$mysqlArgs = @("-h", $DB_HOST, "-P", $DB_PORT, "-u", $DB_USER)
if ($DB_PASS) { $mysqlArgs += "-p$DB_PASS" }

$dropCreate = "DROP DATABASE IF EXISTS ``$DB_NAME``; CREATE DATABASE ``$DB_NAME`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo $dropCreate | & $mysql @mysqlArgs

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR al recrear la base de datos." -ForegroundColor Red
    exit 1
}

# --- Importar dump ---
Write-Host "Importando datos..." -ForegroundColor Cyan
Get-Content $File -Raw | & $mysql @mysqlArgs $DB_NAME

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR al importar el dump." -ForegroundColor Red
    exit 1
}

# --- Limpiar cache de Drupal si drush esta disponible ---
$drush = "$PROJECT_ROOT\vendor\bin\drush"
if (Test-Path "$drush.bat") { $drush = "$drush.bat" }

Write-Host ""
Write-Host "======================================================" -ForegroundColor Green
Write-Host " Restauracion completada: $fileName" -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Green
Write-Host ""
if (Test-Path $drush) {
    Write-Host "Limpiando cache de Drupal..." -ForegroundColor Cyan
    & $drush -r "$PROJECT_ROOT\web" cr
} else {
    Write-Host "Recuerda limpiar la cache de Drupal cuando instales drush:" -ForegroundColor Yellow
    Write-Host "  vendor\bin\drush cr"
}

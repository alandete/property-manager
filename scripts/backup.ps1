<#
.SYNOPSIS
    Crea un checkpoint del estado actual: exporta la BD y hace commit+tag en git.

.DESCRIPTION
    Uso:
        .\scripts\backup.ps1
        .\scripts\backup.ps1 -Name "fase-1-ok"
        .\scripts\backup.ps1 -Name "antes-de-migrar" -Push

    Genera un archivo SQL en checkpoints/ con la fecha y el nombre indicado,
    hace commit de todos los cambios pendientes y crea un tag de git con ese nombre.

.PARAMETER Name
    Nombre del checkpoint. Si se omite, se solicita interactivamente.
    Debe ser un identificador simple: letras, números y guiones (ej: fase-1-ok).

.PARAMETER Push
    Si se indica, hace push al repositorio remoto (incluidos los tags).

.PARAMETER OnlyDB
    Si se indica, solo exporta la BD sin hacer commit ni tag de git.
#>
param(
    [string]$Name   = "",
    [switch]$Push   = $false,
    [switch]$OnlyDB = $false
)

. "$PSScriptRoot\env.ps1"

# --- Nombre del checkpoint ---
if (-not $Name) {
    $Name = Read-Host "Nombre del checkpoint (ej: fase-1-ok, antes-de-migrar)"
}
if (-not $Name) {
    Write-Host "Se requiere un nombre para el checkpoint." -ForegroundColor Red
    exit 1
}
$Name = $Name -replace '[^a-zA-Z0-9\-_]', '-'

$timestamp  = Get-Date -Format "yyyyMMdd-HHmm"
$sqlFile    = "$PROJECT_ROOT\checkpoints\$Name-$timestamp.sql"
$mysqldump  = "$MYSQL_BIN\mysqldump.exe"

# --- Verificar que mysqldump existe ---
if (-not (Test-Path $mysqldump)) {
    Write-Host "ERROR: No se encontro mysqldump en: $mysqldump" -ForegroundColor Red
    Write-Host "Revisa la ruta en scripts\env.ps1" -ForegroundColor Yellow
    exit 1
}

# --- Exportar BD ---
Write-Host ""
Write-Host "Exportando BD '$DB_NAME'..." -ForegroundColor Cyan

$mysqlArgs = @("-h", $DB_HOST, "-P", $DB_PORT, "-u", $DB_USER)
if ($DB_PASS) { $mysqlArgs += "-p$DB_PASS" }
$mysqlArgs += @("--single-transaction", "--routines", "--triggers", "--add-drop-table", $DB_NAME)

& $mysqldump @mysqlArgs | Out-File -FilePath $sqlFile -Encoding utf8

if ($LASTEXITCODE -ne 0 -or -not (Test-Path $sqlFile) -or (Get-Item $sqlFile).Length -lt 1000) {
    Write-Host "ERROR: La exportacion fallo o el archivo esta vacio." -ForegroundColor Red
    Remove-Item $sqlFile -ErrorAction SilentlyContinue
    exit 1
}

$sizeMB = [math]::Round((Get-Item $sqlFile).Length / 1MB, 2)
Write-Host "  -> checkpoints\$Name-$timestamp.sql ($sizeMB MB)" -ForegroundColor Green

if ($OnlyDB) {
    Write-Host ""
    Write-Host "Checkpoint de BD creado (sin commit git)." -ForegroundColor Green
    exit 0
}

# --- Git: commit + tag ---
Write-Host ""
Write-Host "Creando commit y tag git..." -ForegroundColor Cyan

git -C $PROJECT_ROOT add -A
$gitStatus = git -C $PROJECT_ROOT status --porcelain
if ($gitStatus) {
    git -C $PROJECT_ROOT commit -m "checkpoint: $Name ($timestamp)"
} else {
    Write-Host "  (Sin cambios de codigo pendientes, solo se crea el tag)" -ForegroundColor Gray
    # Commit solo el SQL
    git -C $PROJECT_ROOT add "checkpoints\$Name-$timestamp.sql"
    git -C $PROJECT_ROOT commit -m "checkpoint: $Name ($timestamp)" --allow-empty
}

# Eliminar tag previo con el mismo nombre si existe
$existingTag = git -C $PROJECT_ROOT tag -l $Name
if ($existingTag) {
    git -C $PROJECT_ROOT tag -d $Name | Out-Null
    Write-Host "  (Tag '$Name' anterior reemplazado)" -ForegroundColor Gray
}
git -C $PROJECT_ROOT tag $Name

# --- Push opcional ---
if ($Push) {
    Write-Host ""
    Write-Host "Subiendo al repositorio remoto..." -ForegroundColor Cyan
    git -C $PROJECT_ROOT push
    git -C $PROJECT_ROOT push origin $Name
}

# --- Resumen ---
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host " Checkpoint '$Name' creado correctamente" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "  BD:       checkpoints\$Name-$timestamp.sql ($sizeMB MB)"
Write-Host "  Git tag:  $Name"
Write-Host ""
if (-not $Push) {
    Write-Host "Para subir al repositorio:" -ForegroundColor Yellow
    Write-Host "  git push && git push origin $Name"
}

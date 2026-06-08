<#
.SYNOPSIS
    Menu interactivo de herramientas de desarrollo para Property Manager.
.DESCRIPTION
    Uso desde la raiz del proyecto:  .\dev.ps1
#>

. "$PSScriptRoot\env.ps1"

$DRUSH = "$PROJECT_ROOT\vendor\bin\drush.bat"
if (-not (Test-Path $DRUSH)) { $DRUSH = "$PROJECT_ROOT\vendor\bin\drush" }

# --------------------------------------------------------------------------
# Utilidades de pantalla
# --------------------------------------------------------------------------

function Write-Line {
    Write-Host ("  " + ("-" * 54)) -ForegroundColor DarkGray
}

function Write-Header {
    Clear-Host
    Write-Host ""
    Write-Host "  +------------------------------------------------------+" -ForegroundColor Cyan
    Write-Host "  |       Property Manager - Dev Tools                   |" -ForegroundColor Cyan
    Write-Host "  +------------------------------------------------------+" -ForegroundColor Cyan
    Write-Host ""

    $last = Get-ChildItem "$PROJECT_ROOT\checkpoints\*.sql" -ErrorAction SilentlyContinue |
            Sort-Object LastWriteTime -Descending | Select-Object -First 1

    Write-Host "  BD: " -NoNewline -ForegroundColor DarkGray
    Write-Host $DB_NAME -NoNewline -ForegroundColor White
    Write-Host "  |  $DB_HOST`:$DB_PORT  |  " -NoNewline -ForegroundColor DarkGray

    if ($last) {
        Write-Host "Ultimo checkpoint: " -NoNewline -ForegroundColor DarkGray
        Write-Host $last.BaseName -ForegroundColor Green
    } else {
        Write-Host "Sin checkpoints aun" -ForegroundColor DarkYellow
    }
    Write-Host ""
}

function Write-Section([string]$title) {
    Write-Host ""
    Write-Host "  -- $title" -ForegroundColor DarkGray
}

function Write-Option([string]$key, [string]$label, [string]$color = "White") {
    Write-Host "  " -NoNewline
    Write-Host ("[{0,2}]" -f $key) -NoNewline -ForegroundColor Yellow
    Write-Host "  $label" -ForegroundColor $color
}

function Pause-Menu {
    Write-Host ""
    Read-Host "  Presiona Enter para volver al menu"
}

# --------------------------------------------------------------------------
# Acciones - Base de datos
# --------------------------------------------------------------------------

function Action-CreateCheckpoint {
    Write-Header
    Write-Host "  CREAR CHECKPOINT" -ForegroundColor Cyan
    Write-Line
    Write-Host ""
    $name = Read-Host "  Nombre (ej: fase-1-ok, antes-de-migrar)"
    if (-not $name) { Write-Host "  Cancelado." -ForegroundColor Gray; return }

    $doPush = Read-Host "  Subir al repositorio remoto ahora? (s/N)"

    Write-Host ""
    if ($doPush -eq 's' -or $doPush -eq 'S') {
        & "$PSScriptRoot\backup.ps1" -Name $name -Push
    } else {
        & "$PSScriptRoot\backup.ps1" -Name $name
    }
}

function Action-RestoreCheckpoint {
    Write-Header
    Write-Host "  RESTAURAR CHECKPOINT" -ForegroundColor Yellow
    Write-Line
    Write-Host ""

    $files = Get-ChildItem "$PROJECT_ROOT\checkpoints\*.sql" -ErrorAction SilentlyContinue |
             Sort-Object LastWriteTime -Descending

    if (-not $files) {
        Write-Host "  No hay checkpoints disponibles." -ForegroundColor Yellow
        Write-Host "  Crea uno primero con la opcion [1]." -ForegroundColor Gray
        return
    }

    Write-Host "  Checkpoints disponibles:" -ForegroundColor Cyan
    Write-Host ""
    $i = 1
    foreach ($f in $files) {
        $sizeMB = [math]::Round($f.Length / 1MB, 2)
        $fecha  = $f.LastWriteTime.ToString("yyyy-MM-dd HH:mm")
        Write-Host ("  [{0,2}]  {1,-44}  {2,5} MB  {3}" -f $i, $f.BaseName, $sizeMB, $fecha)
        $i++
    }
    Write-Host ""
    $sel = Read-Host "  Numero a restaurar (Enter = cancelar)"
    if (-not $sel) { Write-Host "  Cancelado." -ForegroundColor Gray; return }

    $idx = [int]$sel - 1
    if ($idx -lt 0 -or $idx -ge $files.Count) {
        Write-Host "  Numero invalido." -ForegroundColor Red; return
    }

    & "$PSScriptRoot\restore.ps1" -File $files[$idx].FullName
}

function Action-ListCheckpoints {
    Write-Header
    Write-Host "  CHECKPOINTS DISPONIBLES" -ForegroundColor Cyan
    Write-Line
    Write-Host ""

    $files = Get-ChildItem "$PROJECT_ROOT\checkpoints\*.sql" -ErrorAction SilentlyContinue |
             Sort-Object LastWriteTime -Descending

    if (-not $files) {
        Write-Host "  No hay checkpoints aun." -ForegroundColor Yellow
        return
    }

    $total = 0
    foreach ($f in $files) {
        $sizeMB = [math]::Round($f.Length / 1MB, 2)
        $fecha  = $f.LastWriteTime.ToString("yyyy-MM-dd HH:mm")
        $total += $f.Length
        Write-Host ("  {0,-50}  {1,6} MB  {2}" -f $f.BaseName, $sizeMB, $fecha)
    }
    Write-Host ""
    $totalMB = [math]::Round($total / 1MB, 2)
    Write-Host "  $($files.Count) checkpoint(s)  |  Total: $totalMB MB" -ForegroundColor DarkGray
}

# --------------------------------------------------------------------------
# Acciones - Drupal / Drush
# --------------------------------------------------------------------------

function Invoke-Drush([string[]]$drushArgs, [string]$label = "") {
    if (-not (Test-Path $DRUSH)) {
        Write-Host "  drush no esta instalado aun." -ForegroundColor Yellow
        Write-Host "  Ejecuta: composer require drush/drush" -ForegroundColor Cyan
        return
    }
    if ($label) { Write-Host "  $label" -ForegroundColor Cyan; Write-Host "" }
    & $DRUSH -r "$PROJECT_ROOT\web" @drushArgs
}

function Action-DrushCR {
    Write-Header
    Write-Host "  LIMPIAR CACHE DE DRUPAL" -ForegroundColor Cyan
    Write-Line
    Write-Host ""
    Invoke-Drush @("cr") "drush cr"
}

function Action-ModuleStatus {
    Write-Header
    Write-Host "  ESTADO DEL MODULO property_manager" -ForegroundColor Cyan
    Write-Line
    Write-Host ""
    Invoke-Drush @("pm:list", "--filter=property_manager", "--format=table") "drush pm:list --filter=property_manager"
}

function Action-EnableModule {
    Write-Header
    Write-Host "  INSTALAR MODULO property_manager" -ForegroundColor Cyan
    Write-Line
    Write-Host ""
    Invoke-Drush @("en", "property_manager", "-y") "drush en property_manager"
}

function Action-UninstallModule {
    Write-Header
    Write-Host "  DESINSTALAR MODULO property_manager" -ForegroundColor Red
    Write-Line
    Write-Host ""
    Write-Host "  ATENCION: Esto elimina el modulo y puede borrar datos." -ForegroundColor Red
    $confirm = Read-Host "  Escribi 'si' para confirmar"
    if ($confirm -ne 'si') { Write-Host "  Cancelado." -ForegroundColor Gray; return }
    Invoke-Drush @("pm:uninstall", "property_manager", "-y") "drush pm:uninstall property_manager"
}

function Action-DrushCommand {
    Write-Header
    Write-Host "  EJECUTAR COMANDO DRUSH" -ForegroundColor Cyan
    Write-Line
    Write-Host ""
    $cmd = Read-Host "  Comando (sin 'drush', ej: cr / en views / sql-dump)"
    if (-not $cmd) { return }
    $parts = $cmd -split '\s+'
    Invoke-Drush $parts "drush $cmd"
}

# --------------------------------------------------------------------------
# Acciones - Git
# --------------------------------------------------------------------------

function Action-GitStatus {
    Write-Header
    Write-Host "  ESTADO GIT" -ForegroundColor Cyan
    Write-Line
    Write-Host ""
    git -C $PROJECT_ROOT status
    Write-Host ""
    Write-Host "  Tags (checkpoints):" -ForegroundColor DarkGray
    git -C $PROJECT_ROOT tag --sort=-creatordate | Select-Object -First 10 | ForEach-Object {
        Write-Host "    $_" -ForegroundColor Green
    }
}

function Action-GitLog {
    Write-Header
    Write-Host "  HISTORIAL DE COMMITS" -ForegroundColor Cyan
    Write-Line
    Write-Host ""
    git -C $PROJECT_ROOT log --oneline --decorate -20
}

function Action-GitPush {
    Write-Header
    Write-Host "  PUSH AL REPOSITORIO" -ForegroundColor Cyan
    Write-Line
    Write-Host ""
    git -C $PROJECT_ROOT push
    Write-Host ""
    git -C $PROJECT_ROOT push --tags
}

# --------------------------------------------------------------------------
# Menu principal
# --------------------------------------------------------------------------

$running = $true
while ($running) {
    Write-Header

    Write-Section "Base de datos"
    Write-Option "1"  "Crear checkpoint        (backup BD + git commit + tag)"
    Write-Option "2"  "Restaurar checkpoint    (seleccionar de la lista)"
    Write-Option "3"  "Ver checkpoints         (lista con fechas y tamanos)"

    Write-Section "Drupal"
    Write-Option "4"  "Limpiar cache           (drush cr)"
    Write-Option "5"  "Estado del modulo       (drush pm:list)"
    Write-Option "6"  "Instalar modulo         (drush en property_manager)"
    Write-Option "7"  "Desinstalar modulo      (drush pm:uninstall)"
    Write-Option "8"  "Ejecutar comando drush  (acceso directo)"

    Write-Section "Git"
    Write-Option "9"  "Estado git              (git status + tags)"
    Write-Option "10" "Historial               (ultimos 20 commits)"
    Write-Option "11" "Push al repositorio     (git push + tags)"

    Write-Host ""
    Write-Line
    Write-Option "0"  "Salir" "DarkGray"
    Write-Host ""

    $option = Read-Host "  Opcion"

    switch ($option) {
        "1"  { Action-CreateCheckpoint;  Pause-Menu }
        "2"  { Action-RestoreCheckpoint; Pause-Menu }
        "3"  { Action-ListCheckpoints;   Pause-Menu }
        "4"  { Action-DrushCR;           Pause-Menu }
        "5"  { Action-ModuleStatus;      Pause-Menu }
        "6"  { Action-EnableModule;      Pause-Menu }
        "7"  { Action-UninstallModule;   Pause-Menu }
        "8"  { Action-DrushCommand;      Pause-Menu }
        "9"  { Action-GitStatus;         Pause-Menu }
        "10" { Action-GitLog;            Pause-Menu }
        "11" { Action-GitPush;           Pause-Menu }
        "0"  { $running = $false }
        default {
            Write-Host "  Opcion invalida." -ForegroundColor Red
            Start-Sleep -Milliseconds 700
        }
    }
}

Write-Host ""
Write-Host "  Hasta luego." -ForegroundColor DarkGray
Write-Host ""

<#
.SYNOPSIS
Ejecuta Pest con SQLite en memoria y genera evidencias sin modificar la instancia.
.PARAMETER Scope
plan: cinco casos de la matriz; incidents: regresión de incidencias; full: suite completa.
.PARAMETER PhpPath
Ejecutable PHP opcional. Si se omite, se busca php o php.exe en PATH.
.PARAMETER EvidenceDirectory
Carpeta base opcional para evidencias. Cada ejecución crea un subdirectorio qa-UUID.
Sin este parámetro, se utiliza storage/framework/testing/qa-UUID/evidencias.
.EXAMPLE
./docs/qa/ejecutar-pest-aislado.ps1 -Scope plan
.NOTES
No ejecuta composer test ni limpia cachés compartidas. Conserva las evidencias y
restaura las variables de entorno del proceso al terminar, incluso ante errores.
#>
[CmdletBinding()]
param(
    [ValidateSet('plan', 'full', 'incidents')]
    [string]$Scope = 'full',
    [string]$PhpPath,
    [string]$EvidenceDirectory
)

$ErrorActionPreference = 'Stop'
$repoRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '../..')).Path
$runId = 'qa-' + [Guid]::NewGuid().ToString('N')
$runDirectory = Join-Path $repoRoot "storage/framework/testing/$runId"
$runtime = Join-Path $runDirectory 'runtime'
# Laravel reconoce prefijos / y \\, pero no C: en sus rutas de caché.
# Las rutas relativas al repositorio funcionan igual en Windows y Linux.
$runtimeRelative = "storage/framework/testing/$runId/runtime"
$evidence = if ([string]::IsNullOrWhiteSpace($EvidenceDirectory)) {
    Join-Path $runDirectory 'evidencias'
} else {
    Join-Path ([IO.Path]::GetFullPath($EvidenceDirectory)) $runId
}

if ([string]::IsNullOrWhiteSpace($PhpPath)) {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($null -eq $phpCommand) {
        $phpCommand = Get-Command php.exe -ErrorAction SilentlyContinue | Select-Object -First 1
    }
    if ($null -eq $phpCommand) {
        throw 'No se encontró PHP. Indica su ejecutable con -PhpPath.'
    }
    $PhpPath = $phpCommand.Source
}

if (-not (Test-Path -LiteralPath (Join-Path $repoRoot 'vendor/bin/pest'))) {
    throw 'No se encontró Pest instalado en este repositorio. No se ejecutó ninguna prueba.'
}

# Se reutiliza la configuración local de Windows si existe; nunca se modifica.
$configuration = 'phpunit.xml'
if ($env:OS -eq 'Windows_NT' -and (Test-Path -LiteralPath (Join-Path $repoRoot 'phpunit.windows.xml'))) {
    $configuration = 'phpunit.windows.xml'
}
New-Item -ItemType Directory -Force -Path $evidence, (Join-Path $runtime 'views'), (Join-Path $runtime 'cache') | Out-Null
# Se reutilizan únicamente manifiestos de proveedores, nunca configuración privada.
foreach ($manifest in @('packages.php', 'services.php')) {
    $sourceManifest = Join-Path $repoRoot "bootstrap/cache/$manifest"
    if (Test-Path -LiteralPath $sourceManifest) {
        Copy-Item -LiteralPath $sourceManifest -Destination (Join-Path $runtime $manifest)
    }
}

function Protect-QaOutput([string]$Line) {
    # Defensa adicional: las evidencias nunca necesitan claves, contraseñas ni tokens.
    $clean = $Line -replace '(?i)\bBearer\s+[^\s"''<>]+', 'Bearer [REDACTADO]'
    $clean = $clean -replace '\bsk-[A-Za-z0-9_-]+', '[CLAVE_REDACTADA]'
    $clean = $clean -replace '(?i)((?:APP_KEY|OPENAI_API_KEY|password|remember_token|authorization)["'']?\s*[:=]\s*["'']?)[^\s,"''<>]+', '$1[REDACTADO]'
    $clean = $clean -replace '(?i)(https?://)[^\s/@:]+:[^\s/@]+@', '$1[CREDENCIAL_REDACTADA]@'
    $clean = $clean -replace '\b\d+\|[A-Za-z0-9]{40,}\b', '[TOKEN_REDACTADO]'
    return $clean
}

# Los literales (null)/(empty) impiden que Dotenv vuelva a cargar valores del .env.
$isolatedEnvironment = [ordered]@{
    APP_ENV = 'testing'
    APP_DEBUG = 'false'
    APP_KEY = 'base64:UVFRUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVE='
    APP_BOOTSTRAP_PATH = $null
    APP_CONFIG_CACHE = "$runtimeRelative/unused-config.php"
    APP_SERVICES_CACHE = "$runtimeRelative/services.php"
    APP_PACKAGES_CACHE = "$runtimeRelative/packages.php"
    APP_ROUTES_CACHE = "$runtimeRelative/routes.php"
    APP_EVENTS_CACHE = "$runtimeRelative/events.php"
    VIEW_COMPILED_PATH = (Join-Path $runtime 'views')
    LOG_CHANNEL = 'null'
    DB_CONNECTION = 'sqlite'
    DB_DATABASE = ':memory:'
    DB_URL = '(null)'
    CACHE_STORE = 'array'
    SESSION_DRIVER = 'array'
    QUEUE_CONNECTION = 'sync'
    MAIL_MAILER = 'array'
    OPENAI_ENABLED = 'false'
    OPENAI_API_KEY = '(empty)'
    OPENAI_MODEL = '(empty)'
    BCRYPT_ROUNDS = '4'
}
$originalEnvironment = @{}
foreach ($name in $isolatedEnvironment.Keys) {
    $originalEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
}

$locationChanged = $false
$testExit = 10
$started = [DateTime]::UtcNow
$pestArguments = @(
    'vendor/bin/pest', '--configuration', $configuration,
    '--no-coverage', '--colors=never', "--cache-directory=$(Join-Path $runtime 'cache')",
    '--log-junit', (Join-Path $evidence "$Scope-junit.xml")
)
if ($Scope -eq 'plan') { $pestArguments += 'tests/Feature/Api/CameraExecutionPlanTest.php' }
if ($Scope -eq 'incidents') { $pestArguments += 'tests/Feature/Incidents' }

try {
    foreach ($name in $isolatedEnvironment.Keys) {
        [Environment]::SetEnvironmentVariable($name, $isolatedEnvironment[$name], 'Process')
    }
    Push-Location $repoRoot
    $locationChanged = $true

    # El preflight se ejecuta antes de que RefreshDatabase pueda aplicar migraciones.
    & $PhpPath (Join-Path $PSScriptRoot 'preflight.php') $repoRoot 2>&1 |
        ForEach-Object { Protect-QaOutput ([string]$_) } |
        Tee-Object -FilePath (Join-Path $evidence "$Scope-preflight.json")
    if ($LASTEXITCODE -ne 0) {
        throw 'Se detiene la ejecución: aislamiento no verificado. Revisa la evidencia del preflight.'
    }

    & $PhpPath @pestArguments 2>&1 |
        ForEach-Object { Protect-QaOutput ([string]$_) } |
        Tee-Object -FilePath (Join-Path $evidence "$Scope-stdout.txt")
    $testExit = $LASTEXITCODE
} finally {
    # No se registran los valores de entorno originales: pueden contener secretos.
    foreach ($name in $originalEnvironment.Keys) {
        [Environment]::SetEnvironmentVariable($name, $originalEnvironment[$name], 'Process')
    }
    if ($locationChanged) { Pop-Location }
    # También se depura JUnit por si una futura prueba fallida incluye datos de autenticación.
    $junitPath = Join-Path $evidence "$Scope-junit.xml"
    if (Test-Path -LiteralPath $junitPath) {
        $junitText = Get-Content -LiteralPath $junitPath -Raw
        Protect-QaOutput $junitText | Set-Content -LiteralPath $junitPath -Encoding utf8
    }
    [ordered]@{
        scope = $Scope
        startedUtc = $started.ToString('o')
        finishedUtc = [DateTime]::UtcNow.ToString('o')
        workingDirectory = $repoRoot
        executable = $PhpPath
        arguments = $pestArguments
        exitCode = $testExit
        coverage = 'No medida (--no-coverage).'
        database = 'sqlite :memory:'
        openai = 'Deshabilitada; dobles explícitos en pruebas de integración.'
    } | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath (Join-Path $evidence "$Scope-command.json") -Encoding utf8
    Write-Host "Evidencias de esta ejecución: $evidence"
}

exit $testExit

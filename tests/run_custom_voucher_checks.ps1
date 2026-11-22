$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $projectRoot

$previousDatabaseEnvironment = @{}
foreach ($name in @('DB_CONNECTION', 'DB_DATABASE', 'DB_URL')) {
    $previousDatabaseEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
}

try {
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = ':memory:'
    $env:DB_URL = ''

    php artisan test --compact --filter='CustomOrderFlowTest|VoucherRedemptionFlowTest'
    $testExitCode = $LASTEXITCODE
}
finally {
    foreach ($name in $previousDatabaseEnvironment.Keys) {
        $value = $previousDatabaseEnvironment[$name]
        if ($null -eq $value) {
            Remove-Item "Env:$name" -ErrorAction SilentlyContinue
        } else {
            [Environment]::SetEnvironmentVariable($name, $value, 'Process')
        }
    }
}

if ($testExitCode -ne 0) {
    exit $testExitCode
}

$previousConcurrencyFlag = [Environment]::GetEnvironmentVariable('RUN_VOUCHER_CONCURRENCY_TEST', 'Process')

try {
    $env:RUN_VOUCHER_CONCURRENCY_TEST = '1'
    php tests\manual\voucher_redemption_concurrency.php
    $concurrencyExitCode = $LASTEXITCODE
}
finally {
    if ($null -eq $previousConcurrencyFlag) {
        Remove-Item Env:RUN_VOUCHER_CONCURRENCY_TEST -ErrorAction SilentlyContinue
    } else {
        $env:RUN_VOUCHER_CONCURRENCY_TEST = $previousConcurrencyFlag
    }
}

exit $concurrencyExitCode

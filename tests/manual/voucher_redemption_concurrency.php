<?php

declare(strict_types=1);

use App\Models\Fruit;
use App\Models\Order;
use App\Models\Voucher;
use App\Services\VoucherRedemptionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

$projectRoot = dirname(__DIR__, 2);

require $projectRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$app = require $projectRoot.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
$app->make(Kernel::class)->bootstrap();

if (($argv[1] ?? null) === 'worker') {
    runWorker($argv);
    exit(0);
}

if (getenv('RUN_VOUCHER_CONCURRENCY_TEST') !== '1') {
    fwrite(STDERR, "Jalankan melalui tests\\run_custom_voucher_checks.ps1 atau set RUN_VOUCHER_CONCURRENCY_TEST=1.\n");
    exit(2);
}

if (! in_array(app()->environment(), ['local', 'testing'], true)) {
    fwrite(STDERR, "Uji concurrency hanya boleh dijalankan pada environment local atau testing.\n");
    exit(2);
}

if (! in_array(config('database.default'), ['mysql', 'mariadb'], true)) {
    fwrite(STDERR, "Uji concurrency nyata membutuhkan MySQL/MariaDB Laragon. Atur DB_CONNECTION=mysql pada .env dan jalankan ulang.\n");
    exit(2);
}

if (! function_exists('proc_open')) {
    fwrite(STDERR, "PHP proc_open tidak tersedia; proses worker serentak tidak dapat dijalankan.\n");
    exit(2);
}

try {
    runConcurrencyCheck();
} catch (Throwable $exception) {
    fwrite(STDERR, 'Uji concurrency gagal: '.$exception->getMessage()."\n");
    exit(1);
}

function runWorker(array $arguments): void
{
    $voucherId = (int) ($arguments[2] ?? 0);
    $fruitId = (int) ($arguments[3] ?? 0);
    $gatePath = $arguments[4] ?? '';
    $readyPath = $arguments[5] ?? '';
    $resultPath = $arguments[6] ?? '';
    $runId = $arguments[7] ?? '';

    if ($voucherId < 1 || $fruitId < 1 || $gatePath === '' || $readyPath === '' || $resultPath === '') {
        fwrite(STDERR, "Argumen worker uji concurrency tidak lengkap.\n");
        exit(2);
    }

    file_put_contents($readyPath, 'ready', LOCK_EX);
    $deadline = microtime(true) + 45;

    while (! is_file($gatePath)) {
        if (microtime(true) >= $deadline) {
            file_put_contents($resultPath, json_encode([
                'success' => false,
                'error' => 'Batas waktu menunggu sinyal mulai terlampaui.',
            ], JSON_THROW_ON_ERROR), LOCK_EX);
            exit(1);
        }

        usleep(10000);
    }

    try {
        $order = app(VoucherRedemptionService::class)->redeem($voucherId, [
            'customer_name' => 'Penerima Uji '.$runId,
            'phone' => '08123456789',
            'fulfillment_method' => 'ambil',
            'buah' => [$fruitId],
        ]);

        $result = [
            'success' => true,
            'order_code' => $order->order_code,
        ];
    } catch (ValidationException $exception) {
        $result = [
            'success' => false,
            'validation_rejected' => true,
            'message' => $exception->getMessage(),
        ];
    } catch (Throwable $exception) {
        $result = [
            'success' => false,
            'error' => $exception::class.': '.$exception->getMessage(),
        ];

        file_put_contents($resultPath, json_encode($result, JSON_THROW_ON_ERROR), LOCK_EX);
        exit(1);
    }

    file_put_contents($resultPath, json_encode($result, JSON_THROW_ON_ERROR), LOCK_EX);
}

function runConcurrencyCheck(): void
{
    $runId = Str::upper(Str::random(8));
    $tempDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'voucher-concurrency-'.$runId);

    if (! mkdir($tempDirectory, 0777, true) && ! is_dir($tempDirectory)) {
        throw new RuntimeException('Folder sementara uji concurrency gagal dibuat.');
    }

    $gatePath = $tempDirectory.DIRECTORY_SEPARATOR.'start';
    $fruitId = null;
    $voucherId = null;
    $processes = [];
    $workerFiles = [];

    try {
        $fruit = Fruit::query()->create([
            'nama_buah' => 'Buah Uji Serentak '.$runId,
            'harga_porsi' => 1000,
            'stok_porsi' => 1,
            'aktif' => true,
        ]);
        $fruitId = $fruit->id;

        $voucher = Voucher::query()->create([
            'nama_paket' => 'Paket Uji Serentak',
            'harga' => 1000,
            'jumlah_batok' => 1,
            'maks_buah' => 1,
            'nama_pengirim' => 'Pengirim Uji',
            'nomor_hp_pengirim' => '08123456789',
            'kode_voucher' => generateTestVoucherCode(),
            'status' => Voucher::STATUS_ACTIVE,
            'berlaku_sampai' => now()->addDay(),
        ]);
        $voucherId = $voucher->id;

        for ($worker = 1; $worker <= 2; $worker++) {
            $readyPath = $tempDirectory.DIRECTORY_SEPARATOR.'worker-'.$worker.'.ready';
            $resultPath = $tempDirectory.DIRECTORY_SEPARATOR.'worker-'.$worker.'.json';
            $outputPath = $tempDirectory.DIRECTORY_SEPARATOR.'worker-'.$worker.'.out';
            $errorPath = $tempDirectory.DIRECTORY_SEPARATOR.'worker-'.$worker.'.err';
            $workerFiles = array_merge($workerFiles, [$readyPath, $resultPath, $outputPath, $errorPath]);

            $process = proc_open([
                PHP_BINARY,
                __FILE__,
                'worker',
                (string) $voucherId,
                (string) $fruitId,
                $gatePath,
                $readyPath,
                $resultPath,
                $runId,
            ], [
                0 => ['pipe', 'r'],
                1 => ['file', $outputPath, 'w'],
                2 => ['file', $errorPath, 'w'],
            ], $pipes, dirname(__DIR__, 2));

            if (! is_resource($process)) {
                throw new RuntimeException('Proses worker uji concurrency gagal dijalankan.');
            }

            fclose($pipes[0]);
            $processes[] = $process;
        }

        $deadline = microtime(true) + 45;

        while (! is_file($workerFiles[0]) || ! is_file($workerFiles[4])) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('Worker tidak siap dalam batas waktu yang ditentukan.');
            }

            usleep(10000);
        }

        file_put_contents($gatePath, 'start', LOCK_EX);

        $resultDeadline = microtime(true) + 90;

        while (! is_file($workerFiles[1]) || ! is_file($workerFiles[5])) {
            if (microtime(true) >= $resultDeadline) {
                throw new RuntimeException('Worker tidak selesai dalam batas waktu yang ditentukan.');
            }

            usleep(10000);
        }

        $exitCodes = [];

        foreach ($processes as $index => $process) {
            $exitCodes[] = proc_close($process);
            $processes[$index] = null;
        }

        if (in_array(false, $exitCodes, true) || array_filter($exitCodes, fn (int $code): bool => $code !== 0) !== []) {
            $errors = array_map(
                fn (string $path): string => is_file($path) ? (string) file_get_contents($path) : '',
                [$workerFiles[1], $workerFiles[3], $workerFiles[5], $workerFiles[7]],
            );
            throw new RuntimeException('Worker gagal: '.implode(' ', array_filter($errors)));
        }

        $results = [
            json_decode((string) file_get_contents($workerFiles[1]), true, flags: JSON_THROW_ON_ERROR),
            json_decode((string) file_get_contents($workerFiles[5]), true, flags: JSON_THROW_ON_ERROR),
        ];
        $successfulRedemptions = count(array_filter($results, fn (array $result): bool => $result['success'] ?? false));
        $rejectedRedemptions = count(array_filter($results, fn (array $result): bool => $result['validation_rejected'] ?? false));
        $voucher = Voucher::query()->findOrFail($voucherId);
        $orderCount = Order::query()->where('id_voucher', $voucherId)->count();
        $remainingStock = Fruit::query()->findOrFail($fruitId)->stok_porsi;

        if ($successfulRedemptions !== 1
            || $rejectedRedemptions !== 1
            || $voucher->status !== Voucher::STATUS_USED
            || $orderCount !== 1
            || $remainingStock !== 0) {
            throw new RuntimeException('Hasil tidak sesuai: '.json_encode([
                'hasil_worker' => $results,
                'status_voucher' => $voucher->status,
                'jumlah_pesanan' => $orderCount,
                'stok_tersisa' => $remainingStock,
            ], JSON_THROW_ON_ERROR));
        }

        echo "Lulus: 1 penukaran berhasil, 1 ditolak, 1 pesanan dibuat, stok akhir 0.\n";
    } finally {
        foreach ($processes as $process) {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
        }

        if ($voucherId !== null || $fruitId !== null) {
            DB::transaction(function () use ($fruitId, $voucherId): void {
                if ($voucherId !== null) {
                    Order::query()->where('id_voucher', $voucherId)->delete();
                    Voucher::query()->whereKey($voucherId)->delete();
                }

                if ($fruitId !== null) {
                    Fruit::query()->whereKey($fruitId)->delete();
                }
            });
        }

        foreach (array_merge($workerFiles, [$gatePath]) as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir($tempDirectory)) {
            rmdir($tempDirectory);
        }
    }
}

function generateTestVoucherCode(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $suffix = '';

    for ($index = 0; $index < 8; $index++) {
        $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    return 'QDJ-V-'.$suffix;
}

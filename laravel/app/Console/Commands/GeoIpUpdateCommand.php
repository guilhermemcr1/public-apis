<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Process\Process;

final class GeoIpUpdateCommand extends Command
{
    protected $signature = 'geoip:update {--edition=* : Limitar a editions específicas (ex.: GeoLite2-City)}';

    protected $description = 'Baixa e instala/atualiza bases GeoLite2 (.mmdb) via API de download MaxMind';

    public function handle(): int
    {
        $licenseKey = trim((string) config('geoip.license_key'));
        if ($licenseKey === '') {
            $this->error('Configure MAXMIND_LICENSE_KEY no .env.');

            return self::FAILURE;
        }

        /** @var array<string, array{edition_id: string, path: string}> $databases */
        $databases = config('geoip.databases', []);
        $onlyEditions = $this->option('edition');
        $failed = false;

        foreach ($databases as $label => $cfg) {
            $editionId = $cfg['edition_id'];
            if ($onlyEditions !== [] && ! in_array($editionId, $onlyEditions, true)) {
                continue;
            }

            $this->info("Atualizando {$editionId} ({$label})…");

            if (! $this->downloadAndInstallEdition($editionId, $cfg['path'], $licenseKey)) {
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function downloadAndInstallEdition(string $editionId, string $targetPath, string $licenseKey): bool
    {
        $url = sprintf(
            'https://download.maxmind.com/app/geoip_download?edition_id=%s&license_key=%s&suffix=tar.gz',
            rawurlencode($editionId),
            rawurlencode($licenseKey)
        );

        $workDir = storage_path('app/geoip/.tmp/'.uniqid('dl_', true));
        $extractRoot = $workDir.'/extract';

        if (! mkdir($workDir, 0755, true) || ! mkdir($extractRoot, 0755, true)) {
            $this->error("Não foi possível criar diretório temporário: {$workDir}");

            return false;
        }

        $archivePath = $workDir.'/archive.tar.gz';

        try {
            $response = Http::timeout(600)
                ->retry(2, 5000)
                ->sink($archivePath)
                ->get($url);

            if (! $response->successful()) {
                $this->error("Download falhou (HTTP {$response->status()}) para {$editionId}.");

                return false;
            }

            $process = new Process(['tar', '-xzf', $archivePath, '-C', $extractRoot]);
            $process->setTimeout(600);
            $process->run();

            if (! $process->isSuccessful()) {
                $this->error('Extração tar falhou: '.trim($process->getErrorOutput() ?: $process->getOutput()));

                return false;
            }

            $mmdbPath = $this->findFirstMmdbFile($extractRoot);
            if ($mmdbPath === null) {
                $this->error("Nenhum arquivo .mmdb encontrado no pacote {$editionId}.");

                return false;
            }

            $targetDir = dirname($targetPath);
            if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true)) {
                $this->error("Não foi possível criar {$targetDir}");

                return false;
            }

            $tmpTarget = $targetPath.'.tmp';
            if (! copy($mmdbPath, $tmpTarget)) {
                $this->error("Não foi possível copiar para {$tmpTarget}");

                return false;
            }

            if (! rename($tmpTarget, $targetPath)) {
                @unlink($tmpTarget);
                $this->error("Não foi possível substituir {$targetPath}");

                return false;
            }

            $this->info("Instalado em {$targetPath}");

            return true;
        } finally {
            $this->deleteDirectory($workDir);
        }
    }

    private function findFirstMmdbFile(string $directory): ?string
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'mmdb') {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if ($file->isDir()) {
                @rmdir($path);

                continue;
            }
            @unlink($path);
        }

        @rmdir($directory);
    }
}

<?php

namespace App\Services\Ixc;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Spawns the Node/Playwright scraper (scripts/ixc-sync/scrape.js) as a
 * child process, passing IXC credentials as process env vars only —
 * never written to a file, never logged. See scripts/ixc-sync/README.md
 * for what the script does and why it exists (no API on this IXC account).
 */
class ProcessScraperRunner implements ScraperRunner
{
    public function run(): array
    {
        $config = config('ixc');

        foreach (['base_url', 'username', 'password'] as $key) {
            if (empty($config[$key])) {
                throw new IxcScraperException("Missing config ixc.{$key} — set the corresponding IXC_* env var.");
            }
        }

        $process = new Process(
            [$config['node_binary'], $config['script_path']],
            dirname($config['script_path']),
            [
                'IXC_BASE_URL' => $config['base_url'],
                'IXC_USERNAME' => $config['username'],
                'IXC_PASSWORD' => $config['password'],
                'IXC_BRANCH_NAME' => $config['branch_name'],
                'IXC_TECHNICIANS' => implode(',', $config['technicians']),
                'IXC_DEBUG' => app()->isLocal() ? '1' : '0',
            ],
        );

        $process->setTimeout($config['timeout_seconds']);
        $process->run();

        // The scraper writes progress to stderr regardless of outcome —
        // worth keeping even on success, since it's the trail for
        // recalibrating selectors when IXC's UI changes.
        if ($process->getErrorOutput() !== '') {
            Log::channel(config('logging.default'))->info('ixc-sync scraper output', [
                'stderr' => $process->getErrorOutput(),
            ]);
        }

        if (! $process->isSuccessful()) {
            throw new IxcScraperException(
                'IXC scraper failed: '.$process->getErrorOutput(),
                previous: new ProcessFailedException($process),
            );
        }

        $decoded = json_decode($process->getOutput(), true);

        if (! is_array($decoded)) {
            throw new IxcScraperException('IXC scraper did not return valid JSON on stdout: '.$process->getOutput());
        }

        return $decoded;
    }
}

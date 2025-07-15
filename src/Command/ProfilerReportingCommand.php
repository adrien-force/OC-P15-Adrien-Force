<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ProfilerReportingCommand extends Command
{
    protected static $defaultName = 'app:profiler:reporting';
    protected static $defaultDescription = 'Returns the latest N profiler tokens for a given route pattern';

    private Profiler $profiler;

    public function __construct(
        #[Autowire(service: 'profiler')]
        Profiler $profiler
    ) {
        parent::__construct();
        $this->profiler = $profiler;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->addOption('n', null, InputArgument::OPTIONAL, 'Number of tokens to return', 10)
            ->addOption('s', null, InputArgument::OPTIONAL, 'Size of entity', 0)
            ->addOption('run-test', 't', InputArgument::OPTIONAL, 'Execute Apache Benchmark before collecting data (true/false)', false)
            ->addOption('requests', 'r', InputArgument::OPTIONAL, 'Number of requests to execute with ab', 100)
            ->addOption('concurrency', 'c', InputArgument::OPTIONAL, 'Concurrency level for ab', 1)
            ->addOption('cookie', 'k', InputArgument::OPTIONAL, 'Cookie to send with ab requests (e.g. "PHPSESSID=eo8gqbtnorfl25o0f7390o9v37")', '')
            ->addOption('base-url', 'u', InputArgument::OPTIONAL, 'Base URL for ab requests', 'http://127.0.0.1:8000')
            ->addArgument('routes', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Les routes à analyser (e.g. /admin/ ou !/api/)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $routes = $input->getArgument('routes');
        $n = (int) $input->getOption('n');
        $s = (int) $input->getOption('s');
        $v = $input->getOption('verbose');

        if ($n < 1) {
            $io->error('Number of tokens must be at least 1.');
            return Command::FAILURE;
        }

        $benchmarkOptions = [
            'run' => $input->getOption('run-test'),
            'requests' => (int) $input->getOption('requests'),
            'concurrency' => (int) $input->getOption('concurrency'),
            'cookie' => $input->getOption('cookie'),
            'baseUrl' => $input->getOption('base-url'),
        ];

        foreach ($routes as $route) {
            if ($benchmarkOptions['run']) {
                $this->runBenchmark($route, $benchmarkOptions, $io, $output);
            }

            $csvPath = $this->generateCsvPath($route, $n, $s);
            $tokens = $this->profiler->find('', $route, $n, '', '', '');

            if (!$tokens) {
                $io->warning('No tokens found for the given route pattern: ' . $route);
                continue;
            }

            $rows = $this->collectProfilerData($tokens);
            $headers = $this->getHeaders();

            if ($v > 1) {
                $this->displayTable($io, $n, $route, $headers, $rows);
            }
            $this->generateCsv($csvPath, $route, $headers, $rows, $io);
        }

        return Command::SUCCESS;
    }

    private function runBenchmark(string $route, array $options, SymfonyStyle $io, OutputInterface $output): void
    {
        $fullUrl = rtrim($options['baseUrl'], '/') . '/' . ltrim($route, '/');
        $abCommand = $this->buildAbCommand($fullUrl, $options);

        $io->section(sprintf('Executing benchmark on %s', $fullUrl));
        $io->text('Running: ' . $abCommand);

        $process = proc_open($abCommand, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes);

        if (is_resource($process)) {
            $this->handleProcessOutput($pipes, $output);
            $exitCode = proc_close($process);

            if ($exitCode !== 0) {
                $io->warning(sprintf('Apache Benchmark exited with code %d', $exitCode));
            } else {
                $io->success('Benchmark completed successfully');
            }
        } else {
            $io->error('Failed to execute Apache Benchmark command');
        }

        $io->text('Waiting 2 seconds before collecting profiler data...');
        sleep(2);
    }

    private function buildAbCommand(string $fullUrl, array $options): string
    {
        $abCommand = sprintf('ab -n %d -c %d', $options['requests'], $options['concurrency']);

        if (!empty($options['cookie'])) {
            $abCommand .= sprintf(' -C "%s"', $options['cookie']);
        }

        $abCommand .= sprintf(' "%s"', $fullUrl);

        return $abCommand;
    }

    private function handleProcessOutput(array $pipes, OutputInterface $output): void
    {
        while ($line = fgets($pipes[1])) {
            $output->write($line);
        }

        while ($line = fgets($pipes[2])) {
            $output->write($line);
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
    }

    private function generateCsvPath(string $route, int $n, int $s): string
    {
        $safeRoute = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($route, '/'));
        if ($safeRoute === '') {
            $safeRoute = 'root';
        }

        return sprintf('var/log/profiler_%s_%d_%d.csv', $safeRoute, $n, $s);
    }

    private function collectProfilerData(array $tokens): array
    {
        $rows = [];

        foreach ($tokens as $tokenData) {
            $token = $tokenData['token'] ?? null;
            $dbTimeMs = $queryCount = $managedEntities = 'N/A';

            if ($token) {
                $profile = $this->profiler->loadProfile($token);
                if ($profile && $profile->hasCollector('db')) {
                    $dbCollector = $profile->getCollector('db');
                    $dbTimeMs = number_format($dbCollector->getTime() * 1000, 2) . ' ms';
                    $queryCount = $dbCollector->getQueryCount();
                    $managedEntities = $dbCollector->getManagedEntityCount();
                }
            }

            $rows[] = [
                $token,
                $tokenData['ip'] ?? '',
                $tokenData['method'] ?? '',
                $tokenData['url'] ?? '',
                isset($tokenData['time']) ? date('Y-m-d H:i:s', $tokenData['time']) : '',
                $queryCount,
                $dbTimeMs,
                $managedEntities,
            ];
        }

        return $rows;
    }

    private function getHeaders(): array
    {
        return [
            'Token',
            'IP',
            'Method',
            'URL',
            'Time',
            'Queries',
            'Query Time',
            'Managed Entities'
        ];
    }

    private function displayTable(SymfonyStyle $io, int $n, string $route, array $headers, array $rows): void
    {
        $io->section(sprintf('Latest %d tokens for route pattern "%s":', $n, $route));
        $io->table($headers, $rows);
    }

    private function generateCsv(string $csvPath, string $route, array $headers, array $rows, SymfonyStyle $io): void
    {
        $fp = fopen($csvPath, 'wb');

        fputcsv($fp, ['Route', $route]);
        fputcsv($fp, $headers);

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);
        $io->success("CSV généré: $csvPath");
    }
}

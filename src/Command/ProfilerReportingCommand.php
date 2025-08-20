<?php

namespace App\Command;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\DataCollector\TimeDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ProfilerReportingCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:profiler:reporting';
    /**
     * @var string
     */
    protected static $defaultDescription = 'Returns the latest N profiler tokens for a given route pattern';

    private Profiler $profiler;

    public function __construct(
        #[Autowire(service: 'profiler')]
        Profiler $profiler,
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
            ->addOption('base-url', 'u', InputArgument::OPTIONAL, 'Base URL for ab requests', 'http://127.0.0.1:8000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $nOption = $input->getOption('n');
        $sOption = $input->getOption('s');
        $n = is_numeric($nOption) ? (int) $nOption : 10;
        $s = is_numeric($sOption) ? (int) $sOption : 0;

        if ($n < 1) {
            $io->error('Number of tokens must be at least 1.');

            return Command::FAILURE;
        }

        $benchmarkOptions = $this->getBenchmarkOptions($input);

        $routes = ['/guests', '/portfolio', '/admin/media', '/admin/album', '/admin/guest', '/admin/guest/manage'];
        $allRouteData = [];

        foreach ($routes as $route) {
            $routeData = $this->processRoute($route, $n, $s, $benchmarkOptions, $io, $output);
            if ($routeData) {
                $allRouteData[$route] = $routeData;
            }
        }

        if (count($allRouteData) > 0) {
            $this->generateGlobalReport($allRouteData, $n, $s, $io);
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $benchmarkOptions
     *
     * @return array<int, array<int, mixed>>|null
     */
    private function processRoute(string $route, int $n, int $s, array $benchmarkOptions, SymfonyStyle $io, OutputInterface $output): ?array
    {
        if ($benchmarkOptions['run']) {
            $this->runBenchmark($route, $benchmarkOptions, $io, $output);
        }

        $csvPath = $this->generateCsvPath($route, $n, $s);
        $tokens = $this->profiler->find('', $route, $n, '', '', '');

        if (!$tokens) {
            $io->warning('No tokens found for the given route pattern: '.$route);

            return null;
        }

        /** @var array<int, array<string, mixed>> $typedTokens */
        $typedTokens = $tokens;
        $rows = $this->collectProfilerData($typedTokens);
        $headers = $this->getHeaders();

        $this->generateCsv($csvPath, $route, $headers, $rows, $io);

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function getBenchmarkOptions(InputInterface $input): array
    {
        $requestsOption = $input->getOption('requests');
        $concurrencyOption = $input->getOption('concurrency');

        return [
            'run' => $input->getOption('run-test'),
            'requests' => is_numeric($requestsOption) ? (int) $requestsOption : 100,
            'concurrency' => is_numeric($concurrencyOption) ? (int) $concurrencyOption : 1,
            'cookie' => $input->getOption('cookie'),
            'baseUrl' => $input->getOption('base-url'),
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function runBenchmark(string $route, array $options, SymfonyStyle $io, OutputInterface $output): void
    {
        $baseUrl = is_string($options['baseUrl']) ? $options['baseUrl'] : 'http://127.0.0.1:8000';
        $fullUrl = rtrim($baseUrl, '/').'/'.ltrim($route, '/');
        $abCommand = $this->buildAbCommand($fullUrl, $options);

        $io->section(sprintf('Executing benchmark on %s', $fullUrl));
        $io->text('Running: '.$abCommand);

        $process = proc_open($abCommand, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (is_resource($process) && is_array($pipes)) {
            $this->handleProcessOutput($pipes, $output);
            $exitCode = proc_close($process);

            if (0 !== $exitCode) {
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

    /**
     * @param array<string, mixed> $options
     */
    private function buildAbCommand(string $fullUrl, array $options): string
    {
        $requests = is_int($options['requests']) ? $options['requests'] : 100;
        $concurrency = is_int($options['concurrency']) ? $options['concurrency'] : 1;
        $abCommand = sprintf('ab -n %d -c %d -v 1', $requests, $concurrency);

        $cookie = $options['cookie'] ?? '';
        if (is_string($cookie) && !empty($cookie)) {
            $abCommand .= sprintf(' -C "%s"', $cookie);
        }

        $abCommand .= sprintf(' "%s"', $fullUrl);

        return $abCommand;
    }

    /**
     * @param mixed[] $pipes
     */
    private function handleProcessOutput(array $pipes, OutputInterface $output): void
    {
        if (!isset($pipes[1], $pipes[2]) || !is_resource($pipes[1]) || !is_resource($pipes[2])) {
            return;
        }

        while ($line = fgets($pipes[1])) {
            $output->write($line);
        }

        while ($line = fgets($pipes[2])) {
            $output->write($line);
        }

        if (is_resource($pipes[0])) {
            fclose($pipes[0]);
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
    }

    private function generateCsvPath(string $route, int $n, int $s): string
    {
        $safeRoute = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($route, '/'));
        if ('' === $safeRoute) {
            $safeRoute = 'root';
        }

        return sprintf('var/log/profiler_%s_%d_%d.csv', $safeRoute, $n, $s);
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return array<int, array<int, mixed>>
     */
    private function collectProfilerData(array $tokens): array
    {
        $rows = [];

        foreach ($tokens as $tokenData) {
            $token = $tokenData['token'] ?? null;
            $dbTimeMs = $renderTimeMs = $queryCount = $managedEntities = 'N/A';

            if (is_string($token)) {
                $profile = $this->profiler->loadProfile($token);

                if ($profile) {
                    if ($profile->hasCollector('db')) {
                        /**
                         * @var DoctrineDataCollector $dbCollector
                         */
                        $dbCollector = $profile->getCollector('db');
                        $dbTime = $dbCollector->getTime() * 1000;
                        $dbTimeMs = number_format($dbTime, 2, '.', '');
                        $queryCount = $dbCollector->getQueryCount();
                        $managedEntities = $dbCollector->getManagedEntityCount();
                    }

                    if ($profile->hasCollector('time')) {
                        /**
                         * @var TimeDataCollector $timeCollector
                         */
                        $timeCollector = $profile->getCollector('time');
                        $renderTime = $timeCollector->getDuration();
                        $renderTimeMs = number_format($renderTime, 2, '.', '');
                    }
                }
            }

            $time = $tokenData['time'] ?? null;
            $timeFormatted = is_int($time) ? date('Y-m-d H:i:s', $time) : '';
            $ip = $tokenData['ip'] ?? '';
            $method = $tokenData['method'] ?? '';
            $url = $tokenData['url'] ?? '';

            $rows[] = [
                $timeFormatted,
                $dbTimeMs,
                $renderTimeMs,
                $token,
                is_string($ip) ? $ip : '',
                is_string($method) ? $method : '',
                is_string($url) ? $url : '',
                $queryCount,
                $managedEntities,
            ];
        }

        return $rows;
    }

    /**
     * @return string[]
     */
    private function getHeaders(): array
    {
        return [
            'Time',
            'Query Time (ms)',
            'Render Time (ms)',
            'Token',
            'IP',
            'Method',
            'URL',
            'Queries',
            'Managed Entities',
        ];
    }

    /**
     * @param string[]                      $headers
     * @param array<int, array<int, mixed>> $rows
     */
    private function generateCsv(string $csvPath, string $route, array $headers, array $rows, SymfonyStyle $io): void
    {
        $fp = fopen($csvPath, 'wb');
        if (false === $fp) {
            $io->error("Impossible d'ouvrir le fichier CSV : $csvPath");

            return;
        }
        fputcsv($fp, $headers);

        foreach ($rows as $row) {
            $csvRow = array_map(function ($value) {
                return is_scalar($value) || null === $value ? (string) $value : '';
            }, $row);
            fputcsv($fp, $csvRow);
        }

        fclose($fp);
        $io->success("CSV généré: $csvPath");
    }

    /**
     * @param array<string, array<int, array<int, mixed>>> $allRouteData
     */
    private function generateGlobalReport(array $allRouteData, int $n, int $s, SymfonyStyle $io): void
    {
        $spreadsheet = new Spreadsheet();

        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Récapitulatif');

        $this->generateSummarySheet($summarySheet, $allRouteData);

        $sheetIndex = 1;
        foreach ($allRouteData as $route => $rows) {
            $routeName = $this->getSafeSheetName($route);
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle($routeName);

            $headers = $this->getHeaders();
            $sheet->fromArray($headers, null, 'A1');

            $rowNumber = 2;
            foreach ($rows as $row) {
                $sheet->fromArray($row, null, 'A'.$rowNumber++);
            }

            $this->formatNumericColumns($sheet);
            $this->applyStyles($sheet, $rowNumber, count($headers));
        }

        $reportPath = 'var/log/profiler_global_report_'.date('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($reportPath);

        $io->success("Rapport global généré: $reportPath");
    }

    /**
     * @param array<string, array<int, array<int, mixed>>> $allRouteData
     */
    private function generateSummarySheet(Worksheet $sheet, array $allRouteData): void
    {
        $headers = ['Route', 'Nombre de requêtes', 'Temps requête DB moyen (ms)', 'Écart type DB (ms)',
            'Temps rendu moyen (ms)', 'Écart type rendu (ms)', 'Requêtes SQL', 'Entités gérées'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($allRouteData as $route => $rows) {
            $queryTimes = [];
            $renderTimes = [];
            $queryCounts = [];
            $entityCounts = [];

            foreach ($rows as $data) {
                if (count($data) < 9) {
                    continue;
                }

                $queryTime = 'N/A' !== $data[1] && is_numeric($data[1]) ? (float) $data[1] : null;
                $renderTime = 'N/A' !== $data[2] && is_numeric($data[2]) ? (float) $data[2] : null;
                $queryCount = 'N/A' !== $data[7] && is_numeric($data[7]) ? (int) $data[7] : null;
                $entityCount = 'N/A' !== $data[8] && is_numeric($data[8]) ? (int) $data[8] : null;

                if (null !== $queryTime) {
                    $queryTimes[] = $queryTime;
                }
                if (null !== $renderTime) {
                    $renderTimes[] = $renderTime;
                }
                if (null !== $queryCount) {
                    $queryCounts[] = $queryCount;
                }
                if (null !== $entityCount) {
                    $entityCounts[] = $entityCount;
                }
            }

            $sheet->setCellValue('A'.$row, $route);
            $sheet->setCellValue('B'.$row, count($rows));
            $sheet->setCellValue('C'.$row, $this->calculateAverage($queryTimes));
            $sheet->setCellValue('D'.$row, $this->calculateStandardDeviation($queryTimes));
            $sheet->setCellValue('E'.$row, $this->calculateAverage($renderTimes));
            $sheet->setCellValue('F'.$row, $this->calculateStandardDeviation($renderTimes));
            $sheet->setCellValue('G'.$row, $this->calculateAverage($queryCounts));
            $sheet->setCellValue('H'.$row, $this->calculateAverage($entityCounts));

            ++$row;
        }

        $this->applyStyles($sheet, $row, count($headers));

        $sheet->getStyle('C2:F'.($row - 1))->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    /**
     * @param float[] $values
     */
    private function calculateAverage(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        return array_sum($values) / count($values);
    }

    /**
     * @param float[] $values
     */
    private function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count <= 1) {
            return 0;
        }

        $avg = $this->calculateAverage($values);
        $variance = 0;

        foreach ($values as $value) {
            $variance += ($value - $avg) ** 2;
        }

        return sqrt($variance / $count);
    }

    private function getSafeSheetName(string $route): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($route, '/'));
        if (null === $safeName || '' === $safeName) {
            $safeName = 'root';
        }

        return substr($safeName, 0, 31);
    }

    private function formatNumericColumns(Worksheet $sheet): void
    {
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('B2:C'.$lastRow)->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    private function applyStyles(Worksheet $sheet, int $rowCount, int $columnCount): void
    {
        foreach (range('A', Coordinate::stringFromColumnIndex($columnCount)) as $columnId) {
            $sheet->getColumnDimension($columnId)->setAutoSize(true);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => Color::COLOR_BLACK]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $sheet->getStyle('A1:'.Coordinate::stringFromColumnIndex($columnCount).'1')->applyFromArray($headerStyle);

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => Color::COLOR_BLACK],
                ],
            ],
        ];

        $sheet->getStyle('A1:'.Coordinate::stringFromColumnIndex($columnCount).($rowCount - 1))->applyFromArray($borderStyle);
    }
}

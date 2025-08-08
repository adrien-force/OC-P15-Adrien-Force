<?php

namespace App\Command;

use App\Service\ImageCompressionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:compress-images',
    description: 'Compress existing images in the uploads folder',
)]
class CompressImagesCommand extends Command
{
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'tiff', 'heic'];

    public function __construct(
        private readonly ImageCompressionService $imageCompressionService,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('quality', 'qa', InputOption::VALUE_OPTIONAL, 'Compression quality (1-100)', 85)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be compressed without actually doing it')
            ->setHelp('This command compresses all images in the uploads folder to reduce file size while maintaining quality.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $qualityOption = $input->getOption('quality');
        $quality = is_numeric($qualityOption) ? (int) $qualityOption : 85;
        $dryRun = $input->getOption('dry-run');

        if ($quality < 1 || $quality > 100) {
            $io->error('Quality must be between 1 and 100');
            return Command::FAILURE;
        }

        $uploadsDir = $this->projectDir . '/public/uploads';
        if (!is_dir($uploadsDir)) {
            $io->error("Uploads directory not found: {$uploadsDir}");
            return Command::FAILURE;
        }

        $finder = new Finder();
        $finder->files()
            ->in($uploadsDir)
            ->name('/\.(' . implode('|', self::SUPPORTED_EXTENSIONS) . ')$/i');

        $files = iterator_to_array($finder);
        $totalFiles = count($files);

        if ($totalFiles === 0) {
            $io->info('No supported image files found in uploads directory');
            return Command::SUCCESS;
        }

        $io->title('Image Compression to WebP');
        $io->text("Found {$totalFiles} image files to convert to WebP");
        $io->text("Quality setting: {$quality}%");

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No files will be modified');
        }

        $progressBar = $io->createProgressBar($totalFiles);
        $progressBar->start();

        $totalSizeBefore = 0;
        $totalSizeAfter = 0;
        $processedCount = 0;

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $sizeBefore = filesize($filePath);
            $totalSizeBefore += $sizeBefore;

            if (!$dryRun) {
                try {
                    $newPath = $this->imageCompressionService->compressExistingFile($filePath, null, $quality);
                    $sizeAfter = file_exists($newPath) ? filesize($newPath) : 0;
                    $totalSizeAfter += $sizeAfter;
                    $processedCount++;
                } catch (\Exception $e) {
                    $io->error("Failed to compress {$file->getFilename()}: " . $e->getMessage());
                    $totalSizeAfter += $sizeBefore;
                    continue;
                }
            } else {
                $totalSizeAfter += $sizeBefore;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        if (!$dryRun) {
            $savings = $totalSizeBefore - $totalSizeAfter;
            $savingsPercent = $totalSizeBefore > 0 ? ($savings / $totalSizeBefore) * 100 : 0;

            $io->success([
                "Successfully converted {$processedCount} images to WebP!",
                "Total size before: " . $this->formatBytes($totalSizeBefore),
                "Total size after: " . $this->formatBytes($totalSizeAfter),
                "Space saved: " . $this->formatBytes($savings) . " (" . round($savingsPercent, 2) . "%)",
            ]);
        } else {
            $io->info([
                "Would convert {$totalFiles} files to WebP",
                "Total size: " . $this->formatBytes($totalSizeBefore),
                "Run without --dry-run to actually convert the images",
            ]);
        }

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1024 ** $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

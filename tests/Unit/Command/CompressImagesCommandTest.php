<?php

namespace Unit\Command;

use App\Command\CompressImagesCommand;
use App\Service\ImageCompressionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CompressImagesCommandTest extends TestCase
{
    private ImageCompressionService&MockObject $imageCompressionService;
    private CompressImagesCommand $command;
    private CommandTester $commandTester;
    private string $tempProjectDir;
    private string $tempUploadsDir;

    protected function setUp(): void
    {
        $this->imageCompressionService = $this->createMock(ImageCompressionService::class);

        $this->tempProjectDir = sprintf('%s/test_project_%s', sys_get_temp_dir(), uniqid('', true));
        $this->tempUploadsDir = sprintf('%s/public/uploads', $this->tempProjectDir);
        mkdir($this->tempUploadsDir, 0755, true);

        $this->command = new CompressImagesCommand(
            $this->imageCompressionService,
            $this->tempProjectDir
        );

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempProjectDir)) {
            $this->removeDirectory($this->tempProjectDir);
        }

        parent::tearDown();
    }

    public function testConstructor(): void
    {
        $command = new CompressImagesCommand(
            $this->imageCompressionService,
            '/test/project'
        );

        $this->assertEquals('app:compress-images', $command->getName());
        $this->assertEquals('Compress existing images in the uploads folder', $command->getDescription());
    }

    public function testConfigure(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('quality'));
        $this->assertTrue($definition->hasOption('dry-run'));

        $qualityOption = $definition->getOption('quality');
        $this->assertEquals('qa', $qualityOption->getShortcut());
        $this->assertEquals(85, $qualityOption->getDefault());

        $dryRunOption = $definition->getOption('dry-run');
        $this->assertNull($dryRunOption->getShortcut());
        $this->assertFalse($dryRunOption->getDefault());
    }

    public function testExecuteWithInvalidQualityReturnsFailure(): void
    {
        $this->commandTester->execute([
            '--quality' => '150',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Quality must be between 1 and 100', $this->commandTester->getDisplay());
    }

    public function testExecuteWithInvalidQualityLowReturnsFailure(): void
    {
        $this->commandTester->execute([
            '--quality' => '0',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Quality must be between 1 and 100', $this->commandTester->getDisplay());
    }

    public function testExecuteWithNonNumericQualityUsesDefault(): void
    {
        $testImagePath = $this->tempUploadsDir.'/test.jpg';
        $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        file_put_contents($testImagePath, $testImageContent);

        $this->imageCompressionService
            ->expects($this->once())
            ->method('compressExistingFile')
            ->with($this->callback(function ($path) use ($testImagePath) {
                return basename($path) === basename($testImagePath);
            }), null, 85)
            ->willReturn(sprintf('%s.webp', $testImagePath));

        $this->commandTester->execute([
            '--quality' => 'invalid',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithNonExistentUploadsDirectoryReturnsFailure(): void
    {
        rmdir($this->tempUploadsDir);
        rmdir(sprintf('%s/public', $this->tempProjectDir));

        $this->commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Uploads directory not found:', $this->commandTester->getDisplay());
    }

    public function testExecuteWithNoImagesReturnsSuccess(): void
    {
        $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('No supported image files found in uploads directory', $this->commandTester->getDisplay());
    }

    public function testExecuteWithDryRunMode(): void
    {
        $testImages = ['test1.jpg', 'test2.png', 'test3.webp'];
        foreach ($testImages as $imageName) {
            $testImagePath = sprintf('%s/%s', $this->tempUploadsDir, $imageName);
            $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
            file_put_contents($testImagePath, $testImageContent);
        }

        $this->imageCompressionService
            ->expects($this->never())
            ->method('compressExistingFile');

        $this->commandTester->execute([
            '--dry-run' => true,
            '--quality' => '75',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('DRY RUN MODE - No files will be modified', $output);
        $this->assertStringContainsString('Would convert 3 files to WebP', $output);
        $this->assertStringContainsString('Quality setting: 75%', $output);
    }

    public function testExecuteSuccessfullyCompressesImages(): void
    {
        $testImages = ['image1.jpg', 'image2.png'];
        foreach ($testImages as $imageName) {
            $testImagePath = sprintf('%s/%s', $this->tempUploadsDir, $imageName);
            $testImageContent = str_repeat('test_content', 100);
            file_put_contents($testImagePath, $testImageContent);
        }

        $this->imageCompressionService
            ->expects($this->exactly(2))
            ->method('compressExistingFile')
            ->willReturnCallback(function ($filePath) {
                $webpPath = str_replace(['.jpg', '.png'], '.webp', $filePath);
                file_put_contents($webpPath, 'compressed_content');

                return $webpPath;
            });

        $this->commandTester->execute([
            '--quality' => '90',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Successfully converted 2 images to WebP!', $output);
        $this->assertStringContainsString('Quality setting: 90%', $output);
        $this->assertStringContainsString('Space saved:', $output);
    }

    public function testExecuteHandlesCompressionErrors(): void
    {
        $testImagePath = sprintf('%s/error_image.jpg', $this->tempUploadsDir);
        file_put_contents($testImagePath, 'test_content');

        $this->imageCompressionService
            ->expects($this->once())
            ->method('compressExistingFile')
            ->willThrowException(new \Exception('Compression failed'));

        $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Failed to compress error_image.jpg: Compression failed', $output);
    }

    public function testExecuteWithVariousImageExtensions(): void
    {
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'tiff', 'heic'];
        foreach ($extensions as $ext) {
            $testImagePath = sprintf("%s/test.$ext", $this->tempUploadsDir);
            file_put_contents($testImagePath, 'test_content');
        }

        file_put_contents(filename: sprintf('%s/document.pdf', $this->tempUploadsDir), data: 'pdf_content');
        file_put_contents(sprintf('%s/text.txt', $this->tempUploadsDir), 'text_content');

        $this->imageCompressionService
            ->expects($this->exactly(7))
            ->method('compressExistingFile')
            ->willReturn('compressed.webp');

        $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Found 7 image files to convert', $output);
    }

    /**
     * @throws \ReflectionException
     */
    public function testFormatBytesMethod(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('formatBytes');

        $this->assertEquals('0 B', $method->invokeArgs($this->command, [0]));
        $this->assertEquals('500 B', $method->invokeArgs($this->command, [500]));
        $this->assertEquals('1 KB', $method->invokeArgs($this->command, [1024]));
        $this->assertEquals('1.5 KB', $method->invokeArgs($this->command, [1536]));
        $this->assertEquals('1 MB', $method->invokeArgs($this->command, [1024 * 1024]));
        $this->assertEquals('1 GB', $method->invokeArgs($this->command, [1024 * 1024 * 1024]));
        $this->assertEquals('1024 GB', $method->invokeArgs($this->command, [1024 * 1024 * 1024 * 1024]));
    }

    /**
     * @throws \ReflectionException
     */
    public function testFormatBytesWithNegativeValues(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('formatBytes');

        $this->assertEquals('0 B', $method->invokeArgs($this->command, [-100]));
    }

    public function testExecuteWithSubdirectories(): void
    {
        mkdir(sprintf('%s/subdir1', $this->tempUploadsDir), 0755, true);
        mkdir(sprintf('%s/subdir2', $this->tempUploadsDir), 0755, true);

        $testImages = [
            'image1.jpg',
            'subdir1/image2.png',
            'subdir2/image3.jpeg',
        ];

        foreach ($testImages as $imagePath) {
            $fullPath = sprintf('%s/%s', $this->tempUploadsDir, $imagePath);
            file_put_contents($fullPath, 'test_content');
        }

        $this->imageCompressionService
            ->expects($this->exactly(3))
            ->method('compressExistingFile')
            ->willReturn('compressed.webp');

        $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Found 3 image files to convert', $output);
    }

    public function testExecuteCalculatesSavingsCorrectly(): void
    {
        $testImagePath = sprintf('%s/large_image.jpg', $this->tempUploadsDir);
        $largeContent = str_repeat('x', 10000);
        file_put_contents($testImagePath, $largeContent);

        $this->imageCompressionService
            ->expects($this->once())
            ->method('compressExistingFile')
            ->willReturnCallback(function () {
                $compressedPath = sprintf('%s/large_image.webp', $this->tempUploadsDir);
                $smallContent = str_repeat('x', 5000);
                file_put_contents($compressedPath, $smallContent);

                return $compressedPath;
            });

        $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Space saved:', $output);
        $this->assertStringContainsString('50%', $output);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if ('.' !== $file && '..' !== $file) {
                $fullPath = $directory.'/'.$file;
                if (is_dir($fullPath)) {
                    $this->removeDirectory($fullPath);
                } else {
                    unlink($fullPath);
                }
            }
        }
        rmdir($directory);
    }
}

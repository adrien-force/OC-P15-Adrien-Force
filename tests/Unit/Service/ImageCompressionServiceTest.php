<?php

namespace App\Tests\Unit\Service;

use App\Service\ImageCompressionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageCompressionServiceTest extends TestCase
{
    private ImageCompressionService $service;

    protected function setUp(): void
    {
        $this->service = new ImageCompressionService();
    }

    public function testCompressUploadedFileWithDefaultQuality(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
        $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        file_put_contents($tempFile, $testImageContent);

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.jpg',
            'image/jpeg',
            null,
            true
        );

        $targetDir = sys_get_temp_dir().'/test_output';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir.'/compressed.jpg';

        $result = $this->service->compressUploadedFile($uploadedFile, $targetPath);

        $this->assertStringEndsWith('.webp', $result);

        // Clean up
        unlink($tempFile);
        if (file_exists($targetDir.'/compressed.webp')) {
            unlink($targetDir.'/compressed.webp');
        }
        if (is_dir($targetDir)) {
            rmdir($targetDir);
        }
    }

    public function testCompressUploadedFileWithCustomQuality(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
        $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        file_put_contents($tempFile, $testImageContent);

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.jpg',
            'image/jpeg',
            null,
            true
        );

        $targetDir = sys_get_temp_dir().'/test_output_custom';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir.'/compressed.jpg';

        $result = $this->service->compressUploadedFile($uploadedFile, $targetPath, 70);

        $this->assertStringEndsWith('.webp', $result);

        // Clean up
        unlink($tempFile);
        if (file_exists($targetDir.'/compressed.webp')) {
            unlink($targetDir.'/compressed.webp');
        }
        if (is_dir($targetDir)) {
            rmdir($targetDir);
        }
    }

    public function testCompressExistingFileThrowsExceptionWhenFileNotExists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source file does not exist: /nonexistent/file.jpg');

        $this->service->compressExistingFile('/nonexistent/file.jpg');
    }

    public function testCompressExistingFileWithoutTargetPath(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_existing');
        $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        file_put_contents($tempFile, $testImageContent);

        $result = $this->service->compressExistingFile($tempFile);

        $this->assertStringEndsWith('.webp', $result);

        // Original file should be deleted if different from webp path
        if ('webp' !== pathinfo($tempFile, PATHINFO_EXTENSION)) {
            $this->assertFileDoesNotExist($tempFile);
        }

        // Clean up webp file
        if (file_exists($result)) {
            unlink($result);
        }
    }

    public function testCompressExistingFileWithTargetPath(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_existing');
        $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        file_put_contents($tempFile, $testImageContent);

        $targetDir = sys_get_temp_dir().'/test_target';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $targetPath = $targetDir.'/output.jpg';

        $result = $this->service->compressExistingFile($tempFile, $targetPath);

        $this->assertStringEndsWith('.webp', $result);
        $this->assertFileExists($tempFile);

        // Clean up
        unlink($tempFile);
        if (file_exists($result)) {
            unlink($result);
        }
        if (is_dir($targetDir)) {
            rmdir($targetDir);
        }
    }

    public function testCompressExistingFileWithCustomQuality(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_existing');
        $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        file_put_contents($tempFile, $testImageContent);

        $result = $this->service->compressExistingFile($tempFile, null, 60);

        $this->assertStringEndsWith('.webp', $result);

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    }

    public function testGetCompressedSizeWithExistingFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_size');
        file_put_contents($tempFile, 'test content');

        $size = $this->service->getCompressedSize($tempFile);

        $this->assertGreaterThan(0, $size);
        $this->assertEquals(strlen('test content'), $size);

        unlink($tempFile);
    }

    public function testGetCompressedSizeWithNonExistentFile(): void
    {
        $size = $this->service->getCompressedSize('/nonexistent/file.jpg');

        $this->assertEquals(0, $size);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConvertToWebPWithFullPath(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertToWebP');

        $originalPath = '/path/to/image.jpg';
        $webpPath = $method->invokeArgs($this->service, [$originalPath]);

        $this->assertEquals('/path/to/image.webp', $webpPath);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConvertToWebPWithFileOnly(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertToWebP');

        $originalPath = 'image.jpg';
        $webpPath = $method->invokeArgs($this->service, [$originalPath]);

        $this->assertEquals('./image.webp', $webpPath);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConvertToWebPWithNoExtension(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertToWebP');

        $originalPath = '/path/to/imagename';
        $webpPath = $method->invokeArgs($this->service, [$originalPath]);

        $this->assertEquals('/path/to/imagename.webp', $webpPath);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetAbsolutePathWithAbsolutePath(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAbsolutePath');

        $absolutePath = '/absolute/path/to/file.jpg';
        $result = $method->invokeArgs($this->service, [$absolutePath]);

        $this->assertEquals($absolutePath, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetAbsolutePathWithRelativePath(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAbsolutePath');

        $relativePath = 'uploads/image.jpg';
        $result = $method->invokeArgs($this->service, [$relativePath]);

        // The service uses dirname(__DIR__, 3) from src/Service directory
        $projectRoot = dirname(__DIR__, 3); // tests/Unit/Service -> project root
        $expected = $projectRoot.'/public/'.$relativePath;
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetRelativePathWithPublicPath(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getRelativePath');

        $projectRoot = dirname(__DIR__, 3); // tests/Unit/Service -> project root
        $absolutePath = $projectRoot.'/public/uploads/image.jpg';
        $result = $method->invokeArgs($this->service, [$absolutePath]);

        $this->assertEquals('uploads/image.jpg', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetRelativePathWithNonPublicPath(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getRelativePath');

        $absolutePath = '/some/other/path/image.jpg';
        $result = $method->invokeArgs($this->service, [$absolutePath]);

        $this->assertEquals($absolutePath, $result);
    }

    public function testCompressUploadedFileCreatesDirectory(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
        $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        file_put_contents($tempFile, $testImageContent);

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.jpg',
            'image/jpeg',
            null,
            true
        );

        $nonExistentDir = sys_get_temp_dir().'/test_new_dir_'.uniqid('', true);
        $targetPath = $nonExistentDir.'/subdir/compressed.jpg';

        $this->service->compressUploadedFile($uploadedFile, $targetPath);

        $this->assertDirectoryExists(dirname($nonExistentDir.'/subdir/compressed.webp'));

        // Clean up
        unlink($tempFile);
        if (file_exists($nonExistentDir.'/subdir/compressed.webp')) {
            unlink($nonExistentDir.'/subdir/compressed.webp');
        }
        if (is_dir($nonExistentDir.'/subdir')) {
            rmdir($nonExistentDir.'/subdir');
        }
        if (is_dir($nonExistentDir)) {
            rmdir($nonExistentDir);
        }
    }

    public function testCompressExistingFileWithWebpSameFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_existing').'.webp';
        $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        file_put_contents($tempFile, $testImageContent);

        $result = $this->service->compressExistingFile($tempFile);

        $this->assertStringEndsWith('.webp', $result);
        $this->assertFileExists($tempFile);

        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    public function testScalingOfLargeImage(): void
    {
        // Create a large test image (2000x2000) that will exceed MAX_WIDTH and MAX_HEIGHT
        $largeImageContent = $this->createLargeImageData();

        $tempFile = tempnam(sys_get_temp_dir(), 'test_large_image').'.jpg';
        file_put_contents($tempFile, $largeImageContent);

        $uploadedFile = new UploadedFile(
            $tempFile,
            'large_test.jpg',
            'image/jpeg',
            null,
            true
        );

        $targetDir = sys_get_temp_dir().'/test_large_output';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir.'/large_compressed.jpg';

        // This should trigger the scale method in processImage
        $result = $this->service->compressUploadedFile($uploadedFile, $targetPath);

        $this->assertStringEndsWith('.webp', $result);
        $this->assertFileExists($result);

        // Clean up
        unlink($tempFile);
        if (file_exists($result)) {
            unlink($result);
        }
        if (is_dir($targetDir)) {
            rmdir($targetDir);
        }
    }

    private function createLargeImageData(): string
    {
        // Create a minimal but large JPEG image programmatically
        // This is a base64-encoded 2x2 JPEG that we'll use as template
        $smallImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');

        // Create a larger image by creating an image resource and scaling it up
        $tempSmallFile = tempnam(sys_get_temp_dir(), 'small_image').'.jpg';
        file_put_contents($tempSmallFile, $smallImageContent);

        // Use GD to create a large image
        $source = imagecreatefromjpeg($tempSmallFile);
        $large = imagecreate(2000, 1500);
        $white = imagecolorallocate($large, 255, 255, 255);
        if (false === $source) {
            // Fallback: create a simple large image with GD
            // This exceeds MAX_WIDTH (1920) and MAX_HEIGHT (1080)
            $black = imagecolorallocate($large, 0, 0, 0);
            imagefill($large, 0, 0, $white);
            imagestring($large, 5, 10, 10, 'Large Test Image', $black);
        } else {
            imagefill($large, 0, 0, $white);
            imagedestroy($source);
        }

        $largeTempFile = tempnam(sys_get_temp_dir(), 'large_temp').'.jpg';
        imagejpeg($large, $largeTempFile, 90);
        imagedestroy($large);

        $largeImageData = file_get_contents($largeTempFile);

        // Clean up temp files
        unlink($tempSmallFile);
        unlink($largeTempFile);

        return $largeImageData;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up any remaining temp files
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir.'/test_*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                rmdir($file);
            }
        }
    }
}

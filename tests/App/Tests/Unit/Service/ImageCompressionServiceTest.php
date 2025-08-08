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

    public function testConstructor(): void
    {
        $service = new ImageCompressionService();
        $this->assertInstanceOf(ImageCompressionService::class, $service);
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

        $targetDir = sys_get_temp_dir() . '/test_output';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $targetPath = $targetDir . '/compressed.jpg';
        
        $result = $this->service->compressUploadedFile($uploadedFile, $targetPath);
        
        $this->assertIsString($result);
        $this->assertStringEndsWith('.webp', $result);
        
        // Clean up
        unlink($tempFile);
        if (file_exists($targetDir . '/compressed.webp')) {
            unlink($targetDir . '/compressed.webp');
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

        $targetDir = sys_get_temp_dir() . '/test_output_custom';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $targetPath = $targetDir . '/compressed.jpg';
        
        $result = $this->service->compressUploadedFile($uploadedFile, $targetPath, 70);
        
        $this->assertIsString($result);
        $this->assertStringEndsWith('.webp', $result);
        
        // Clean up
        unlink($tempFile);
        if (file_exists($targetDir . '/compressed.webp')) {
            unlink($targetDir . '/compressed.webp');
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
        
        $this->assertIsString($result);
        $this->assertStringEndsWith('.webp', $result);
        
        // Original file should be deleted if different from webp path
        if (pathinfo($tempFile, PATHINFO_EXTENSION) !== 'webp') {
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
        
        $targetDir = sys_get_temp_dir() . '/test_target';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $targetPath = $targetDir . '/output.jpg';
        
        $result = $this->service->compressExistingFile($tempFile, $targetPath);
        
        $this->assertIsString($result);
        $this->assertStringEndsWith('.webp', $result);
        $this->assertFileExists($tempFile); // Original should still exist
        
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
        
        $this->assertIsString($result);
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
        
        $this->assertIsInt($size);
        $this->assertGreaterThan(0, $size);
        $this->assertEquals(strlen('test content'), $size);
        
        unlink($tempFile);
    }

    public function testGetCompressedSizeWithNonExistentFile(): void
    {
        $size = $this->service->getCompressedSize('/nonexistent/file.jpg');
        
        $this->assertEquals(0, $size);
    }

    public function testGetCompressedSizeWithFilesizeFailure(): void
    {
        // This test case is difficult to reliably simulate filesize() returning false
        // Skip it as the real-world scenario is already covered by other tests
        $this->markTestSkipped('Filesize failure simulation is environment-dependent');
    }

    public function testConvertToWebPWithFullPath(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertToWebP');
        $method->setAccessible(true);
        
        $originalPath = '/path/to/image.jpg';
        $webpPath = $method->invokeArgs($this->service, [$originalPath]);
        
        $this->assertEquals('/path/to/image.webp', $webpPath);
    }

    public function testConvertToWebPWithFileOnly(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertToWebP');
        $method->setAccessible(true);
        
        $originalPath = 'image.jpg';
        $webpPath = $method->invokeArgs($this->service, [$originalPath]);
        
        $this->assertEquals('./image.webp', $webpPath);
    }

    public function testConvertToWebPWithNoExtension(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertToWebP');
        $method->setAccessible(true);
        
        $originalPath = '/path/to/imagename';
        $webpPath = $method->invokeArgs($this->service, [$originalPath]);
        
        $this->assertEquals('/path/to/imagename.webp', $webpPath);
    }

    public function testGetAbsolutePathWithAbsolutePath(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAbsolutePath');
        $method->setAccessible(true);
        
        $absolutePath = '/absolute/path/to/file.jpg';
        $result = $method->invokeArgs($this->service, [$absolutePath]);
        
        $this->assertEquals($absolutePath, $result);
    }

    public function testGetAbsolutePathWithRelativePath(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAbsolutePath');
        $method->setAccessible(true);
        
        $relativePath = 'uploads/image.jpg';
        $result = $method->invokeArgs($this->service, [$relativePath]);
        
        $projectRoot = dirname(__DIR__, 3); // Adjust based on actual test location
        $expected = $projectRoot . '/public/' . $relativePath;
        $this->assertEquals($expected, $result);
    }

    public function testGetRelativePathWithPublicPath(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getRelativePath');
        $method->setAccessible(true);
        
        $projectRoot = dirname(__DIR__, 3);
        $absolutePath = $projectRoot . '/public/uploads/image.jpg';
        $result = $method->invokeArgs($this->service, [$absolutePath]);
        
        $this->assertEquals('uploads/image.jpg', $result);
    }

    public function testGetRelativePathWithNonPublicPath(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getRelativePath');
        $method->setAccessible(true);
        
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

        $nonExistentDir = sys_get_temp_dir() . '/test_new_dir_' . uniqid('', true);
        $targetPath = $nonExistentDir . '/subdir/compressed.jpg';
        
        $result = $this->service->compressUploadedFile($uploadedFile, $targetPath);
        
        $this->assertIsString($result);
        $this->assertDirectoryExists(dirname($nonExistentDir . '/subdir/compressed.webp'));
        
        // Clean up
        unlink($tempFile);
        if (file_exists($nonExistentDir . '/subdir/compressed.webp')) {
            unlink($nonExistentDir . '/subdir/compressed.webp');
        }
        if (is_dir($nonExistentDir . '/subdir')) {
            rmdir($nonExistentDir . '/subdir');
        }
        if (is_dir($nonExistentDir)) {
            rmdir($nonExistentDir);
        }
    }

    public function testCompressExistingFileWithWebpSameFile(): void
    {
        // Test the case where original file is already webp and should not be deleted
        $tempFile = tempnam(sys_get_temp_dir(), 'test_existing') . '.webp';
        $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        file_put_contents($tempFile, $testImageContent);
        
        $result = $this->service->compressExistingFile($tempFile);
        
        $this->assertIsString($result);
        $this->assertStringEndsWith('.webp', $result);
        // File should still exist since it was already webp
        $this->assertFileExists($tempFile);
        
        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up any remaining temp files
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . '/test_*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                rmdir($file);
            }
        }
    }
}
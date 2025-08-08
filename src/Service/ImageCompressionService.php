<?php

namespace App\Service;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Interfaces\ImageInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageCompressionService
{
    private const DEFAULT_QUALITY = 85;
    private const MAX_WIDTH = 1920;
    private const MAX_HEIGHT = 1080;
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function compressUploadedFile(UploadedFile $file, string $targetPath, int $quality = self::DEFAULT_QUALITY): string
    {
        $image = $this->manager->read($file->getPathname());

        $webpPath = $this->convertToWebP($targetPath);
        $this->processImage($image, $webpPath, $quality);

        return $webpPath;
    }

    public function compressExistingFile(string $sourcePath, string $targetPath = null, int $quality = self::DEFAULT_QUALITY): string
    {
        if (!file_exists($sourcePath)) {
            throw new \InvalidArgumentException("Source file does not exist: {$sourcePath}");
        }

        $image = $this->manager->read($sourcePath);

        if ($targetPath === null) {
            $webpPath = $this->convertToWebP($sourcePath);
            $this->processImage($image, $webpPath, $quality);

            if ($webpPath !== $sourcePath) {
                unlink($sourcePath);
            }

            return $webpPath;
        }

        $webpPath = $this->convertToWebP($targetPath);
        $this->processImage($image, $webpPath, $quality);

        return $webpPath;
    }

    private function processImage(ImageInterface $image, string $targetPath, int $quality): void
    {
        if ($image->width() > self::MAX_WIDTH || $image->height() > self::MAX_HEIGHT) {
            $image->scale(width: self::MAX_WIDTH, height: self::MAX_HEIGHT);
        }

        $image->toWebp(quality: $quality)->save($targetPath);
    }

    private function convertToWebP(string $originalPath): string
    {
        $pathInfo = pathinfo($originalPath);
        $dirname = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['filename'];
        return $dirname . '/' . $filename . '.webp';
    }

    public function getCompressedSize(string $filePath): int
    {
        if (!file_exists($filePath)) {
            return 0;
        }

        $size = filesize($filePath);
        return $size !== false ? $size : 0;
    }
}

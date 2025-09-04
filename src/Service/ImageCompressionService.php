<?php

namespace App\Service;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageCompressionService
{
    private const DEFAULT_QUALITY = 85;
    private const MAX_WIDTH = 1920;
    private const MAX_HEIGHT = 1080;
    private readonly ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function compressUploadedFile(UploadedFile $file, string $targetPath, int $quality = self::DEFAULT_QUALITY): string
    {
        $image = $this->manager->read($file->getPathname());

        // Convertir le chemin relatif en chemin absolu si nécessaire
        $fullPath = $this->getAbsolutePath($targetPath);

        // Créer le répertoire si nécessaire
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $webpPath = $this->convertToWebP($fullPath);
        $this->processImage($image, $webpPath, $quality);

        // Retourner le chemin relatif pour la base de données
        return $this->getRelativePath($webpPath);
    }

    public function compressExistingFile(string $sourcePath, ?string $targetPath = null, int $quality = self::DEFAULT_QUALITY): string
    {
        if (!file_exists($sourcePath)) {
            throw new \InvalidArgumentException("Source file does not exist: {$sourcePath}");
        }

        $image = $this->manager->read($sourcePath);

        if (null === $targetPath) {
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

        return $dirname.'/'.$filename.'.webp';
    }

    public function getCompressedSize(string $filePath): int
    {
        if (!file_exists($filePath)) {
            return 0;
        }

        $size = filesize($filePath);

        return false !== $size ? $size : 0;
    }

    private function getAbsolutePath(string $path): string
    {
        // Si le chemin est déjà absolu, le retourner tel quel
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Sinon, le préfixer avec le répertoire public
        $projectRoot = dirname(__DIR__, 2);

        return $projectRoot.'/public/'.$path;
    }

    private function getRelativePath(string $absolutePath): string
    {
        $projectRoot = dirname(__DIR__, 2);
        $publicPath = $projectRoot.'/public/';

        if (str_starts_with($absolutePath, $publicPath)) {
            return substr($absolutePath, strlen($publicPath));
        }

        return $absolutePath;
    }
}

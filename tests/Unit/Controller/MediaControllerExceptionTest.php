<?php

namespace Unit\Controller;

use PHPUnit\Framework\TestCase;

class MediaControllerExceptionTest extends TestCase
{
    public function testFileRequiredExceptionExists(): void
    {
        $controllerFile = __DIR__ . '/../../../src/Controller/Admin/MediaController.php';
        $content = file_get_contents($controllerFile);
        
        $this->assertStringContainsString('throw new \InvalidArgumentException(\'File is required\')', $content);
    }

    public function testExtensionExceptionExists(): void
    {
        $controllerFile = __DIR__ . '/../../../src/Controller/Admin/MediaController.php';
        $content = file_get_contents($controllerFile);
        
        $this->assertStringContainsString('throw new \InvalidArgumentException(\'Could not determine file extension\')', $content);
    }

    public function testUnlinkCallExists(): void
    {
        $controllerFile = __DIR__ . '/../../../src/Controller/Admin/MediaController.php';
        $content = file_get_contents($controllerFile);
        
        $this->assertStringContainsString('unlink($filePath)', $content);
    }
}

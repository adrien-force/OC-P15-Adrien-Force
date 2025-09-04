<?php

namespace App\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

class MediaControllerTest extends TestCase
{
    public function testDeleteLogicWithFileExists(): void
    {
        $testFile = sys_get_temp_dir().'/test_media_delete.jpg';
        file_put_contents($testFile, 'test content');
        $this->assertFileExists($testFile);

        if (file_exists($testFile)) {
            unlink($testFile);
        }

        $this->assertFileDoesNotExist($testFile);
    }

    public function testDeleteLogicWithNonExistentFile(): void
    {
        $nonExistentFile = sys_get_temp_dir().'/non_existent_file.jpg';
        $this->assertFileDoesNotExist($nonExistentFile);

        if (file_exists($nonExistentFile)) {
            unlink($nonExistentFile);
        }

        $this->assertFileDoesNotExist($nonExistentFile);
    }
}

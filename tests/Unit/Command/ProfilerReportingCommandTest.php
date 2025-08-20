<?php

namespace Unit\Command;

use App\Command\ProfilerReportingCommand;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\DataCollector\TimeDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ProfilerReportingCommandTest extends TestCase
{
    private Profiler&MockObject $profiler;
    private ProfilerReportingCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->profiler = $this->createMock(Profiler::class);
        $this->command = new ProfilerReportingCommand($this->profiler);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommandConstruction(): void
    {
        $this->assertEquals('app:profiler:reporting', $this->command->getName());
    }

    public function testCommandConfiguration(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('n'));
        $this->assertTrue($definition->hasOption('s'));
        $this->assertTrue($definition->hasOption('run-test'));
        $this->assertTrue($definition->hasOption('requests'));
        $this->assertTrue($definition->hasOption('concurrency'));
        $this->assertTrue($definition->hasOption('cookie'));
        $this->assertTrue($definition->hasOption('base-url'));
    }

    public function testExecuteWithInvalidTokenNumber(): void
    {
        $this->commandTester->execute([
            '--n' => '0',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Number of tokens must be at least 1', $this->commandTester->getDisplay());
    }

    public function testExecuteWithValidTokenNumberButNoTokens(): void
    {
        $this->profiler->expects($this->exactly(6))
            ->method('find')
            ->willReturn([]);

        $this->commandTester->execute([
            '--n' => '5',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('No tokens found', $this->commandTester->getDisplay());
    }

    public function testExecuteWithTokens(): void
    {
        $mockTokens = [
            [
                'token' => 'test-token-123',
                'time' => time(),
                'ip' => '127.0.0.1',
                'method' => 'GET',
                'url' => '/test',
            ],
        ];

        $mockProfile = $this->createMock(Profile::class);
        $mockProfile->method('hasCollector')->willReturn(true);

        $timeCollector = $this->createMock(TimeDataCollector::class);
        $timeCollector->method('getDuration')->willReturn(150.5);

        $dbCollector = $this->createMock(DoctrineDataCollector::class);
        $dbCollector->method('getTime')->willReturn(0.025);
        $dbCollector->method('getQueryCount')->willReturn(3);
        $dbCollector->method('getManagedEntityCount')->willReturn(5);

        $mockProfile->method('getCollector')->willReturnMap([
            ['time', $timeCollector],
            ['db', $dbCollector],
        ]);

        $this->profiler->expects($this->exactly(6))
            ->method('find')
            ->willReturn($mockTokens);

        $this->profiler->expects($this->exactly(6))
            ->method('loadProfile')
            ->with('test-token-123')
            ->willReturn($mockProfile);

        $this->commandTester->execute([
            '--n' => '1',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('CSV généré', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Rapport global généré', $this->commandTester->getDisplay());
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetBenchmarkOptionsMethod(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->willReturnMap([
            ['run-test', true],
            ['requests', '50'],
            ['concurrency', '2'],
            ['cookie', 'PHPSESSID=test'],
            ['base-url', 'http://localhost:8080'],
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getBenchmarkOptions');

        $result = $method->invoke($this->command, $input);

        $this->assertIsArray($result);
        $this->assertTrue($result['run']);
        $this->assertEquals(50, $result['requests']);
        $this->assertEquals(2, $result['concurrency']);
        $this->assertEquals('PHPSESSID=test', $result['cookie']);
        $this->assertEquals('http://localhost:8080', $result['baseUrl']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetBenchmarkOptionsWithDefaults(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->willReturnMap([
            ['run-test', false],
            ['requests', null],
            ['concurrency', null],
            ['cookie', ''],
            ['base-url', null],
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getBenchmarkOptions');

        $result = $method->invoke($this->command, $input);

        $this->assertFalse($result['run']);
        $this->assertEquals(100, $result['requests']);
        $this->assertEquals(1, $result['concurrency']);
        $this->assertEquals('', $result['cookie']);
        $this->assertNull($result['baseUrl']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testBuildAbCommand(): void
    {
        $options = [
            'requests' => 10,
            'concurrency' => 2,
            'cookie' => 'PHPSESSID=test123',
        ];

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('buildAbCommand');

        $result = $method->invoke($this->command, 'https://example.com/test', $options);

        $this->assertStringContainsString('ab -n 10 -c 2 -v 1', $result);
        $this->assertStringContainsString('-C "PHPSESSID=test123"', $result);
        $this->assertStringContainsString('"https://example.com/test"', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testBuildAbCommandWithoutCookie(): void
    {
        $options = [
            'requests' => 5,
            'concurrency' => 1,
            'cookie' => '',
        ];

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('buildAbCommand');

        $result = $method->invoke($this->command, 'https://example.com/test', $options);

        $this->assertStringContainsString('ab -n 5 -c 1 -v 1', $result);
        $this->assertStringNotContainsString('-C', $result);
        $this->assertStringContainsString('"https://example.com/test"', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGenerateCsvPath(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('generateCsvPath');

        $result = $method->invoke($this->command, '/admin/media', 10, 5);
        $this->assertEquals('var/log/profiler_admin_media_10_5.csv', $result);

        $result = $method->invoke($this->command, '/', 10, 5);
        $this->assertEquals('var/log/profiler_root_10_5.csv', $result);

        $result = $method->invoke($this->command, '/test/with-special@chars!', 10, 5);
        $this->assertEquals('var/log/profiler_test_with-special_chars__10_5.csv', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetSafeSheetName(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getSafeSheetName');

        $result = $method->invoke($this->command, '/admin/media');
        $this->assertEquals('admin_media', $result);

        $result = $method->invoke($this->command, '/');
        $this->assertEquals('root', $result);

        $result = $method->invoke($this->command, '/very/long/route/name/that/exceeds/thirty/one/characters/limit');
        $this->assertEquals('very_long_route_name_that_excee', $result);
        $this->assertLessThanOrEqual(31, strlen($result));
    }

    /**
     * @throws \ReflectionException
     */
    public function testCalculateAverage(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('calculateAverage');

        $result = $method->invoke($this->command, [10.0, 20.0, 30.0]);
        $this->assertEquals(20.0, $result);

        $result = $method->invoke($this->command, []);
        $this->assertEquals(0.0, $result);

        $result = $method->invoke($this->command, [5.5]);
        $this->assertEquals(5.5, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCalculateStandardDeviation(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('calculateStandardDeviation');

        $result = $method->invoke($this->command, [10.0, 20.0, 30.0]);
        $this->assertGreaterThan(0, $result);
        $this->assertEqualsWithDelta(8.165, $result, 0.01);

        $result = $method->invoke($this->command, []);
        $this->assertEquals(0.0, $result);

        $result = $method->invoke($this->command, [5.0]);
        $this->assertEquals(0.0, $result);

        $result = $method->invoke($this->command, [10.0, 10.0, 10.0]);
        $this->assertEquals(0.0, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCollectProfilerDataWithNoProfile(): void
    {
        $tokens = [
            [
                'token' => 'invalid-token',
                'time' => time(),
                'ip' => '192.168.1.1',
                'method' => 'POST',
                'url' => '/submit',
            ],
        ];

        $this->profiler->method('loadProfile')
            ->with('invalid-token')
            ->willReturn(null);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('collectProfilerData');

        $result = $method->invoke($this->command, $tokens);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('N/A', $result[0][1]); // DB time
        $this->assertEquals('N/A', $result[0][2]); // Render time
        $this->assertEquals('invalid-token', $result[0][3]); // Token
        $this->assertEquals('192.168.1.1', $result[0][4]); // IP
        $this->assertEquals('POST', $result[0][5]); // Method
        $this->assertEquals('/submit', $result[0][6]); // URL
    }

    /**
     * @throws \ReflectionException
     */
    public function testCollectProfilerDataWithCollectors(): void
    {
        $tokens = [
            [
                'token' => 'valid-token',
                'time' => 1640995200, // 2022-01-01 00:00:00
                'ip' => '127.0.0.1',
                'method' => 'GET',
                'url' => '/api/test',
            ],
        ];

        $mockProfile = $this->createMock(Profile::class);
        $mockProfile->method('hasCollector')->willReturnMap([
            ['db', true],
            ['time', true],
        ]);

        $timeCollector = $this->createMock(TimeDataCollector::class);
        $timeCollector->method('getDuration')->willReturn(250.75);

        $dbCollector = $this->createMock(DoctrineDataCollector::class);
        $dbCollector->method('getTime')->willReturn(0.055); // 55ms
        $dbCollector->method('getQueryCount')->willReturn(7);
        $dbCollector->method('getManagedEntityCount')->willReturn(12);

        $mockProfile->method('getCollector')->willReturnMap([
            ['time', $timeCollector],
            ['db', $dbCollector],
        ]);

        $this->profiler->method('loadProfile')
            ->with('valid-token')
            ->willReturn($mockProfile);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('collectProfilerData');

        $result = $method->invoke($this->command, $tokens);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('2022-01-01 00:00:00', $result[0][0]); // Time formatted
        $this->assertEquals('55.00', $result[0][1]); // DB time in ms
        $this->assertEquals('250.75', $result[0][2]); // Render time
        $this->assertEquals('valid-token', $result[0][3]); // Token
        $this->assertEquals('127.0.0.1', $result[0][4]); // IP
        $this->assertEquals('GET', $result[0][5]); // Method
        $this->assertEquals('/api/test', $result[0][6]); // URL
        $this->assertEquals(7, $result[0][7]); // Query count
        $this->assertEquals(12, $result[0][8]); // Managed entities
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetHeaders(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getHeaders');

        $result = $method->invoke($this->command);

        $expectedHeaders = [
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

        $this->assertEquals($expectedHeaders, $result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $csvFiles = glob('var/log/profiler_*.csv');
        foreach ($csvFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $xlsxFiles = glob('var/log/profiler_global_report_*.xlsx');
        foreach ($xlsxFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}

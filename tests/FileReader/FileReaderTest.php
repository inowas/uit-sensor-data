<?php

declare(strict_types=1);

namespace Inowas\Sensor;

use Inowas\SensorData\FileReader\FileReader;
use PHPUnit\Framework\TestCase;

final class FileReaderTest extends TestCase
{

    public function testASimpleTestCase(): void
    {
        $this->assertTrue(true);
        $this->assertFalse(false);
    }

    public function testListSensorFiles(): void
    {
        $fileReader = new FileReader();
        $files = $fileReader->listSensorFiles(__DIR__ . '/../files');

        $this->assertIsArray($files);
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('tests/files/DEU1_I-3_190606140024.csv', $files[0]);
    }

    public function testReadValidSensorFile(): void
    {
        $fileReader = new FileReader();
        $sensorFile = $fileReader->listSensorFiles(__DIR__ . '/../files')[0];
        $fileReader->readSensorValuesFromFile($sensorFile);
    }

    public function testReadNotExistentFile(): void
    {
        $path = 'sdkljfjh';
        $this->expectExceptionMessage('Sensor file ' . $path . ' not found');
        $fileReader = new FileReader();
        $fileReader->readSensorValuesFromFile($path);
    }
}

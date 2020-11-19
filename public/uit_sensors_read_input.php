<?php

use Inowas\SensorData\FileReader\FileReader;
use Inowas\SensorData\Sensor\Sensor;
use Inowas\SensorData\Sensor\SensorValue;
use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/../bootstrap.php';

$dataPath = __DIR__ . '/../sensoweb';
$archivePath = __DIR__ . '/../archive';
$processedPath = __DIR__ . '/../processed';

$filesystem = new Filesystem();
if (!$filesystem->exists($dataPath)) {
    $filesystem->mkdir($dataPath);
}

if (!$filesystem->exists($archivePath)) {
    $filesystem->mkdir($archivePath);
}

if (!$filesystem->exists($processedPath)) {
    $filesystem->mkdir($processedPath);
}

$fileReader = new FileReader();
$validFiles = $fileReader->listSensorFiles($dataPath);


foreach ($validFiles as $filename) {
    $info = explode('_', explode('.', basename($filename))[0]);
    [$projectId, $sensorId, $dateTime] = $info;

    /** @var Sensor $sensor */
    $sensor = $entityManager->getRepository(Sensor::class)->findOneBy([
        'project' => $projectId,
        'name' => $sensorId
    ]);

    if (!$sensor instanceof Sensor) {
        try {
            $sensor = new Sensor($projectId, $sensorId, '');
        } catch (Exception $e) {
        }
    }

    /** @var SensorValue[] $sensorValues */
    try {
        $sensorValues = $fileReader->readSensorValuesFromFile($filename);
        foreach ($sensorValues as $sensorValue) {
            $sensor->addValue($sensorValue);
        }

        $entityManager->persist($sensor);
        $entityManager->flush();

    } catch (\Inowas\SensorData\Exception\InvalidArgumentException $e) {
    } catch (\League\Csv\Exception $e) {
    } catch (Exception $e) {
    }

    $filesystem->copy(
        $dataPath . '/' . basename($filename),
        $processedPath . '/' . basename($filename),
    );

    $filesystem->rename(
        $dataPath . '/' . basename($filename),
        $archivePath . '/' . basename($filename),
    );
}

echo 'DONE!';

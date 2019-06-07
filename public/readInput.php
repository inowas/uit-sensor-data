<?php

use Inowas\SensorData\FileReader\FileReader;
use Inowas\SensorData\Sensor\Sensor;
use Inowas\SensorData\Sensor\SensorValue;
use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/../bootstrap.php';


$dataPath = __DIR__ . '/../sensoweb';
$archivePath = __DIR__ . '/../sensoweb/archive';

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
        $sensor = new Sensor($projectId, $sensorId, '');
    }

    /** @var SensorValue[] $sensorValues */
    $sensorValues = $fileReader->readSensorValuesFromFile($filename);

    foreach ($sensorValues as $sensorValue) {
        $sensor->addValue($sensorValue);
    }

    $entityManager->persist($sensor);
    $entityManager->flush();

    $filesystem = new Filesystem();

    $filesystem->rename(
        $dataPath . '/' . basename($filename),
        $archivePath . '/' . basename($filename),
        );
}

echo 'DONE!';

<?php

use Inowas\SensorData\FileReader\FileReader;
use Inowas\SensorData\Sensor\Sensor;
use Inowas\SensorData\Sensor\SensorValue;
use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/../bootstrap.php';


$dataPath = __DIR__ . '/../sensoweb';
$archivePath = __DIR__ . '/../archive';

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

$stations = [
    [
        'projectId' => 'DEU1',
        'sensorId' => 'WL-ELBE-PIRNA',
        'url' => 'https://www.pegelonline.wsv.de/webservices/rest-api/v2/stations/PIRNA/W.json?includeCurrentMeasurement=true'
    ]
];

foreach ($stations as $station) {
    $response = json_decode(file_get_contents($station['url']), true);
    $dateTime = new DateTime($response['currentMeasurement']['timestamp']);
    $waterLevel = $response['currentMeasurement']['value'];
    $gaugeZero = $response['gaugeZero']['value'];

    /** @var Sensor $sensor */
    $sensor = $entityManager->getRepository(Sensor::class)->findOneBy([
        'project' => $station['projectId'],
        'name' => $station['sensorId']
    ]);

    if (!$sensor instanceof Sensor) {
        $sensor = new Sensor($station['projectId'], $station['sensorId'], '');
    }

    $sensorValue = new SensorValue($dateTime, ['waterLevel' => $waterLevel, 'gaugeZero' => $gaugeZero]);
    $sensor->addValue($sensorValue);

    $entityManager->persist($sensor);
    $entityManager->flush();
}

echo 'DONE!';

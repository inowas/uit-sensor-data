<?php

declare(strict_types=1);

use Inowas\SensorData\Sensor\Sensor;
use Inowas\SensorData\Sensor\SensorValue;
use OpenMetricsPhp\Exposition\Text\Collections\GaugeCollection;
use OpenMetricsPhp\Exposition\Text\Collections\LabelCollection;
use OpenMetricsPhp\Exposition\Text\Metrics\Gauge;
use OpenMetricsPhp\Exposition\Text\Types\MetricName;
use OpenMetricsPhp\Exposition\Text\HttpResponse;


require_once __DIR__ . '/../bootstrap.php';

$withTimeStamp = (int)$_GET['withTimeStamp'] === 1;


$sensors = $entityManager->getRepository(Sensor::class)->findAll();
$gauges = GaugeCollection::withMetricName(
    MetricName::fromString('uit_sensors'),
    )->withHelp('A collection of all uit-sensors in the smartcontrol project.');


/** @var Sensor $sensor */
foreach ($sensors as $sensor) {
    $name = $sensor->name();
    $project = $sensor->project();
    $location = $sensor->location();

    /** @var SensorValue $sensorValue */
    $sensorValue = $sensor->values()->last();

    $dateTime = $sensorValue->dateTime()->format(DateTime::ATOM);
    foreach ($sensorValue->data() as $type => $value) {

        $labels = [
            'project_id' => $project,
            'sensor_id' => $name,
            'type' => $type
        ];

        if ($location !== null && $location !== '') {
            $labels['geohash'] = $location;
        }

        if ($withTimeStamp) {
            $gauges->add(
                Gauge::fromValueAndTimestamp(
                    $value,
                    $sensorValue->dateTime()->getTimestamp() * 1000
                )->withLabelCollection(
                    LabelCollection::fromAssocArray($labels)
                )
            );
        }

        if (!$withTimeStamp) {
            $gauges->add(
                Gauge::fromValue($value)->withLabelCollection(
                    LabelCollection::fromAssocArray($labels)
                )
            );
        }
    }
}

$httpResponse = HttpResponse::fromMetricCollections($gauges);
$httpResponse->withHeader('Content-Type', 'text/plain; charset=utf-8');
$httpResponse->respond();

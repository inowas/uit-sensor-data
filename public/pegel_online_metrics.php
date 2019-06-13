<?php

declare(strict_types=1);

use OpenMetricsPhp\Exposition\Text\Collections\GaugeCollection;
use OpenMetricsPhp\Exposition\Text\Collections\LabelCollection;
use OpenMetricsPhp\Exposition\Text\Metrics\Gauge;
use OpenMetricsPhp\Exposition\Text\Types\MetricName;
use OpenMetricsPhp\Exposition\Text\HttpResponse;

require_once __DIR__ . '/../bootstrap.php';

$withTimeStamp = (int)($_GET['withTimeStamp'] ?? 1);
$stations = $_GET['stations'] ?? [];

if (!is_array($stations)) {
    $stations = explode(',', $stations);
}

$gauges = GaugeCollection::withMetricName(
    MetricName::fromString('pegel_online_wsv_sensors')
)->withHelp('Pegeldata from pegelonline.wsv.de.');

foreach ($stations as $station) {
    $url = sprintf('https://www.pegelonline.wsv.de/webservices/rest-api/v2/stations/%s/W.json?includeCurrentMeasurement=true', $station);
    $content = file_get_contents($url);
    if ($content === false) {
        continue;
    }

    $response = json_decode($content, true);
    $dateTime = new DateTime($response['currentMeasurement']['timestamp']);
    $waterLevel = $response['currentMeasurement']['value'];
    $gaugeZero = $response['gaugeZero']['value'];

    if ($withTimeStamp) {
        $gauges->add(
            Gauge::fromValueAndTimestamp(
                $waterLevel, $dateTime->getTimestamp() * 1000
            )->withLabelCollection(LabelCollection::fromAssocArray(['station' => $station, 'type' => 'waterlevel']))
        );

        $gauges->add(
            Gauge::fromValueAndTimestamp(
                $gaugeZero, $dateTime->getTimestamp() * 1000
            )->withLabelCollection(LabelCollection::fromAssocArray(['station' => $station, 'type' => 'gaugeZero']))
        );
    }

    if (!$withTimeStamp) {
        $gauges->add(
            Gauge::fromValue($waterLevel)->withLabelCollection(
                LabelCollection::fromAssocArray(['station' => $station, 'type' => 'waterlevel'])
            )
        );

        $gauges->add(
            Gauge::fromValue($waterLevel)->withLabelCollection(
                LabelCollection::fromAssocArray(['station' => $station, 'type' => 'gaugeZero'])
            )
        );
    }
}

$httpResponse = HttpResponse::fromMetricCollections($gauges);
$httpResponse->withHeader('Content-Type', 'text/plain; charset=utf-8');
$httpResponse->respond();

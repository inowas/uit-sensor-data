<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use Inowas\SensorData\Sensor\Sensor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$request = Request::createFromGlobals();

$uriParams = explode('/', $request->getPathInfo());
$projectName = count($uriParams) > 2 ? $uriParams[2] : null;
$sensorName = count($uriParams) > 4 ? $uriParams[4] : null;
$parameter = count($uriParams) > 6 ? $uriParams[6] : null;

$response = new Response();
$response->headers->set('Access-Control-Allow-Headers', '*');
$response->headers->set('Access-Control-Allow-Origin', '*');
$response->headers->set('Access-Control-Allow-Methods', '*');
$response->headers->set('Content-Type', 'application/json');
$response->setStatusCode(Response::HTTP_OK);
$response->setCharset('UTF8');

if ($projectName === null || $sensorName === null) {
    $sensors = $entityManager->getRepository(Sensor::class)->findAll();
    $result = [];

    /** @var Sensor $sensorName */
    foreach ($sensors as $sensorName) {
        $result[] = [
            'name' => $sensorName->name(),
            'location' => $sensorName->location(),
            'project' => $sensorName->project(),
            'properties' => $sensorName->properties(),
            'last' => $sensorName->values()->last(),
        ];
    }

    $response->setContent(json_encode($result, JSON_THROW_ON_ERROR, 512));
    $response->setStatusCode(Response::HTTP_OK);
    $response->send();
    return;
}

if ($sensorName && $projectName) {
    /** @var Sensor $sensor */
    $sensor = $entityManager->getRepository(Sensor::class)->findOneBy(['name' => $sensorName, 'project' => $projectName]);
    if (!$sensor) {
        $response->setStatusCode(Response::HTTP_NOT_FOUND);
        $response->send();
        return;
    }

    $begin = $request->query->get('begin') !== false ? (int)$request->query->get('begin') : null;
    $end = $request->query->get('end') !== false ? (int)$request->query->get('end') : null;
    $min = $request->query->get('min') !== false ? (float)$request->query->get('min') : null;
    $max = $request->query->get('max') !== false ? (float)$request->query->get('max') : null;
    $timeResolution = $request->query->get('timeResolution');

    /** @var $parameter string|null */
    if ($parameter) {
        $response->setContent(json_encode($sensor->getParameterData($parameter, $begin, $end, $min, $max, $timeResolution), JSON_THROW_ON_ERROR, 512));
        $response->send();
        return;
    }

    $response->setContent(json_encode($sensor, JSON_THROW_ON_ERROR, 512));
    $response->send();
    return;
}

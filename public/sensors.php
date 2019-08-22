<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Inowas\SensorData\Sensor\Sensor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$request = Request::createFromGlobals();

$uriParams = explode('/', $request->server->get('PATH_INFO'));
$projectName = count($uriParams) > 3 ? $uriParams[3] : null;
$sensorName = count($uriParams) > 5 ? $uriParams[5] : null;
$property = count($uriParams) > 7 ? $uriParams[7] : null;


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

    $response = new Response();
    $response->setContent(json_encode($result));
    $response->setStatusCode(Response::HTTP_OK);
    $response->headers->set('Content-Type', 'application/json');
    $response->setCharset('UTF8');
    $response->send();
    return;
}

if ($sensorName && $projectName) {
    /** @var Sensor $sensor */
    $sensor = $entityManager->getRepository(Sensor::class)->findOneBy(['name' => $sensorName, 'project' => $projectName]);
    if (!$sensor) {
        $response = new Response();
        $response->setStatusCode(Response::HTTP_NOT_FOUND);
        $response->headers->set('Content-Type', 'application/json');
        $response->setCharset('UTF8');
        $response->send();
        return;
    }

    $begin = $request->query->get('begin') ? (int)$request->query->get('begin') : null;
    $end = $request->query->get('end') ? (int)$request->query->get('end') : null;

    /** @var $property string|null */
    if ($property) {
        $response = new Response();
        $response->setContent(json_encode($sensor->getPropertyData($property, $begin, $end)));
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/json');
        $response->setCharset('UTF8');
        $response->send();
        return;
    }

    $response = new Response();
    $response->setContent(json_encode($sensor));
    $response->setStatusCode(Response::HTTP_OK);
    $response->headers->set('Content-Type', 'application/json');
    $response->setCharset('UTF8');
    $response->send();
    return;
}

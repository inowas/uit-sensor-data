<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Inowas\SensorData\Sensor\Sensor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$request = Request::createFromGlobals();

$sensorName = $request->query->get('name');

if ($sensorName === null) {
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

if ($sensorName) {
    /** @var Sensor $sensor */
    $sensor = $entityManager->getRepository(Sensor::class)->findOneBy(['name' => $sensorName]);
    if (!$sensor) {
        $response = new Response();
        $response->setStatusCode(Response::HTTP_NOT_FOUND);
        $response->headers->set('Content-Type', 'application/json');
        $response->setCharset('UTF8');
        $response->send();
        return;
    }

    $property = $request->query->get('property');
    $begin = $request->query->get('begin') ? (int)$request->query->get('begin') : null;
    $end = $request->query->get('end') ? (int)$request->query->get('end') : null;

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

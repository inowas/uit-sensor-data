<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use Inowas\SensorData\Sensor\Sensor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$request = Request::createFromGlobals();

$response = new Response();
$response->headers->set('Access-Control-Allow-Headers', '*');
$response->headers->set('Access-Control-Allow-Origin', '*');
$response->headers->set('Access-Control-Allow-Methods', '*');
$response->headers->set('Content-Type', 'application/json');
$response->setStatusCode(Response::HTTP_OK);
$response->setCharset('UTF8');

$sensors = $entityManager->getRepository(Sensor::class)->findAll();
$exportData = [];
$exportData[] = '# DDL';
$exportData[] = '';
$exportData[] = 'CREATE DATABASE SENSOWEB_database';
$exportData[] = '';
$exportData[] = '# DML';
$exportData[] = '';
$exportData[] = '# CONTEXT-DATABASE: SENSOWEB_database';
$exportData[] = '';

foreach ($sensors as $sensor) {
    /** @var string $parameter */
    foreach ($sensor->properties() as $parameter) {
        $dateTimeValues = $sensor->getParameterData($parameter);
        foreach ($dateTimeValues as $dateTimeValue) {
            $dateTime = (new DateTime($dateTimeValue['date_time']))->getTimestamp();
            $name = str_replace(' ', '', $sensor->name());
            $exportData[] = sprintf('sensors,project=%s,name=%s %s=%s %s', $sensor->project(), $name, $parameter, $dateTimeValue[$parameter], $dateTime);
        }
    }
}

$response->setContent(implode("\n", $exportData));
$response->send();

<?php

declare(strict_types=1);

namespace Inowas\SensorData\FileReader;

use DateTime;
use Exception;
use Inowas\SensorData\Exception\InvalidArgumentException;
use Inowas\SensorData\Sensor\SensorValue;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Component\Finder\Finder;

class FileReader
{
    public function listSensorFiles(string $path): array
    {
        $finder = new Finder();
        $finder->files()->in($path);

        $list = [];
        foreach ($finder as $file) {
            $filename = $file->getFilenameWithoutExtension();

            /**
             * Expecting file format:
             *  * PROJID_MEASURINGPOINTID_DATETIMESTR.csv
             *  * DEU1_I-3_190606140024.csv
             */
            $metadata = explode('_', $filename);
            if (count($metadata) !== 3) {
                continue;
            }

            $list[] = $file->getRealPath();
        }

        return $list;
    }

    /**
     * @param string $path
     * @return array
     * @throws InvalidArgumentException
     * @throws \League\Csv\Exception
     * @throws Exception
     */
    public function readSensorValuesFromFile(string $path): array
    {
        try {
            $csv = Reader::createFromPath($path);
        } catch (Exception $e) {
            throw new InvalidArgumentException(sprintf('Sensor file %s not found', $path));
        }

        $parts = explode('/', $path);
        $filename = $parts[count($parts) - 1];
        $metadata = explode('_', $filename);
        [$projectId, $measuringPointId] = $metadata;

        $csv->setHeaderOffset(0);
        $csv->setDelimiter(';');

        $stmt = (new Statement())->offset(0);
        $records = $stmt->process($csv);

        $sensorValues = [];
        foreach ($records as $record) {
            $sensorValues[] = $this->parseRecord($record);
        }

        return $sensorValues;
    }

    /**
     * @param $record
     * @return SensorValue
     * @throws Exception
     */
    private function parseRecord(array $record): SensorValue
    {
        $dateTime = null;
        $values = [];

        $firstColumn = true;
        foreach ($record as $head => $value) {
            if ($firstColumn) {
                $dateTime = (new DateTime($value))->setTimezone(new \DateTimeZone('Europe/Berlin'));
                $firstColumn = false;
            }

            preg_match_all("/\{([^\]]*)\}/", $head, $matches);
            if (count($matches[1]) > 0) {
                $property = $matches[1][0];
                $values[$property] = (float)$value;
            }
        }

        return new SensorValue($dateTime, $values);
    }
}

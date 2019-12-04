<?php

declare(strict_types=1);

namespace Inowas\SensorData\Sensor;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(name="sensors")
 **/
class Sensor implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=36)
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="project", type="string")
     */
    private $project;

    /**
     * @var $name
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="location", type="string")
     */
    private $location;

    /**
     * @var ArrayCollection $values
     * @ORM\ManyToMany(targetEntity="SensorValue", cascade={"persist"})
     * @ORM\JoinTable(name="sensors_sensorvalues",
     *     joinColumns={@ORM\JoinColumn(name="sensor_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="sensorvalue_key", referencedColumnName="key", unique=true)}
     * )
     */
    private $values;

    /**
     * Sensor constructor.
     * @param string $project
     * @param string $name
     * @param string $location
     * @throws Exception
     */
    public function __construct(string $project, string $name, string $location = '')
    {
        $this->id = Uuid::uuid4()->toString();
        $this->project = $project;
        $this->name = $name;
        $this->location = $location;
        $this->values = new ArrayCollection();
    }

    public function project(): string
    {
        return $this->project;
    }

    public function name(): string
    {
        return $this->name;

    }

    public function location(): string
    {
        return $this->location;
    }

    public function addValue(SensorValue $value): self
    {
        $this->values->add($value);
        return $this;
    }

    public function values(): Collection
    {
        return $this->values;
    }

    public function properties(): array
    {
        /** @var SensorValue $sensorValue */
        $sensorValue = $this->values->last();
        if ($sensorValue instanceof SensorValue) {
            return array_keys($sensorValue->data());
        }
        return [];
    }

    /**
     * @param string $parameter
     * @param int|null $tsBegin
     * @param int|null $tsEnd
     * @param float|null $minValue
     * @param float|null $maxValue
     * @param null $timeResolution
     * @return array
     * @throws Exception
     */
    public function getParameterData(string $parameter, int $tsBegin = null, int $tsEnd = null, float $minValue = null, float $maxValue = null, $timeResolution = null): array
    {
        $data = [];
        /** @var SensorValue $value */
        foreach ($this->values as $value) {

            $dateTime = $value->dateTime();
            if ($tsBegin !== null && ($dateTime < new DateTime('@' . $tsBegin))) {
                continue;
            }

            if ($tsEnd !== null && ($dateTime > new DateTime('@' . $tsEnd))) {
                continue;
            }

            if (!array_key_exists($parameter, $value->data())) {
                continue;
            }

            $value = $value->data()[$parameter];
            if ($minValue !== null && $value < $minValue) {
                continue;
            }

            if ($maxValue !== null && $value > $maxValue) {
                continue;
            }

            $data[] = [
                'date_time' => $dateTime,
                $parameter => $value
            ];
        }

        if ($timeResolution !== null) {
            $data = $this->applyTimeResolution($data, $parameter, $timeResolution);
        }

        usort($data, static function ($a, $b) {
            return $a['date_time'] > $b['date_time'];
        });

        $data = array_map(static function ($a) {
            /** @var DateTime $dateTime */
            $dateTime = $a['date_time'];
            $a['date_time'] = $dateTime->format(DATE_ATOM);
            return $a;
        }, $data);

        return $data;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'project' => $this->project,
            'location' => $this->location,
            'values' => $this->values->toArray()
        ];
    }

    /**
     * @param array $data
     * @param string $parameter
     * @param string|null $timeResolution
     * @return array
     * @throws Exception
     */
    private function applyTimeResolution(array $data, string $parameter, string $timeResolution = null): array
    {
        if ($timeResolution === null) {
            return $data;
        }

        foreach ($data as &$dataSet) {
            /** @var DateTime $dateTime */
            $dateTime = $dataSet['date_time'];

            switch ($timeResolution) {
                case '1D':
                    $dateTime->setTime(0, 0);
                    break;
                case '1H':
                    $dateTime->setTime((int)$dateTime->format('H'), 0);
                    break;
            }

            $dataSet['date_time'] = $dateTime;
        }
        unset($dataSet);

        $timeStampValues = [];
        foreach ($data as $dataSet) {
            /** @var DateTime $dateTime */
            $dateTime = $dataSet['date_time'];
            $timeStamp = $dateTime->getTimestamp();

            if (!array_key_exists($timeStamp, $timeStampValues)) {
                $timeStampValues[$timeStamp] = [];
            }

            $timeStampValues[$timeStamp][] = $dataSet[$parameter];
        }

        if (count($data) > count($timeStampValues)) {
            $newData = [];
            foreach ($timeStampValues as $timeStamp => $values) {
                $newData[] = [
                    'date_time' => (new DateTime())->setTimestamp($timeStamp),
                    $parameter => array_sum($values) / count($values)
                ];
            }

            $data = $newData;
        }

        return $data;
    }
}

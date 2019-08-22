<?php

declare(strict_types=1);

namespace Inowas\SensorData\Sensor;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(name="sensors")
 **/
class Sensor implements \JsonSerializable
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

    public function getPropertyData(string $property, int $tsBegin = null, int $tsEnd = null): array
    {
        $data = [];
        /** @var SensorValue $value */
        foreach ($this->values as $value) {
            if ($tsBegin === null || $value->dateTime() >= new DateTime('@' . $tsBegin)) {
                if ($tsEnd === null || $value->dateTime() <= new DateTime('@' . $tsEnd)) {
                    $dateTime = $value->dateTime();
                    $propertyData = null;
                    if (array_key_exists($property, $value->data())) {
                        $propertyData = $value->data()[$property];
                    }

                    $data[] = [
                        'date_time' => $dateTime,
                        $property => $propertyData
                    ];
                }

            }
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
}

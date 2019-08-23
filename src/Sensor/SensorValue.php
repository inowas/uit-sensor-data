<?php

declare(strict_types=1);


namespace Inowas\SensorData\Sensor;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity
 * @ORM\Table(name="sensor_values")
 **/
class SensorValue implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="key", type="integer")
     * @ORM\GeneratedValue()
     */
    protected $key;

    /**
     * @var DateTime $dateTime
     * @ORM\Column(type="datetime")
     */
    protected $dateTime;

    /**
     * @var array $data
     * @ORM\Column(type="array")
     */
    private $data;

    /**
     * SensorValue constructor.
     * @param DateTime $dateTime
     * @param array $data
     */
    public function __construct(DateTime $dateTime, array $data)
    {
        $this->dateTime = $dateTime;
        $this->data = $data;
    }

    public function dateTime(): DateTime
    {
        return $this->dateTime;
    }

    public function data(): array
    {
        return $this->data;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return [
            'date_time' => $this->dateTime->format(DATE_ATOM),
            'data' => $this->data()
        ];
    }
}

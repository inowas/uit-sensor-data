<?php

declare(strict_types=1);


namespace Inowas\SensorData\Sensor;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="sensor_values")
 **/
class SensorValue
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
}

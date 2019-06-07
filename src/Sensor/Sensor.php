<?php

declare(strict_types=1);

namespace Inowas\SensorData\Sensor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(name="sensors")
 **/
class Sensor
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
}

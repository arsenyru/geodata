<?php

namespace Geodata\Model\GeoData;

use Geodata\ORM\Type\Point;

// check supported type point

trait GeoData
{
    /**
     * @ORM\Column(type="point", nullable=true)
     */
    protected $location;

    /**
     * Get location.
     *
     * @return location.
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set location.
     *
     * @param location the value to set.
     */
    public function setLocation(Point $location)
    {
        $this->location = $location;
    }
}

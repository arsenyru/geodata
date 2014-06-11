<?php

namespace Geodata\ORM\Type;

class LocPoint
{
    private $base;	 	// base point
	private $azimuth;	// азимут
	private $distance;	// расстояние

	public function __construct ()
    {
    }
	
	// загрузить данные - координаты базовой точки, азимут, расстояние
	// returns 0 - all right
	// returns error code:
	// 0xCDEABXYZ - ABXYZ: see setFromLLH docs in Point.php
	// D=1 - azimuth not float
	// E=1 - distance not float
	// C=1 - azimuth not in [0;360] degrees
	
    public function setFromLLHAD ($latitude, $longitude, $height, $azimuth, $distance)
    {
		$this->base = new Point();
        $mask = $this->base->setFromLLH($latitude, $longitude, $height);
		if (is_numeric($azimuth)) {
			if ($azimuth>=0 && $azimuth<=360)
				$this->azimuth= $azimuth;
			else 
				$mask = $mask | 1<<7;
		}
		else
			$mask = $mask | 1<<6;
		if (is_numeric($distance))
			$this->distance = $distance;
		else
			$mask = $mask | 1<<5;
		return $mask;
    }
	
	// загрузить данные - базовая точка, азимут , расстояние
    public function setFromPAD(Point $p, $az, $distance)
    {
        $this->base = $p;
		$this->azimuth= $az;
		$this->distance = $distance;
    }	
		
	
	// на выходе: точка формата Point - координаты в абсолюте
    public function asPoint()
    {
		$thisPoint = new Point();
		$thisPoint->setFromPAD($this->base, $this->azimuth, $this->distance);
		return $thisPoint;
    }	
	
	// получить из массива - ассациативного или обычного
    public static function fromArray(array $array)
    {
        if (isset($array['latitude'])) {
			$pf = new self();
			$pf->setFromLLHAD($array['latitude'], $array['longitude'], $array['height'], $array['azimuth'], $array['distance']);
            return $pf;
        } else {
			$pf = new self();
			$pf->setFromLLHAD($array[0], $array[1], $array[2], $array[3], $array[4]);
            return $pf;
        }
    }

    public static function fromString($string)
    {
        return self::fromArray(sscanf($string, '(%f,%f,%f,%f,%f)'));
    }

    public function getBase()
    {
        return $this->base;
    }

    public function getAzimuth()
    {
        return $this->azimuth;
    }


    public function getDistance()
    {
        return $this->distance;
    }
	
	public function setBase($base) {
		$this->base = $base;
		return $this;
	}
	
	public function setAzimuth($azimuth) {
		$this->azimuth = $azimuth;
		return $this;
	}
	
	public function setDistance($distance) {
		$this->distance = $distance;
		return $this;
	}	
	
	
    public function __toString()
    {
        return sprintf('(%F,%F,%F,%F,%F)', $this->base->getLatitude(), $this->base->getLongitude(), $this->base->getHeight(), $this->azimuth, $this->distance);
    }

    public function isEmpty()
    {
        return empty($this->base) && empty($this->azimuth) && empty($this->distance);
    }
	
	// Получить дистанцию до другой точки в формате Point
	public function getDistanceToPoint(Point $point2) {
		$asPoint = $this->asPoint();
		return $asPoint->getDistanceToPoint($point2);
	}
	
	// Получить дистанцию до другой точки в формате LocPoint
	public function getDistanceToLocPoint(LocPoint $point2) {
	
		$asPoint = $point2->asPoint();
		return $this->getDistanceToPoint($asPoint);
	}

	// Получить азимут до другой точки в формате Point
	public function getAzimuthToPoint(Point $point2) {
		$asPoint = $this->asPoint();
		return $asPoint->getAzimuthToPoint($point2);
	}
	
	// Получить азимут до другой точки в формате LocPoint
	public function getAzimuthToLocPoint(LocPoint $point2) {
		$asPoint = $point2->asPoint();
		return $this->getAzimuthToPoint($asPoint);
	}
}
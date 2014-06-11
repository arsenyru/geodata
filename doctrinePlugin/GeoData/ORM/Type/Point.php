<?php

namespace Geodata\ORM\Type;

// Радиус земли
define('EARTH_RADIUS', 6371302);

class Point
{
    private $latitude; // широта
    private $longitude; // долгота
	private $height; // высота
	
	public function __construct ()
    {
    }
	
	// заполнить данные из широты, долготы и высоты
	// returns 0 - all right
	// returns error code:
	// 0xABXYZ - X=1 if latitude not float, Y=1 if longitude not float, Z=1 if longitude not float W=1
	// A = 1 if latitude <-360 or >360
	// B = 1 if longitude > 360 or <-360
	
    public function setFromLLH ($latitude, $longitude, $height)
    {
		$mask = 0;
		
		if (is_numeric($latitude))
			if ($latitude>-360 && $latitude<360)
				$this->latitude  = $latitude;
			else
				$mask = $mask | 1<<4;
		else
			$mask = $mask | 1<<2;
		if (is_numeric($longitude))
			if ($longitude>-360 && $longitude<360)
				$this->longitude = $longitude;
			else
				$mask = $mask | 1<<3;
		else
			$mask = $mask | 1<<1;
		if (is_numeric($height))
			$this->height = $height;
		else
			$mask = $mask | 1;
		return $mask;
    }
	
	// заполнить данные из базовой точки, азимута и дистанции
    public function setFromPAD (Point $p, $az, $dist)
    {
		$this->getGeoCoord ($p->getLatitude(), $p->getLongitude(), $p->getHeight(), $az, $dist);
    }	
	
	// заполнить данные из LocPoint
	public function setFromLocPoint(LocPoint $a) {
		$this->setFromPAD($a->getBase(), $a->getAzimuth(), $a->getDistance());
	}
	
	// заполнить данные из координат базовой точки, азимута и дистанции
    public function setFromLLHAD ($lat, $long, $height, $az, $dist)
    {
		$this->getGeoCoord ($lat, $long, $height, $az, $dist);
    }		
	
	// получить точку в координатах относительно $point2;
	public function asLocPoint (Point $point2) {
		$azimut = $point2->getAzimutToPoint($this);
		$dist = $point2->getDistanceToPoint($this);
		
		$pointAsLoc = new LocPoint();
		$pointAsLoc->setFromPAD ($point2, $azimut, $dist);
		return $pointAsLoc;
	}
	
	// наоборот - получить точку Point2 в координатах относительно текущей
	public function getLocPoint (Point $point2) {
		$azimut = $this->getAzimutToPoint($point2);
		$dist = $this->getDistanceToPoint($point2);
		
		$pointLoc = new LocPoint();
		$pointLoc->setFromPAD ($this, $azimut, $dist);
		return $pointLoc;
	}	
	
	// получить геокоординаты из системы, связанной с другой точкой
    public function getGeoCoord($latitude, $longitude, $height, $azimut, $dist)
    {
        $s = (float)$dist/EARTH_RADIUS;
		$this->height = $height;
		
		$lat = $latitude * M_PI / 180;
		$long = $longitude * M_PI / 180;
		$az = $azimut * M_PI / 180;
		
		
		$lat2rad = asin(sin($lat)*cos($s)+cos($lat)*sin($s)*cos($az)); 
		$lat2 = $lat2tmp * 180 / M_PI;
		$long2 = ($long + (atan2(sin($az)*sin($s)*cos($lat),cos($s)-sin($lat)*sin($lat2tmp))))* 180 / M_PI;
		
		$this->latitude = $lat2;
		$this->longitude = $long2;
    }	
	
	// получить расстояние до другой точки в формате APoint
	public function getDistanceToPoint(Point $point2) {
		// перевести координаты в радианы
		$lat1 = $this->getLatitude() * M_PI / 180;
		$lat2 = $point2->getLatitude() * M_PI / 180;
		$long1 = $this->getLongitude() * M_PI / 180;
		$long2 = $point2->getLongitude() * M_PI / 180;
	 
		// косинусы и синусы широт и разницы долгот
		$cl1 = cos($lat1);
		$cl2 = cos($lat2);
		$sl1 = sin($lat1);
		$sl2 = sin($lat2);
		$delta = $long2 - $long1;
		$cdelta = cos($delta);
		$sdelta = sin($delta);
	 
		// вычисления длины большого круга
		$y = sqrt(pow($cl2 * $sdelta, 2) + pow($cl1 * $sl2 - $sl1 * $cl2 * $cdelta, 2));
		$x = $sl1 * $sl2 + $cl1 * $cl2 * $cdelta;
		$ad = atan2($y, $x);
		$dist = $ad * EARTH_RADIUS;
		
		return $dist;
	}
	
	// Получить дистанцию до другой точки в формате LocPoint
	public function getDistanceToLocPoint(LocPoint $point2) {
		$asPoint = $point2->asPoint();
		return $this->getDistanceToPoint($asPoint);
	}

	// получить азимут до другой точки в формате Point
	public function getAzimuthToPoint(Point $point2) {
		// перевести координаты в радианы
		$lat1 = $this->getLatitude() * M_PI / 180;
		$lat2 = $point2->getLatitude() * M_PI / 180;
		$long1 = $this->getLongitude() * M_PI / 180;
		$long2 = $point2->getLongitude() * M_PI / 180;
	 
		// косинусы и синусы широт и разницы долгот
		$cl1 = cos($lat1);
		$cl2 = cos($lat2);
		$sl1 = sin($lat1);
		$sl2 = sin($lat2);
		$delta = $long2 - $long1;
		$cdelta = cos($delta);
		$sdelta = sin($delta);
		
		 //вычисление начального азимута
		$x = (float)($cl1*$sl2) - ($sl1*$cl2*$cdelta);
		$y = (float)$sdelta*$cl2;
		
		$z = atan2($y,$x)*180/M_PI;
		if ($z<0)
			$z+=360;
		return $z;
	}
	
	// получить азимут до другой точки в формате LocPoint
	public function getAzimuthToLocPoint(LocPoint $point2) {	
		$point22 = $point2->asPoint();
		return $this->getAzimuthToPoint($point22);
	}
	
	
	 public static function fromArray(array $array)
    {
        if (isset($array['latitude'])) {
			$pf = new self();
			$pf->setFromLLH($array['latitude'], $array['longitude'], $array['height']);
            return $pf;
        } else {
			$pf = new self();
			$pf->setFromLLH($array[0], $array[1], $array[2]);
            return $pf;
        }
    }

	// функции обработки при загрузке/выгрузке из базы
    public static function fromString($string)
    {
        return self::fromArray(sscanf($string, '(%f,%f, %f)'));
    }

		
    public function __toString()
    {
        return sprintf('(%F,%F,%F)', $this->latitude, $this->longitude, $this->height);
    }

    public function isEmpty()
    {
        return empty($this->latitude) && empty($this->longitude) && empty($this->height);
    }
	
	// ... геттеры ... сеттеры .....
    public function getLatitude()
    {
        return $this->latitude;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }


    public function getHeight()
    {
        return $this->height;
    }
	
	public function setLatitude($latitude) {
		$this->latitude = $latitude;
		return $this;
	}
	
	public function setLongtude($longitude) {
		$this->longitude = $longitude;
		return $this;
	}
	
	public function setHeight($height) {
		$this->height = $height;
		return $this;
	}	
	

}
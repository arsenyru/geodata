<?php
namespace Geo\DataBundle\Visual;
use TCPDF;

// константы для параметров
define ('MAP_YANDEX', "1");
define ('MAP_GOOGLE', "2");

class VisualMap
{
	// параметры
	public $points;
	public $yandexKey;
	public $googleKey;
	public $div;
	public $func;
	
	function __construct ($param) {
		$this->setParam($param);
	}
	
	public function setParam ($param) {	
		if (isset($param['points']))
			$this->points = $param['points'];
		else
			$this->points = array();
		if (isset($param['div']))
			$this->div = $param['div'];
		else
			$this->div = "map";
		if (isset($param['yandexKey']))
			$this->yandexKey = $param['yandexKey'];
		else
			$this->yandexKey = "";
		if (isset($param['googleKey']))	
			$this->googleKey = $param['googleKey'];
		else
			$this->googleKey = "";		
		if (isset($param['func']))
			$this->func = $param['func'];
		else
			$this->func = "initialize";
	}
	
	public function getYandexMap () {
		return $this->getMap(MAP_YANDEX);
	}
	
	
	// returns map according to parameters:
	// $mapType = MAP_YANDEX --> yandex map
	// $mapType = MAP_GOOGLE --> google map
	// $addVidget = true --> function to add points from map
	public function getMap ($mapType, $addVidget = false) {
		
		// style
		$script = '
			<style>
			body, html {
				padding: 0;
				margin: 0;
				width: 100%;
				height: 100%;
			}
			#'.$this->div.' {
				width: 100%;
				height: 50%;
			}
		</style>';
		
		// jQuery
		$script.= '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js"></script>';
		
		// loading API...
		if ($mapType == MAP_YANDEX)
			$script.='<script src="http://api-maps.yandex.ru/2.1/?lang=ru_RU&amp;key='.$this->yandexKey.'" type="text/javascript"></script>';
		if ($mapType == MAP_GOOGLE)
			$script.='
			<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
			<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key='.$this->googleKey.'&sensor=false"></script>
			<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?libraries=places&sensor=false"></script>
			';
		
		// initalizing map
		$script.= '
		<script type="text/javascript">
		var map;
		';
		
		if ($mapType == MAP_GOOGLE)
			$script.='
			var markers = new Array();
			';
		
		if ($mapType == MAP_YANDEX)
			$script.='
				ymaps.ready('.$this->func.');
			';
			
		// init function
		$script.= "function ".$this->func."() {";
		
		if ($mapType==MAP_GOOGLE) 
			$script.= '	var mapOptions = {
		  center: new google.maps.LatLng('.$this->points[0]->getPoint()->getLatitude().', '.$this->points[0]->getPoint()->getLongitude().'),
		  zoom: 10,
		  mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		map = new google.maps.Map(document.getElementById("'.$this->div.'"),
			mapOptions);
		';
		
		if ($mapType == MAP_YANDEX) 
			$script.="map = new ymaps.Map(".$this->div.", {
        center: [".$this->points[0]->getPoint()->getLatitude().", ".$this->points[0]->getPoint()->getLongitude()."],
        zoom: 10
		});";
		
		if ($mapType == MAP_YANDEX) 
			foreach ($this->points as $pts) {
			$script.="
			myGeoObject = new ymaps.GeoObject({
			// Описание геометрии.
			geometry: {
				type: 'Point',
				coordinates: [".$pts->getPoint()->getLatitude().", ".$pts->getPoint()->getLongitude()."]
			},
			// Свойства.
			properties: {
				// Контент метки.
				iconContent: '".$pts->getTitle()."'
			}
			}, {
				// Иконка метки будет растягиваться под размер ее содержимого.
				preset: 'islands#blackStretchyIcon',
			});
			map.geoObjects.add(myGeoObject);
			";
			}
			
		if ($mapType == MAP_GOOGLE) {
			$index=1;
			foreach ($this->points as $pts) {
			$script.='
			var myLatlng = new google.maps.LatLng('.$pts->getPoint()->getLatitude().','.$pts->getPoint()->getLongitude().');
				markers['.$index++.'] = new google.maps.Marker({
				  position: myLatlng,
				  map: map,
				  title:"'.$pts->getTitle().'"
			  });
			  ';
			}
		}
		
		if ($addVidget) {
			if ($mapType == MAP_YANDEX)
				$script.='			
				map.events.add("click", function(e){
				var coords = e.get("coords");
				ymaps.geocode(coords).then(function (res) {
					var nameObject = res.geoObjects.get(0);
					var nameToBD = nameObject.properties.get("text");
					
					$.ajax({ // AJAX to Add
						type:"post",
						url: document.URL,
						data: {"lat": coords[0].toPrecision(6), "long": coords[1].toPrecision(6), "title": nameToBD},
						response: "text",
						success:function (data) {
							$("#response").html(data);
							}
						});
					});
				});';
			if ($mapType == MAP_GOOGLE)
				$script.="
				geocoder = new google.maps.Geocoder();
				google.maps.event.addListener(map, 'click', function(e) {
					var titleToBD = 'Google Maps Point '+parseInt(Math.random()*1000);
					geocoder.geocode({'latLng': e.latLng}, function(results, status) {
						if (status == google.maps.GeocoderStatus.OK) {
							if (results[1]) {
								titleToBD = results[1].formatted_address;
							}
						}
						
						$.ajax({ // AJAX to Add
							type:'post',
							url: document.URL,
							data: {'lat': e.latLng.lat(), 'long': e.latLng.lng(), 'title': titleToBD},
							response: 'text',
							success:function (data) {
								$('#response').html(data);
							}
						});
					});
				});";
		}
			
		
		

		$script.= '
		}
		</script>';
		
		
		return $script;
	}
	
	
	public function getGoogleMap () {
		return $this->getMap(MAP_GOOGLE);
	}
	
	
	public function getYandexAddPointVidget() {
		return $this->getMap(MAP_YANDEX, true);
	}
	
	public function getGoogleAddPointVidget() {
		return $this->getMap(MAP_GOOGLE, true);
	}
	
}

?>
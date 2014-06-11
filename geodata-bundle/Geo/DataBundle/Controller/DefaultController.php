<?php

namespace Geo\DataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Geo\DataBundle\Entity\Points;
use Geo\DataBundle\Entity\APoints;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use geodata\ORM\Type\Point;
use geodata\ORM\Type\LocPoint;
use Geo\DataBundle\Form\Extension\FormType;
use Geo\DataBundle\Form\Extension\FormLocType;
use TCPDF;
use Geo\DataBundle\Visual\Visual;
use Geo\DataBundle\Visual\VisualMap;

class DefaultController extends Controller
{

	// main page
    public function indexAction()
    {
        return $this->render('GeoDataBundle:Default:index.html.twig');
    }
	
	// edit point 1
	public function editpoint1Action (Request $request) {
	
		// сообщения для отображения на странице
		$messages = array(); 
		
		$id = -1;
		
		// get id and data
		if (isset($_GET['id'])) {
			$id = intval($_GET['id']);
			$em = $this->getDoctrine()->getEntityManager();
			$product = $em->getRepository('GeoDataBundle:Points')->find($id);
			$data['latitude'] = $product->getPoint()->getLatitude();
			$data['longitude'] = $product->getPoint()->getLongitude();
			$data['height'] = $product->getPoint()->getHeight();
			$data['title'] = $product->getTitle();
		} else {
			$message = array('status'=>1, 'text' => 'Ошибка передачи данных о точке');
			$messages[] = $message;	
		}
		
		$errorEdit = false; // all right
		
		if ($request->getMethod() == 'POST') {
			// получены не все данные
			if ($this->emptyString($_POST['latitude']) || $this->emptyString($_POST['longitude']) || $this->emptyString($_POST['height']) || $this->emptyString($_POST['title'])) {
				$errorAdd = true;
				$message = array('status'=>1, 'text' => 'Заполнены не все поля');
				$messages[] = $message;				
			}
			else {
				$repository = $this->getDoctrine()->getRepository('GeoDataBundle:Points');
				$pp = new Point();
				$result = $pp->setFromLLH($_POST['latitude'], $_POST['longitude'], $_POST['height']);
				if ($result == 0) {
					// проверка наличия такой точки
					$point = $repository->findOneBy(array('point' => $pp));
					if ($point && $point->getId()!=$id) {
						$message = array('status'=>1, 'text' => 'Географический объект с такими координатами уже есть в базе данных');
						$messages[] = $message;		
						$errorEdit = true;
					}
					else {
						// устанавливаем
						$updPoint = $repository->find($id);
						$updPoint->setPoint($pp);
						$updPoint->setTitle($_POST['title']);
						$em->flush();
						return $this->redirect($this->generateUrl('geodata_viewp1')."?update");
					}				
				}
				else {
					$errorEdit = true;
					// собираем все полученные ошибки
					if ($result & 1) {
						$message = array('status'=>1, 'text' => 'Неправильный формат высоты: должно быть число');
						$messages[] = $message;
					}
					if ($result & 1<<1) {
						$message = array('status'=>1, 'text' => 'Неправильный формат долготы: должно быть число');
						$messages[] = $message;
					}
					if ($result & 1<<2) {
						$message = array('status'=>1, 'text' => 'Неправильный формат широты: должно быть число');
						$messages[] = $message;
					}	
					if ($result & 1<<3) {
						$message = array('status'=>1, 'text' => 'Неправильный формат долготы: число должно быть географической координатой');
						$messages[] = $message;
					}	
					if ($result & 1<<4) {
						$message = array('status'=>1, 'text' => 'Неправильный формат широты: число должно быть географической координатой');
						$messages[] = $message;
					}					
				}
			}
		}
	
		// добавляем названия
		$formArray = array ('label_longitude'=>'Долгота',
							'label_latitude'=>'Широта',
							'label_height'=>'Высота',
							'label_title'=>'Название');
		
		// подготавливаем массив в форму
		foreach (array ('longitude', 'latitude', 'height', 'title') as $v)
			if (isset($_POST[$v]))
				$formArray[$v]=$_POST[$v];
			else if(isset($data[$v]))
				$formArray[$v]=$data[$v];
			
		
		$form = $this->CreateForm(new FormType(), $formArray);
        return $this->render('GeoDataBundle:Default:editpoint1.html.twig', array ('form'=>$form->createView(), 'messages'=>$messages, 'id'=>$id));
	}
	
	// просмотр точек глобальных..
	    public function viewp1Action(Request $request)
    {
		// message to view
		// status - 0 good, 1 bad
		// text - text of message
		// example
		// $messages[0]['status'] = 0, $messages[0]['text'] = "Delete completed"
		$messages = array(); 
		if ($request->getMethod() =='GET') {
			// удаление
			if (isset($_GET['del'])) {
				$id = intval($_GET['del']);
				$em = $this->getDoctrine()->getEntityManager();
				$product = $em->getRepository('GeoDataBundle:Points')->find($id);
				
				if (!$product) {
					$message = array('status'=>1, 'text' => 'Нет такой точки');
					$messages[] = $message;
				}
				else {
					$em->remove($product);
					$em->flush();
					$message = array('status'=>0, 'text' => 'Удаление успешно выполнено');
					$messages[] = $message;
				}
			}
			// обновление успешно
			if (isset($_GET['update'])) {
				$message = array('status'=>0, 'text' => 'Изменение успешно выполнено');
				$messages[] = $message;				
			}
		}
		$errorAdd = false; // all right
		// добавление
		if ($request->getMethod() == 'POST') {
			if ($this->emptyString($_POST['latitude']) || $this->emptyString($_POST['longitude']) || $this->emptyString($_POST['height']) || $this->emptyString($_POST['title'])) {
				$errorAdd = true;
				$message = array('status'=>1, 'text' => 'Заполнены не все поля');
				$messages[] = $message;				
			}
			else {
				$newp = new Points();
				$newp->setTitle($_POST['title']);
				$pp = new Point();
				$result = $pp->setFromLLH($_POST['latitude'], $_POST['longitude'], $_POST['height']);
				if ($result == 0) {
					// проверка наличия такой точки
					$repository = $this->getDoctrine()->getRepository('GeoDataBundle:Points');
					$point = $repository->findOneBy(array('point' => $pp));
					if ($point) {
						$message = array('status'=>1, 'text' => 'Географический объект с такими координатами уже есть в базе данных');
						$messages[] = $message;		
						$errorAdd = true;
					}
					else {
						$newp->setPoint($pp);
						$em = $this->getDoctrine()->getEntityManager();
						$em->persist($newp);
						$em->flush();
						$message = array('status'=>0, 'text' => 'Добавление успешно выполнено');
						$messages[] = $message;	
					}				
				}
				else {
					$errorAdd = true;
					// собираем все полученные ошибки
					if ($result & 1) {
						$message = array('status'=>1, 'text' => 'Неправильный формат высоты: должно быть число');
						$messages[] = $message;
					}
					if ($result & 1<<1) {
						$message = array('status'=>1, 'text' => 'Неправильный формат долготы: должно быть число');
						$messages[] = $message;
					}
					if ($result & 1<<2) {
						$message = array('status'=>1, 'text' => 'Неправильный формат широты: должно быть число');
						$messages[] = $message;
					}	
					if ($result & 1<<3) {
						$message = array('status'=>1, 'text' => 'Неправильный формат долготы: число должно быть географической координатой');
						$messages[] = $message;
					}	
					if ($result & 1<<4) {
						$message = array('status'=>1, 'text' => 'Неправильный формат широты: число должно быть географической координатой');
						$messages[] = $message;
					}					
				}
			}
		}
		
		
		$pts = $this->getDoctrine()->getRepository('GeoDataBundle:Points')->findAll();
	
		// добавляем названия
		$formArray = array ('label_longitude'=>'Долгота',
							'label_latitude'=>'Широта',
							'label_height'=>'Высота',
							'label_title'=>'Название');
		
		if ($errorAdd) {
			// подготавливаем массив в форму
			foreach (array ('longitude', 'latitude', 'height', 'title') as $v)
				if (isset($_POST[$v]))
					$formArray[$v]=$_POST[$v];
		}
			
		
		$form = $this->CreateForm(new FormType(), $formArray);
        return $this->render('GeoDataBundle:Default:viewp1.html.twig', array ('points'=>$pts, 'form'=>$form->createView(), 'messages'=>$messages));
    }
	
	// edit point 2..

	public function editpoint2Action (Request $request) {
		$messages = array(); 
		$id = -1;
		if (isset($_GET['id'])) {
			$id = intval($_GET['id']);
			$em = $this->getDoctrine()->getEntityManager();
			$product = $em->getRepository('GeoDataBundle:APoints')->find($id);
			$data['latitude'] = $product->getPoint()->getBase()->getLatitude();
			$data['longitude'] = $product->getPoint()->getBase()->getLongitude();
			$data['height'] = $product->getPoint()->getBase()->getHeight();
			$data['azimuth'] = $product->getPoint()->getAzimuth();
			$data['distance'] = $product->getPoint()->getDistance();
			$data['title'] = $product->getTitle();
		} else {
			$message = array('status'=>1, 'text' => 'Ошибка передачи данных о точке');
			$messages[] = $message;	
		}
		
		$errorEdit = false; // all right
		if ($request->getMethod() == 'POST') {
			if ($this->emptyString($_POST['latitude']) || $this->emptyString($_POST['longitude']) || $this->emptyString($_POST['height']) || $this->emptyString($_POST['title'])
			|| $this->emptyString($_POST['azimuth']) || $this->emptyString($_POST['distance'])) {
				$errorEdit = true;
				$message = array('status'=>1, 'text' => 'Заполнены не все поля');
				$messages[] = $message;				
			}
			else {
				$repository = $this->getDoctrine()->getRepository('GeoDataBundle:APoints');
				$pp = new LocPoint();
				$result = $pp->setFromLLHAD($_POST['latitude'], $_POST['longitude'], $_POST['height'], $_POST['azimuth'], $_POST['distance']);
				if ($result == 0) {
					// проверка наличия такой точки
					$point = $repository->findOneBy(array('point' => $pp));
					if ($point && $point->getId()!=$id) {
						$message = array('status'=>1, 'text' => 'Географический объект с такими координатами уже есть в базе данных');
						$messages[] = $message;		
						$errorEdit = true;
					}
					else {
						$updPoint = $repository->find($id);
						$updPoint->setPoint($pp);
						$updPoint->setTitle($_POST['title']);
						$em->flush();
						return $this->redirect($this->generateUrl('geodata_viewp2')."?update");
					}				
				}
				else {
					$errorEdit = true;
					// собираем все полученные ошибки
					if ($result & 1) {
						$message = array('status'=>1, 'text' => 'Неправильный формат высоты: должно быть число');
						$messages[] = $message;
					}
					if ($result & 1<<1) {
						$message = array('status'=>1, 'text' => 'Неправильный формат долготы: должно быть число');
						$messages[] = $message;
					}
					if ($result & 1<<2) {
						$message = array('status'=>1, 'text' => 'Неправильный формат широты: должно быть число');
						$messages[] = $message;
					}	
					if ($result & 1<<3) {
						$message = array('status'=>1, 'text' => 'Неправильный формат долготы: число должно быть географической координатой');
						$messages[] = $message;
					}	
					if ($result & 1<<4) {
						$message = array('status'=>1, 'text' => 'Неправильный формат широты: число должно быть географической координатой');
						$messages[] = $message;
					}		
					if ($result & 1<<5) {
						$message = array('status'=>1, 'text' => 'Неправильный формат расстояния: должно быть число');
						$messages[] = $message;
					}		
					if ($result & 1<<6) {
						$message = array('status'=>1, 'text' => 'Неправильный формат азимута: должно быть число');
						$messages[] = $message;
					}	
					if ($result & 1<<7) {
						$message = array('status'=>1, 'text' => 'Неправильный формат азимута: должен быть угол в градусах от 0 до 360');
						$messages[] = $message;
					}						
				}
			}
		}
	
		// добавляем названия
		$formArray = array ('label_longitude'=>'Долгота',
							'label_latitude'=>'Широта',
							'label_height'=>'Высота',
							'label_title'=>'Название',
							'label_azimuth'=>'Азимут',
							'label_distance'=>'Расстояние');
		
		// подготавливаем массив в форму
		foreach (array ('longitude', 'latitude', 'height', 'azimuth', 'distance', 'title') as $v)
			if (isset($_POST[$v]))
				$formArray[$v]=$_POST[$v];
			else if(isset($data[$v]))
				$formArray[$v]=$data[$v];
			
		
		$form = $this->CreateForm(new FormLocType(), $formArray);
        return $this->render('GeoDataBundle:Default:editpoint2.html.twig', array ('form'=>$form->createView(), 'messages'=>$messages, 'id'=>$id));
	}
	
	// просмотр точек в локальном формате
	    public function viewp2Action(Request $request)
    {
		$messages = array();
		
		if ($request->getMethod() =='GET') {
			if (isset($_GET['del'])) {
				$id = intval($_GET['del']);
				$em = $this->getDoctrine()->getEntityManager();
				$product = $em->getRepository('GeoDataBundle:APoints')->find($id);
				
				if (!$product) {
					$message = array('status'=>1, 'text' => 'Нет такой точки');
					$messages[] = $message;
				}
				else {
					$em->remove($product);
					$em->flush();
					$message = array('status'=>0, 'text' => 'Удаление успешно выполнено');
					$messages[] = $message;
				}
			}
			if (isset($_GET['update'])) {
				$message = array('status'=>0, 'text' => 'Изменение успешно выполнено');
				$messages[] = $message;				
			}
		}
		$errorAdd = false; // all right
		
		if ($request->getMethod() == 'POST') {
			if ($this->emptyString($_POST['latitude']) || $this->emptyString($_POST['longitude']) || $this->emptyString($_POST['height']) || $this->emptyString($_POST['title']) 
			|| $this->emptyString($_POST['azimuth']) || $this->emptyString($_POST['distance'])) {
				$errorAdd = true;
				$message = array('status'=>1, 'text' => 'Заполнены не все поля');
				$messages[] = $message;				
			}
			else {
				$newp = new APoints();
				$newp->setTitle($_POST['title']);
				$pp = new LocPoint();
				$result = $pp->setFromLLHAD($_POST['latitude'], $_POST['longitude'], $_POST['height'], $_POST['azimuth'], $_POST['distance']);
				if ($result==0) {
					// проверка наличия такой точки
					$repository = $this->getDoctrine()->getRepository('GeoDataBundle:APoints');
					$point = $repository->findOneBy(array('point' => $pp));
					if ($point) {
						$message = array('status'=>1, 'text' => 'Географический объект с такими координатами уже есть в базе данных');
						$messages[] = $message;		
						$errorAdd = true;
					}
					else {
						$newp->setPoint($pp);
						$em = $this->getDoctrine()->getEntityManager();
						$em->persist($newp);
						$em->flush();
						$message = array('status'=>0, 'text' => 'Добавление успешно выполнено');
						$messages[] = $message;	
					}
				}
				else {
					$errorAdd = true;
					// собираем все полученные ошибки
					if ($result & 1) {
						$message = array('status'=>1, 'text' => 'Неправильный формат высоты: должно быть число');
						$messages[] = $message;
					}
					if ($result & 1<<1) {
						$message = array('status'=>1, 'text' => 'Неправильный формат долготы: должно быть число');
						$messages[] = $message;
					}
					if ($result & 1<<2) {
						$message = array('status'=>1, 'text' => 'Неправильный формат широты: должно быть число');
						$messages[] = $message;
					}	
					if ($result & 1<<3) {
						$message = array('status'=>1, 'text' => 'Неправильный формат долготы: число должно быть географической координатой');
						$messages[] = $message;
					}	
					if ($result & 1<<4) {
						$message = array('status'=>1, 'text' => 'Неправильный формат широты: число должно быть географической координатой');
						$messages[] = $message;
					}			
					if ($result & 1<<5) {
						$message = array('status'=>1, 'text' => 'Неправильный формат расстояния: должно быть число');
						$messages[] = $message;
					}		
					if ($result & 1<<6) {
						$message = array('status'=>1, 'text' => 'Неправильный формат азимута: должно быть число');
						$messages[] = $message;
					}	
					if ($result & 1<<7) {
						$message = array('status'=>1, 'text' => 'Неправильный формат азимута: должен быть угол в градусах от 0 до 360');
						$messages[] = $message;
					}					
				}
			}
		}	
		$pts = $this->getDoctrine()->getRepository('GeoDataBundle:APoints')->findAll();
		
		// добавляем названия
		$formArray = array ('label_longitude'=>'Долгота',
							'label_latitude'=>'Широта',
							'label_height'=>'Высота',
							'label_title'=>'Название',
							'label_azimuth'=>'Азимут',
							'label_distance'=>'Расстояние');
		
		if ($errorAdd) {
			// подготавливаем массив в форму
			foreach (array ('longitude', 'latitude', 'height', 'title', 'distance', 'azimuth') as $v)
				if (isset($_POST[$v]))
					$formArray[$v]=$_POST[$v];
		}
			
		
		$form = $this->CreateForm(new FormLocType(), $formArray);
		
        return $this->render('GeoDataBundle:Default:viewp2.html.twig', array ('form'=>$form->createView(), 'points'=>$pts, 'messages'=>$messages));
    }
	
	
	// calc distance
	    public function distAction(Request $request)
    {
		$dist = null;
		$pointRequest1 = 0;
		$pointRequest2 = 0;
		$point1 = 0;
		$point2 = 0;
		
		if ($request->getMethod() == 'POST') {
			$pointRequest1 = $_POST['point1'];
			$pointRequest2 = $_POST['point2'];
			$point1 = new Point();
			$point2 = new Point();
			// get points
			if ($pointRequest1[0] == 'a') {
				preg_match('#a([0-9]{1,})#im', $pointRequest1, $arr);
				$tmp = $this->getDoctrine()->getRepository('GeoDataBundle:APoints')->find($arr[1]);
				$point1->setFromAPoint($tmp->getPoint());
			}
			else {
				$tmp = $this->getDoctrine()->getRepository('GeoDataBundle:Points')->find($pointRequest1);
				$point1=$tmp->getPoint();
			}
			if ($pointRequest2[0] == 'a') {
				preg_match('#a([0-9]{1,})#im', $pointRequest2, $arr);
				$tmp = $this->getDoctrine()->getRepository('GeoDataBundle:APoints')->find($arr[1]);
				$point2->setFromAPoint($tmp->getPoint());
			}
			else {
				$tmp = $this->getDoctrine()->getRepository('GeoDataBundle:Points')->find($pointRequest2);
				$point2=$tmp->getPoint();
			}		

			
			$dist = $point1->getDistanceToPoint($point2);
		}
		
		$pts = $this->getDoctrine()->getRepository('GeoDataBundle:Points')->findAll();
		$apts = $this->getDoctrine()->getRepository('GeoDataBundle:APoints')->findAll();
		
        return $this->render('GeoDataBundle:Default:dist.html.twig', array ('dist'=>$dist, 'pts'=>$pts, 'apts'=>$apts,
		'pointRequest2'=>$pointRequest2, 'pointRequest1'=>$pointRequest1, 'point1'=>$point1, 'point2'=>$point2));
    }	

	// view google maps
	    public function googlemapsAction(Request $request)
    {
		$addClick = false;
		// add point from map
		if (isset($_GET['addclick'])) {
			$addClick = true;
			
			if (isset($_POST['title'])) {
				$lat = $_POST['lat'];
				$long = $_POST['long'];
				$height = 0;
				$title = $_POST['title'];
				$newp = new Points();
				$newp->setTitle($title);
				$pp = new Point();
				$result = $pp->setFromLLH($lat, $long, $height);
				$repository = $this->getDoctrine()->getRepository('GeoDataBundle:Points');
				$newp->setPoint($pp);
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($newp);
				$em->flush();
				// возвращаем ответ ajax-запросу
				echo "Добавление точки <b>$title</b> успешно выполнено";
				exit;
			}
		}
		
		
		$pts = $this->getDoctrine()->getRepository('GeoDataBundle:Points')->findAll();
		$param['points'] 	= $pts;											// точки для отображения
		$param['div'] 	 	= "map_canvas";									// элемент с таким id для карты
		$param['func']  	= "initialize";									// функция загрузки
		$param['googleKey'] = "AIzaSyDQHv7bOsO3ic5deOBx8Cz-WR6jLo3NxYo";	// ключ api google
		
		$visualMap = new VisualMap($param); // создаем карту
		if (!$addClick)
			$script = $visualMap->getGoogleMap(); // без добавления
		else
			$script = $visualMap->getGoogleAddPointVidget(); // с добавлением
		
		return $this->render('GeoDataBundle:Default:googlemaps.html.twig', array ('googleScript'=>$script, 'func'=>$param['func'], 'pts'=>$pts, 'addClick'=>$addClick));
	}
	
		// яндекс карты
	    public function yamapsAction(Request $request)
    {
		$addClick = false;
		// добавление точки с карты
		if (isset($_GET['addclick'])) {
			$addClick = true;
			
			if (isset($_POST['title'])) {
				$lat = $_POST['lat'];
				$long = $_POST['long'];
				$height = 0;
				$title = $_POST['title'];
				$newp = new Points();
				$newp->setTitle($title);
				$pp = new Point();
				$result = $pp->setFromLLH($lat, $long, $height);
				$repository = $this->getDoctrine()->getRepository('GeoDataBundle:Points');
				$newp->setPoint($pp);
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($newp);
				$em->flush();
				echo "Добавление точки <b>$title</b> успешно выполнено";
				exit;
			}
			
		}
	
		$pts = $this->getDoctrine()->getRepository('GeoDataBundle:Points')->findAll();
		$param['points'] = $pts; // настраиваем параметры
		$param['div'] = "map_canvas";
		$param['func'] = "initialize";
		
		$visualMap = new VisualMap($param);
		if (!$addClick)
			$script = $visualMap->getYandexMap();
		else
			$script = $visualMap->getYandexAddPointVidget();
		
		return $this->render('GeoDataBundle:Default:yamaps.html.twig', array ('yaScript'=>$script, 'func'=>$param['func'], 'pts'=>$pts, 'addClick'=>$addClick));
	}
	
	// check empty string
	public function emptyString ($string) {
		if ($string=="")
			return true;
		else
			return false;
	}
	
	// визуализация точек локального формата - D3JS, PNG, PDF
	
	public function maps2Action (Request $request) {
		if ($request->getMethod()=='POST') {
			$repo = $this->getDoctrine()->getRepository('GeoDataBundle:APoints');
			// получили точку для отрисовки
			$point = intval($_POST['point']);
			// проверить, что есть
			$dataPoint = $repo->find($point);
			if (!$dataPoint);// error
			else {			
				// search maxDist
				$pointsBased = array();
				$allPoints = $repo->findAll();
				foreach ($allPoints as $v) {
					if ($v->getPoint()->getBase() == $dataPoint->getPoint()->getBase())
						$pointsBased[] = $v;
				}	
				
				// параметры
				$param['width'] 		= 300; 				// ширина картинки
				$param['widthLegend']	= 200; 				// щирина легенды
				$param['height'] 		= 300; 				// высота картинки
				$param['colorCenter'] 	= "000000"; 		// цвет отрисовки центральной точки
				$param['colorLegend'] 	= "000000";			// цвет отрисовки легенды
				$param['colorPoint'] 	= "000000"; 		// цвет отрисовки точек
				$param['colorBG'] 		= "FFFFFF"; 		// цвет фона
				$param['sizePoints'] 	= 6; 				// размер рисуемых точек
				$param['font'] 			= "arial.ttf"; 		// Шрифт
				$param['fontSize'] 		= 10; 				// размер шрифта	
				$param['metersLabel'] 	= "метров"; 		// свой перевод для meters
				$param['points'] 		= $pointsBased; 	// массив точек для отрисовки
				$param['div'] 			= 'chart'; 			// div для D3JS
				$param['centerLabel'] 	= "Центр"; 			// свой перевод для center
				
				foreach ($param as $k=>$v) 
					if (isset($_POST[$k]))
						$param[$k] = $_POST[$k];
				
			// создаем класс визуализации
			$visual = new Visual ($param);
	
			
			// PNG
			if ($_POST['format']==2) {
				header ("Content-type: image/png");
				echo $visual->getPNG();
				exit;
			}
			
			// PDF
			if ($_POST['format']==3) {
				$pdf = $visual->getPDF();
				$pdf->Output('doc.pdf', 'I'); 
				exit;
			}
			
			// else javascript
			
			$script = $visual->getJS();
			return $this->render('GeoDataBundle:Default:maps2d3js.html.twig', array('script'=>$script, 'width'=>$param['width']+$param['widthLegend'], 'height'=>$param['height']));
				
			}
		}
		
		// form selecting based point
		$pts = $this->getDoctrine()->getRepository('GeoDataBundle:APoints')->findAll();
		$points = array();
		for ($i=0;$i<count($pts);$i++) {
			$base = $pts[$i]->getPoint()->getBase()->__toString();
			$flag = false; 
			for ($j=0;$j<$i;$j++)
				if ($pts[$j]->getPoint()->getBase()->__toString()==$base) {
					$flag = true; // если точка уже есть в списке - установить флаг
					break;
				}
			if (!$flag)
				$points[]=$pts[$i];
		}
		return $this->render('GeoDataBundle:Default:maps2.html.twig', array ('pts'=>$points));		
	}
}

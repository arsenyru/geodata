<?php

// класс визуализации точек в локальном формате

namespace Geo\DataBundle\Visual;
use TCPDF;

class Visual
{
	// принимаемые параметры
	public $maxDist, $minWidthHeight, $metersIn10Pix, $pathToFont;
	
	public $width, $this, $widthLegend, $colorCenter, $colorLegend, $colorPoints, $colorBG, $sizePoints, $font, $fontSize, $metersLabel, $pointsBased;
	public $centerLabel;
	
	public $k;
	public $div;
	public $centerWidth, $centerHeight;
	
	function __construct ($param) {
		$this->setParam($param);
		$this->init();
	}
	
	public function setParam ($param) {	
		// загружаем данные из массива параметров
		$this->width = $param['width'];
		$this->height = $param['height'];
		$this->widthLegend = $param['widthLegend'];
		$this->colorCenter = hexdec($param['colorCenter']);
		$this->colorLegend = hexdec($param['colorLegend']);
		$this->colorPoints = hexdec($param['colorPoint']);
		$this->colorBG = hexdec($param['colorBG']);
		$this->sizePoints = $param['sizePoints'];
		$this->font = $param['font'];
		$this->fontSize = $param['fontSize'];
		$this->metersLabel = $param['metersLabel'];
		$this->pointsBased = $param['points'];
		$this->div = $param['div'];
		$this->centerLabel = $param['centerLabel'];
	}
	
	// инициализация, получение коэффициентов и расчетных переменных
	public function init() {
		$this->maxDist = 0;
		foreach ($this->pointsBased as $v) {
			if ($v->getPoint()->getDistance()>$this->maxDist)
				$this->maxDist = $v->getPoint()->getDistance();
		}
		
		$this->minWidthHeight = ($this->width>$this->height)?$this->height:$this->width;
		$this->metersIn10Pix = $this->maxDist * 18 / $this->minWidthHeight;
		
		$this->pathToFont = __DIR__."/../Resources/public/fonts/".$this->font; // путь к используемому шрифту
		if ($this->maxDist!=0)
			$this->k = $this->minWidthHeight*0.9/(2*$this->maxDist);
		else
			$this->k=0;
			
		$this->centerWidth = $this->width/2;
		$this->centerHeight = $this->height/2;
		
	}

	// получить карту в формате PNG
	public function getPNG () {
		
		// создаем картинку
		$image = imageCreate($this->width+$this->widthLegend, $this->height);
		$backColor = imageColorAllocate($image, ($this->colorBG>>16), ($this->colorBG>>8)%256, $this->colorBG%256);
		$centerColor = imageColorAllocate($image, ($this->colorCenter>>16), ($this->colorCenter>>8)%256, $this->colorCenter%256);
		$pointsColor = imageColorAllocate($image, ($this->colorPoints>>16), ($this->colorPoints>>8)%256, $this->colorPoints%256);
		$legendColor = imageColorAllocate($image, ($this->colorLegend>>16), ($this->colorLegend>>8)%256, $this->colorLegend%256);
		
		// background
		imageFilledRectangle($image, 0,0, $this->width, $this->height, $backColor);
		imageRectangle($image, $this->width, 0, $this->widthLegend+$this->width-1, $this->height-1, $legendColor);
		// добавляем масштаб в легенду
		imageLine($image, $this->width+10, $this->height-$this->fontSize*2-1, $this->width+10, $this->height-$this->fontSize*2+1, $legendColor);
		imageLine($image, $this->width+10, $this->height-$this->fontSize*2, $this->width+20, $this->height-$this->fontSize*2, $legendColor);
		imageLine($image, $this->width+20, $this->height-$this->fontSize*2-1, $this->width+20, $this->height-$this->fontSize*2+1, $legendColor);
		imageTTFText($image, $this->fontSize, 0, $this->width+$this->fontSize+20, $this->height-$this->fontSize*1.5, $legendColor, $this->pathToFont, "$this->metersIn10Pix $this->metersLabel");
		
		// center
		imageFilledEllipse($image, $this->centerWidth, $this->centerHeight, $this->sizePoints, $this->sizePoints, $centerColor);
		imageTTFText($image, $this->fontSize, 0, $this->centerWidth, $this->centerHeight+$this->fontSize+$this->sizePoints, $centerColor, $this->pathToFont, "0");
		imageTTFText($image, $this->fontSize, 0, $this->width+$this->fontSize, $this->fontSize*2, $legendColor, $this->pathToFont, "0 - $this->centerLabel");
		// points
		$i=1;
		foreach ($this->pointsBased as $v) {
			$angle = $v->getPoint()->getAzimuth() - 90 ; // угол для тригонометрии
			 $pointWidth = $this->centerWidth+$this->k*($v->getPoint()->getDistance()*cos(deg2rad($angle)));
			 $pointHeight = $this->centerHeight+$this->k*($v->getPoint()->getDistance()*sin(deg2rad($angle)));
			 // рисуем точку
			 imageEllipse ($image, $pointWidth, $pointHeight, $this->sizePoints, $this->sizePoints, $pointsColor);
			 // подпись
			 imageTTFText($image, $this->fontSize, 0, $pointWidth, $pointHeight+$this->fontSize+$this->sizePoints, $pointsColor, $this->pathToFont, $i);
			 // в легенду
			 imageTTFText($image, $this->fontSize, 0, $this->width+$this->fontSize, $this->fontSize*2*($i+1), $legendColor, $this->pathToFont, "$i - ".$v->getTitle());
			 $i++;
		}
		ob_start();
		imagePng($image);
		$str = ob_get_clean();
		return $str;
	}
	
	// карта в формате PDF
	public function getPDF() {
		$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
		$pdf->setPrintHeader(false); 
		$pdf->setPrintFooter(false); 
		$pdf->AddPage(); // создаем первую страницу, на которой будет содержимое 
		$pngMap = $this->getPNG();
		$pdf->Image('@'.$pngMap);
		return $pdf;
	}
	
	// NOTE: use with filter raw to correct print javascript: {{script|raw}} 
	// карта с помощью JavaScript на страничку
	public function getJS () {	
		$script = '
		document.getElementById("'.$this->div.'").style="background-color: #'.$this->myDecHex($this->colorBG).'";
		var svg = d3.select("#'.$this->div.'").append("svg")
		.attr("width", '.($this->width+$this->widthLegend).')
		.attr("height", '.$this->height.');'; // создаем график
		
		$script.='	  
		
		svg.append("svg:line")
			.attr("x1", '.($this->width+10).')
			.attr("x2", '.($this->width+10).')
			.attr("y1", '.($this->height-$this->fontSize*2-5).')
			.attr("y2", '.($this->height-$this->fontSize*2+5).')
			.style("stroke", "#'.$this->myDecHex($this->colorLegend).'")
			.style("stroke-width", "1px");
		
		svg.append("svg:line")
			.attr("x1", '.($this->width+20).')
			.attr("x2", '.($this->width+20).')
			.attr("y1", '.($this->height-$this->fontSize*2-5).')
			.attr("y2", '.($this->height-$this->fontSize*2+5).')
			.style("stroke", "#'.$this->myDecHex($this->colorLegend).'")
			.style("stroke-width", "1px");

		svg.append("svg:line")
			.attr("x1", '.($this->width+10).')
			.attr("x2", '.($this->width+20).')
			.attr("y1", '.($this->height-$this->fontSize*2).')
			.attr("y2", '.($this->height-$this->fontSize*2).')
			.style("stroke", "#'.$this->myDecHex($this->colorLegend).'")
			.style("stroke-width", "1px");			
		  
		   svg.append("text")
			  .attr("x", '.($this->width+$this->fontSize+20).')
			  .attr("y", '.($this->height-$this->fontSize*1.5).')
			  .attr("dy", ".25em")
			  .style("font", "'.$this->fontSize.'px sans-serif")
			  .style("text-anchor", "start")
			  .style("font-weight", "normal")
			  .style("stroke", "#'.$this->myDecHex($this->colorLegend).'")
			  .style("letter-spacing", ".25em")
			  .text("'.$this->metersIn10Pix.' '.$this->metersLabel.'");
		  
		svg.append("circle")
		  .attr("r", '.($this->sizePoints/2).')
		  .attr("cx", '.$this->centerWidth.')
		  .attr("cy", '.$this->centerHeight.')
		  .style("fill", "#'.$this->myDecHex($this->colorCenter).'")
		  .style("stroke", "#'.$this->myDecHex($this->colorCenter).'");
		  //.on("mousemove", viewLabel("Base Point: '.$this->pointsBased[0]->getPoint()->getBase()->__toString().'"));	

		  svg.append("text")
			  .attr("x", '.($this->centerWidth).')
			  .attr("y", '.($this->centerHeight+$this->fontSize+$this->sizePoints).')
			  .attr("dy", ".25em")
			  .style("font", "'.$this->fontSize.'px sans-serif")
			  .style("text-anchor", "start")
			  .style("font-weight", "normal")
			  .style("stroke", "#'.$this->myDecHex($this->colorCenter).'")
			  .text("0");	

		  svg.append("text")
			  .attr("x", '.($this->width+$this->fontSize).')
			  .attr("y", '.($this->fontSize*2).')
			  .attr("dy", ".25em")
			  .style("font", "'.$this->fontSize.'px sans-serif")
			  .style("text-anchor", "start")
			  .style("font-weight", "normal")
			  .style("stroke", "#'.$this->myDecHex($this->colorLegend).'")
			  .text("0 - '.$this->centerLabel.'");

		  ';
		  
		$i=1;
		foreach ($this->pointsBased as $v) {
			$angle = $v->getPoint()->getAzimuth() - 90 ; // угол для тригонометрии
			 $pointWidth = $this->centerWidth+$this->k*($v->getPoint()->getDistance()*cos(deg2rad($angle)));
			 $pointHeight = $this->centerHeight+$this->k*($v->getPoint()->getDistance()*sin(deg2rad($angle)));
			 // рисуем точку
			 $script.='svg.append("circle")
				  .attr("r", '.($this->sizePoints/2).')
				  .attr("cx", '.$pointWidth.')
				  .attr("cy", '.$pointHeight.')
				  .style("fill", "#'.$this->myDecHex($this->colorPoints).'")	
				  .style("stroke", "#'.$this->myDecHex($this->colorPoints).'");	
		  ';
			 // подпись
		  $script.='svg.append("text")
				  .attr("x", '.($pointWidth).')
				  .attr("y", '.($pointHeight+$this->fontSize+$this->sizePoints).')
				  .attr("dy", ".25em")
				  .style("font", "'.$this->fontSize.'px sans-serif")
				  .style("text-anchor", "start")
				  .style("font-weight", "normal")
				  .style("stroke", "#'.$this->myDecHex($this->colorPoints).'")
				  .text("'.$i.'");
		  ';	
			 // в легенду
		  $script.='svg.append("text")
				  .attr("x", '.($this->width+$this->fontSize).')
				  .attr("y", '.($this->fontSize*2*($i+1)).')
				  .attr("dy", ".25em")
				  .style("font", "'.$this->fontSize.'px sans-serif")
				  .style("text-anchor", "start")
				  .style("font-weight", "normal")
				  .style("stroke", "#'.$this->myDecHex($this->colorLegend).'")
				  .text("'.("$i - ".$v->getTitle()).'");		  
		  ';
			 $i++;
			}
			
		return $script;
	}
	
	// 16-ричное число в строку с незначащами нулями впереди - для цвета
	public function myDecHex ($number16) {
		$res = dechex($number16);
		while (strlen($res)<6)
			$res = "0".$res;
		return $res;
	}
	
}

?>
<?php

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

// geopoint & geolocpoint
$container->setParameter("form.longitude","");
$container->setParameter("form.latitude","");
$container->setParameter("form.height","");
$container->setParameter("form.title","");
$container->setParameter("form.label_longitude", "");
$container->setParameter("form.label_latitude", "");
$container->setParameter("form.label_height", "");
$container->setParameter("form.label_title", "");

// geolocpoint parameters
$container->setParameter("form.label_azimuth", "");
$container->setParameter("form.label_distance", "");
$container->setParameter("form.distance", "");
$container->setParameter("form.azimuth", "");


/*/ visualizating for local points parameters..
$container->setParameter("visual.loc.width", "");
$container->setParameter("visual.loc.widthLegend", "");
$container->setParameter("visual.loc.height", "");
$container->setParameter("visual.loc.colorCenter", "");
$container->setParameter("visual.loc.colorLegend", "");
$container->setParameter("visual.loc.colorPoint", "");
$container->setParameter("visual.loc.colorBG", "");
$container->setParameter("visual.loc.sizePoints", "");
$container->setParameter("visual.loc.font", "");
$container->setParameter("visual.loc.fontSize", "");
$container->setParameter("visual.loc.metersLabel", "");
$container->setParameter("visual.loc.points", "");
$container->setParameter("visual.loc.div", "");
$container->setParameter("visual.loc.centerLabel", "");
/**/
$container->setParameter("visual.loc.param", "");
//*/


// geopoint
$container
    ->setDefinition('geodata.form.type.geopoint', new Definition(
        'Geo\DataBundle\Form\Extension\geoType',
        array(	'%form.longitude%', 
				'%form.latitude%', 
				'%form.height%', 
				'%form.title%',
				'%form.label_longitude%', 
				'%form.label_latitude%', 
				'%form.label_height%', 
				'%form.label_title%')
    ))
    ->addTag('form.type', array(
        'alias' => 'geopoint',
    ))
;

// geolocpoint
$container
    ->setDefinition('geodata.form.type.geolocpoint', new Definition(
        'Geo\DataBundle\Form\Extension\geoLocType',
        array(	'%form.longitude%', 
				'%form.latitude%', 
				'%form.height%', 
				'%form.title%',
				'%form.azimuth%',
				'%form.distance%',
				'%form.label_longitude%', 
				'%form.label_latitude%', 
				'%form.label_height%', 
				'%form.label_title%',
				'%form.label_azimuth%',
				'%form.label_distance%')
    ))
    ->addTag('form.type', array(
        'alias' => 'geolocpoint',
    ))
;

// visualizating for local points..

$container
    ->setDefinition('visual.loc', new Definition(
		'Geo\DataBundle\Visual\Visual',
		array( '%visual.loc.param%'
				/*'%visual.loc.width%', 
				'%visual.loc.widthLegend%', 
				'%visual.loc.height%', 
				'%visual.loc.colorCenter%', 
				'%visual.loc.colorLegend%', 
				'%visual.loc.colorPoint%', 
				'%visual.loc.colorBG%', 
				'%visual.loc.sizePoints%', 
				'%visual.loc.font%', 
				'%visual.loc.fontSize%', 
				'%visual.loc.metersLabel%', 
				'%visual.loc.points%', 
				'%visual.loc.div%', 
				'%visual.loc.centerLabel%',*/ 
			)
		)
	);

<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('geodata_homepage', new Route('', array(
    '_controller' => 'GeoDataBundle:Default:index',
)));
$collection->add('geodata_viewp1', new Route('/points1', array(
    '_controller' => 'GeoDataBundle:Default:viewp1',
)));
$collection->add('geodata_viewp2', new Route('/points2', array(
    '_controller' => 'GeoDataBundle:Default:viewp2',
)));
$collection->add('geodata_dist', new Route('/dist', array(
    '_controller' => 'GeoDataBundle:Default:dist',
)));
$collection->add('geodata_editpoint1', new Route('/editpoint1', array(
    '_controller' => 'GeoDataBundle:Default:editpoint1',
)));
$collection->add('geodata_editpoint2', new Route('/editpoint2', array(
    '_controller' => 'GeoDataBundle:Default:editpoint2',
)));

$collection->add('geodata_googlemaps', new Route('/googlemaps', array(
    '_controller' => 'GeoDataBundle:Default:googlemaps',
)));

$collection->add('geodata_yamaps', new Route('/yamaps', array(
    '_controller' => 'GeoDataBundle:Default:yamaps',
)));

$collection->add('geodata_maps2', new Route('/maps2', array(
    '_controller' => 'GeoDataBundle:Default:maps2',
)));


return $collection;

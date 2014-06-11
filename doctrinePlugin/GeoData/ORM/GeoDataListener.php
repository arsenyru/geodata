<?php

namespace Geodata\ORM;

use Geodata\KnpLibs\DoctrineBehaviors\Reflection\ClassAnalyzer;
use Geodata\KnpLibs\DoctrineBehaviors\ORM\AbstractListener;

use Geodata\ORM\Type\Point;
use Geodata\ORM\Type\LocPoint;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\Events;

/**
 * Adds doctrine point, locpoint types
 */
class GeoDataListener extends AbstractListener
{
    /**
     * @var callable
     */
    private $geolocationCallable;

    /**
     * @param callable
     */
    public function __construct(ClassAnalyzer $classAnalyzer, $isRecursive, callable $geolocationCallable = null)
    {
        parent::__construct($classAnalyzer, $isRecursive);
        
        $this->geolocationCallable = $geolocationCallable;
    }

    /**
     * Adds doctrine point, apoint type
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (null === $classMetadata->reflClass) {
            return;
        }

        if ($this->isEntitySupported($classMetadata->reflClass)) {

            if (!Type::hasType('point')) {
                Type::addType('point', 'geodata\DBAL\Types\PointType');
            }

            $em = $eventArgs->getEntityManager();
            $con = $em->getConnection();

            // skip non-postgres platforms
            if (!$con->getDatabasePlatform() instanceof PostgreSqlPlatform) {
                return;
            }

            // skip platforms with registerd stuff
            if ($con->getDatabasePlatform()->hasDoctrineTypeMappingFor('point')) {
                return;
            }

            $con->getDatabasePlatform()->registerDoctrineTypeMapping('point', 'point');

        }
		
        if ($this->isEntitySupported($classMetadata->reflClass)) {

            if (!Type::hasType('locpoint')) {
                Type::addType('locpoint', 'geodata\DBAL\Types\LocPointType');
            }

            $em = $eventArgs->getEntityManager();
            $con = $em->getConnection();

            // skip non-postgres platforms
            if (!$con->getDatabasePlatform() instanceof PostgreSqlPlatform) {
                return;
            }

            // skip platforms with registerd stuff
            if ($con->getDatabasePlatform()->hasDoctrineTypeMappingFor('locpoint')) {
                return;
            }

            $con->getDatabasePlatform()->registerDoctrineTypeMapping('locpoint', 'locpoint');

        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    private function updateLocation(LifecycleEventArgs $eventArgs, $override = false)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        $entity = $eventArgs->getEntity();

        $classMetadata = $em->getClassMetadata(get_class($entity));
        if ($this->isEntitySupported($classMetadata->reflClass)) {

            $oldValue = $entity->getLocation();
            if (!$oldValue instanceof Point || $override) {
                $entity->setLocation($this->getLocation($entity));

                $uow->propertyChanged($entity, 'location', $oldValue, $entity->getLocation());
                $uow->scheduleExtraUpdate($entity, [
                    'location' => [$oldValue, $entity->getLocation()],
                ]);
            }
        }
    }

    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->updateLocation($eventArgs, false);
    }

    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
        $this->updateLocation($eventArgs, true);
    }

    /**
     * @return Point the location
     */
    public function getLocation($entity)
    {
        if (null === $this->geolocationCallable) {
            return;
        }

        $callable = $this->geolocationCallable;

        return $callable($entity);
    }

    /**
     * Checks if entity supports GeoData
     *
     * @param  ClassMetadata $classMetadata
     * @return boolean
     */
    private function isEntitySupported(\ReflectionClass $reflClass)
    {
        return $this->getClassAnalyzer()->hasTrait($reflClass, 'geoData\Model\GeoData');
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::loadClassMetadata,
        ];
    }

    public function setGeolocationCallable(callable $callable)
    {
        $this->geolocationCallable = $callable;
    }
}

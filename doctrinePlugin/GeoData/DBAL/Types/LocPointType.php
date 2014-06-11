<?php

namespace Geodata\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Geodata\ORM\Type\LocPoint;
use Geodata\ORM\Type\Point;

/**
 * Mapping type for LocPoint objects
 */
class LocPointType extends Type
{
    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return 'locpoint';
    }

    /**
     * Returns the SQL declaration snippet for a field of this type.
     *
     * @param array            $fieldDeclaration The field declaration.
     * @param AbstractPlatform $platform         The currently used database platform.
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'TEXT';
    }

    /**
     * Converts SQL value to the PHP representation.
     *
     * @param string           $value    value in DB format
     * @param AbstractPlatform $platform DB platform
     *
     * @return Point
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (!$value) {
            return;
        }

        return LocPoint::fromString($value);
    }

    /**
     * Converts PHP representation to the SQL value.
     *
     * @param Point            $value    specific point
     * @param AbstractPlatform $platform DB platform
     *
     * @return string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!$value) {
            return;
        }

        return sprintf('(%F,%F,%F,%F,%F)', $value->getBase()->getLatitude(), $value->getBase()->getLongitude(), $value->getBase()->getHeight(), $value->getAzimuth(), $value->getDistance());
    }
}

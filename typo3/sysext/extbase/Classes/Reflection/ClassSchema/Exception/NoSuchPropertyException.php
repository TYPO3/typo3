<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception;

/**
 * Class TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchPropertyException
 */
class NoSuchPropertyException extends \Exception
{
    /**
     * @param string $className
     * @param string $propertyName
     * @return NoSuchPropertyException
     */
    public static function create(string $className, string $propertyName): NoSuchPropertyException
    {
        return new self(
            'Property ' . $className . '::$' . $propertyName . ' does not exist',
            1546975326
        );
    }
}

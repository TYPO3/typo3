<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Extbase\Utility;

use TYPO3\CMS\Core\Type\TypeInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException;

/**
 * PHP type handling functions
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class TypeHandlingUtility
{
    /**
     * A property type parse pattern.
     */
    const PARSE_TYPE_PATTERN = '/^\\\\?(?P<type>integer|int|float|double|boolean|bool|string|DateTimeImmutable|DateTime|[A-Z][a-zA-Z0-9\\\\]+|object|resource|array|ArrayObject|SplObjectStorage|TYPO3\\\\CMS\\\\Extbase\\\\Persistence\\\\ObjectStorage)(?:<\\\\?(?P<elementType>[a-zA-Z0-9\\\\]+)>)?/';

    /**
     * A type pattern to detect literal types.
     */
    const LITERAL_TYPE_PATTERN = '/^(?:integer|int|float|double|boolean|bool|string)$/';

    /**
     * @var array
     */
    protected static $collectionTypes = ['array', \ArrayObject::class, \SplObjectStorage::class, ObjectStorage::class];

    /**
     * Returns an array with type information, including element type for
     * collection types (array, SplObjectStorage, ...)
     *
     * @param string $type Type of the property (see PARSE_TYPE_PATTERN)
     * @return array An array with information about the type
     * @throws \TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException
     */
    public static function parseType(string $type): array
    {
        $matches = [];
        if (preg_match(self::PARSE_TYPE_PATTERN, $type, $matches)) {
            $type = self::normalizeType($matches['type']);
            $elementType = isset($matches['elementType']) ? self::normalizeType($matches['elementType']) : null;

            if ($elementType !== null && !self::isCollectionType($type)) {
                throw new InvalidTypeException('Found an invalid element type declaration in %s. Type "' . $type . '" must not have an element type hint (' . $elementType . ').', 1264093642);
            }

            return [
                'type' => $type,
                'elementType' => $elementType,
            ];
        }
        throw new InvalidTypeException('Found an invalid element type declaration in %s. A type "' . var_export($type, true) . '" does not exist.', 1264093630);
    }

    /**
     * Normalize data types so they match the PHP type names:
     *  int -> integer
     *  double -> float
     *  bool -> boolean
     *
     * @param string $type Data type to unify
     * @return string unified data type
     */
    public static function normalizeType(string $type): string
    {
        switch ($type) {
            case 'int':
                $type = 'integer';
                break;
            case 'bool':
                $type = 'boolean';
                break;
            case 'double':
                $type = 'float';
                break;
        }
        return $type;
    }

    /**
     * Returns TRUE if the $type is a literal.
     *
     * @param string $type
     * @return bool
     */
    public static function isLiteral(string $type): bool
    {
        return preg_match(self::LITERAL_TYPE_PATTERN, $type) === 1;
    }

    /**
     * Returns TRUE if the $type is a simple type.
     *
     * @param string $type
     * @return bool
     */
    public static function isSimpleType(string $type): bool
    {
        return in_array(self::normalizeType($type), ['array', 'string', 'float', 'integer', 'boolean'], true);
    }

    /**
     * Returns TRUE if the $type is a CMS core type object.
     *
     * @param string|object $type
     * @return bool
     */
    public static function isCoreType($type): bool
    {
        return is_subclass_of($type, TypeInterface::class);
    }

    /**
     * Returns TRUE if the $type is a collection type.
     *
     * @param string $type
     * @return bool
     */
    public static function isCollectionType(string $type): bool
    {
        if (in_array($type, self::$collectionTypes, true)) {
            return true;
        }

        if (class_exists($type) === true || interface_exists($type) === true) {
            foreach (self::$collectionTypes as $collectionType) {
                if (is_subclass_of($type, $collectionType) === true) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns TRUE when the given value can be used in an "in" comparison in a query.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isValidTypeForMultiValueComparison($value): bool
    {
        return is_iterable($value);
    }
}

<?php
namespace TYPO3\CMS\Extbase\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

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
    protected static $collectionTypes = ['array', 'ArrayObject', 'SplObjectStorage', \TYPO3\CMS\Extbase\Persistence\ObjectStorage::class];

    /**
     * Returns an array with type information, including element type for
     * collection types (array, SplObjectStorage, ...)
     *
     * @param string $type Type of the property (see PARSE_TYPE_PATTERN)
     * @return array An array with information about the type
     * @throws \TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException
     */
    public static function parseType($type)
    {
        $matches = [];
        if (preg_match(self::PARSE_TYPE_PATTERN, $type, $matches)) {
            $type = self::normalizeType($matches['type']);
            $elementType = isset($matches['elementType']) ? self::normalizeType($matches['elementType']) : null;

            if ($elementType !== null && !self::isCollectionType($type)) {
                throw new \TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException('Found an invalid element type declaration in %s. Type "' . $type . '" must not have an element type hint (' . $elementType . ').', 1264093642);
            }

            return [
                'type' => $type,
                'elementType' => $elementType
            ];
        }
        throw new \TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException('Found an invalid element type declaration in %s. A type "' . var_export($type, true) . '" does not exist.', 1264093630);
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
    public static function normalizeType($type)
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
    public static function isLiteral($type)
    {
        return preg_match(self::LITERAL_TYPE_PATTERN, $type) === 1;
    }

    /**
     * Returns TRUE if the $type is a simple type.
     *
     * @param string $type
     * @return bool
     */
    public static function isSimpleType($type)
    {
        return in_array(self::normalizeType($type), ['array', 'string', 'float', 'integer', 'boolean'], true);
    }

    /**
     * Returns TRUE if the $type is a CMS core type object.
     *
     * @param string|object $type
     * @return bool
     */
    public static function isCoreType($type)
    {
        return is_subclass_of($type, \TYPO3\CMS\Core\Type\TypeInterface::class);
    }

    /**
     * Returns TRUE if the $type is a collection type.
     *
     * @param string $type
     * @return bool
     */
    public static function isCollectionType($type)
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
    public static function isValidTypeForMultiValueComparison($value)
    {
        return is_array($value) || $value instanceof \Traversable;
    }

    /**
     * Converts a hex encoded string into binary data
     *
     * @param string $hexadecimalData A hex encoded string of data
     * @return string A binary string decoded from the input
     */
    public static function hex2bin($hexadecimalData)
    {
        $binaryData = '';
        $length = strlen($hexadecimalData);
        for ($i = 0; $i < $length; $i += 2) {
            $binaryData .= pack('C', hexdec(substr($hexadecimalData, $i, 2)));
        }
        return $binaryData;
    }
}

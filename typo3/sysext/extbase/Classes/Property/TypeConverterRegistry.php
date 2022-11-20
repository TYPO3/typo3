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

namespace TYPO3\CMS\Extbase\Property;

use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;

/**
 * Registry containing all available Type Converters, registered via Services.yaml.
 *
 * @internal not part of TYPO3 Core API, as this is a framework-internal registry.
 */
class TypeConverterRegistry
{
    /**
     * @var array<string,array<string,array<int,TypeConverterInterface>>>
     */
    protected array $typeConverters = [];

    /**
     * Used in the TypeConverterPass only.
     *
     * @param array|string[] $sources
     * @throws Exception\DuplicateTypeConverterException
     * @internal
     */
    public function add(TypeConverterInterface $converter, int $priority, array $sources, string $target): void
    {
        foreach ($sources as $source) {
            if (isset($this->typeConverters[$source][$target][$priority])) {
                throw new Exception\DuplicateTypeConverterException(
                    sprintf(
                        'There exist at least two type converters which handle the conversion from "%s" to "%s" with priority "%d": %s and %s',
                        $source,
                        $target,
                        $priority,
                        get_class($this->typeConverters[$source][$target][$priority]),
                        get_class($converter)
                    ),
                    1297951378
                );
            }

            $this->typeConverters[$source][$target][$priority] = $converter;
        }
    }

    /**
     * @throws Exception\DuplicateTypeConverterException
     * @throws Exception\InvalidTargetException
     * @throws Exception\TypeConverterException
     */
    public function findTypeConverter(string $sourceType, string $targetType): TypeConverterInterface
    {
        $converter = null;
        if (TypeHandlingUtility::isSimpleType($targetType)) {
            if (isset($this->typeConverters[$sourceType][$targetType])) {
                $converter = $this->findEligibleConverterWithHighestPriority($this->typeConverters[$sourceType][$targetType]);
            }
        } else {
            $converter = $this->findFirstEligibleTypeConverterInObjectHierarchy($sourceType, $targetType);
        }

        if ($converter === null) {
            throw new Exception\TypeConverterException(
                'No converter found which can be used to convert from "' . $sourceType . '" to "' . $targetType . '".',
                1476044883
            );
        }

        return $converter;
    }

    /**
     * Tries to find a suitable type converter for the given source type and target type.
     *
     * @param string $sourceType Type of the source to convert from
     * @param class-string $targetClass Name of the target class to find a type converter for
     *
     *
     * @throws Exception\InvalidTargetException
     * @throws Exception\DuplicateTypeConverterException
     */
    protected function findFirstEligibleTypeConverterInObjectHierarchy(string $sourceType, string $targetClass): ?TypeConverterInterface
    {
        if (!class_exists($targetClass) && !interface_exists($targetClass)) {
            throw new Exception\InvalidTargetException('Could not find a suitable type converter for "' . $targetClass . '" because no such class or interface exists.', 1297948764);
        }

        if (!isset($this->typeConverters[$sourceType])) {
            return null;
        }

        $convertersForSource = $this->typeConverters[$sourceType];
        if (isset($convertersForSource[$targetClass])) {
            $converter = $this->findEligibleConverterWithHighestPriority($convertersForSource[$targetClass]);
            if ($converter !== null) {
                return $converter;
            }
        }

        foreach (class_parents($targetClass) as $parentClass) {
            if (!isset($convertersForSource[$parentClass])) {
                continue;
            }

            $converter = $this->findEligibleConverterWithHighestPriority($convertersForSource[$parentClass]);
            if ($converter !== null) {
                return $converter;
            }
        }

        $implementedInterface = class_implements($targetClass);
        /** @var array<class-string,class-string> $implementedInterface */
        $implementedInterface = $implementedInterface === false ? [] : $implementedInterface;
        $implementedInterface = array_keys($implementedInterface);

        $converters = $this->getConvertersForInterfaces($convertersForSource, $implementedInterface);
        $converter = $this->findEligibleConverterWithHighestPriority($converters);

        if ($converter !== null) {
            return $converter;
        }
        if (isset($convertersForSource['object'])) {
            return $this->findEligibleConverterWithHighestPriority($convertersForSource['object']);
        }
        return null;
    }

    /**
     * @param array<int,TypeConverterInterface> $converters
     */
    protected function findEligibleConverterWithHighestPriority(array $converters): ?TypeConverterInterface
    {
        if ($converters === []) {
            return null;
        }

        krsort($converters, SORT_NUMERIC);
        reset($converters);
        return current($converters);
    }

    /**
     * @param array<string,array<int,TypeConverterInterface>> $convertersForSource
     * @param class-string[] $interfaceNames
     *
     * @return TypeConverterInterface[]
     *
     * @throws Exception\DuplicateTypeConverterException
     */
    protected function getConvertersForInterfaces(array $convertersForSource, array $interfaceNames): array
    {
        $convertersForInterface = [];
        foreach ($interfaceNames as $implementedInterface) {
            if (isset($convertersForSource[$implementedInterface])) {
                foreach ($convertersForSource[$implementedInterface] as $priority => $converter) {
                    if (isset($convertersForInterface[$priority])) {
                        throw new Exception\DuplicateTypeConverterException(
                            sprintf(
                                'There exist at least two converters which handle the conversion to an interface with priority "%d". %s and %s',
                                $priority,
                                get_class($convertersForInterface[$priority]),
                                get_class($converter)
                            ),
                            1297951338
                        );
                    }
                    $convertersForInterface[$priority] = $converter;
                }
            }
        }
        return $convertersForInterface;
    }
}

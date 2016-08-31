<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Mvc\Configuration;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\CycleInheritancesException;

/**
 * Resolve declared inheritances within an configuration array
 *
 * Scope: frontend / backend
 * @internal
 */
class InheritancesResolverService
{

    /**
     * The operator which is used to declare inheritances
     */
    const INHERITANCE_OPERATOR = '__inheritances';

    /**
     * The reference configuration is used to get untouched values which
     * can be merged into the touched configuration.
     *
     * @var array
     */
    protected $referenceConfiguration = [];

    /**
     * This stack is needed to find cyclically inheritances which are on
     * the same nesting level but which do not follow each other directly.
     *
     * @var array
     */
    protected $inheritanceStack = [];

    /**
     * Needed to park a configuration path for cyclically inheritances
     * detection while inheritances for this path is ongoing.
     *
     * @var string
     */
    protected $inheritancePathToCkeck = '';

    /**
     * Returns an instance of this service. Additionally the configuration
     * which should be resolved can be passed.
     *
     * @param array $configuration
     * @return InheritancesResolverService
     * @internal
     */
    public static function create(array $configuration = []): InheritancesResolverService
    {
        /** @var InheritancesResolverService $inheritancesResolverService */
        $inheritancesResolverService = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(self::class);
        $inheritancesResolverService->setReferenceConfiguration($configuration);
        return $inheritancesResolverService;
    }

    /**
     * Reset the state of this service.
     * Mainly introduced for unit tests.
     *
     * @return InheritancesResolverService
     * @internal
     */
    public function reset()
    {
        $this->referenceConfiguration = [];
        $this->inheritanceStack = [];
        $this->inheritancePathToCkeck = '';
        return $this;
    }

    /**
     * Set the reference configuration which is used to get untouched
     * values which can be merged into the touched configuration.
     *
     * @param array
     * @return InheritancesResolverService
     */
    public function setReferenceConfiguration(array $referenceConfiguration)
    {
        $this->referenceConfiguration = $referenceConfiguration;
        return $this;
    }

    /**
     * Resolve all inheritances within a configuration.
     * After that the configuration array is cleaned from the
     * inheritance operator.
     *
     * @return array
     * @internal
     */
    public function getResolvedConfiguration(): array
    {
        $configuration = $this->resolve($this->referenceConfiguration);
        $configuration = $this->removeInheritanceOperatorRecursive($configuration);
        return $configuration;
    }

    /**
     * Resolve all inheritances within a configuration.
     *
     * @toDo: More description
     * @param array $configuration
     * @param array $pathStack
     * @param bool $setInheritancePathToCkeck
     * @return array
     */
    protected function resolve(
        array $configuration,
        array $pathStack = [],
        bool $setInheritancePathToCkeck = true
    ): array {
        foreach ($configuration as $key => $values) {
            $pathStack[] = $key;
            $path = implode('.', $pathStack);

            $this->throwExceptionIfCycleInheritances($path, $path);
            if ($setInheritancePathToCkeck) {
                $this->inheritancePathToCkeck = $path;
            }

            if (is_array($configuration[$key])) {
                if (isset($configuration[$key][self::INHERITANCE_OPERATOR])) {
                    $inheritances = static::getValueByPathHelper(
                        $this->referenceConfiguration,
                        $path . '.' . self::INHERITANCE_OPERATOR
                    );

                    if (is_array($inheritances)) {
                        $inheritedConfigurations = $this->resolveInheritancesRecursive($inheritances);

                        $configuration[$key] = $this->mergeRecursiveWithOverrule(
                            $inheritedConfigurations,
                            $configuration[$key]
                        );
                    }

                    unset($configuration[$key][self::INHERITANCE_OPERATOR]);
                }

                if (!empty($configuration[$key])) {
                    $configuration[$key] = $this->resolve(
                        $configuration[$key],
                        $pathStack
                    );
                }
            }
            array_pop($pathStack);
        }

        return $configuration;
    }

    /**
     * Additional helper for the resolve method.
     *
     * @toDo: More description
     * @param array $inheritances
     * @return array
     * @throws CycleInheritancesException
     */
    protected function resolveInheritancesRecursive(array $inheritances): array
    {
        ksort($inheritances);
        $inheritedConfigurations = [];
        foreach ($inheritances as $inheritancePath) {
            $this->throwExceptionIfCycleInheritances($inheritancePath, $inheritancePath);
            $inheritedConfiguration = static::getValueByPathHelper(
                $this->referenceConfiguration,
                $inheritancePath
            );

            if (
                isset($inheritedConfiguration[self::INHERITANCE_OPERATOR])
                && count($inheritedConfiguration) === 1
            ) {
                if ($this->inheritancePathToCkeck === $inheritancePath) {
                    throw new CycleInheritancesException(
                        $this->inheritancePathToCkeck . ' has cycle inheritances',
                        1474900796
                    );
                }

                $inheritedConfiguration = $this->resolveInheritancesRecursive(
                    $inheritedConfiguration[self::INHERITANCE_OPERATOR]
                );
            } else {
                $pathStack = explode('.', $inheritancePath);
                $key = array_pop($pathStack);
                $newConfiguration = [
                    $key => $inheritedConfiguration
                ];
                $inheritedConfiguration = $this->resolve(
                    $newConfiguration,
                    $pathStack,
                    false
                );
                $inheritedConfiguration = $inheritedConfiguration[$key];
            }

            $inheritedConfigurations = $this->mergeRecursiveWithOverrule(
                $inheritedConfigurations,
                $inheritedConfiguration
            );
        }

        return $inheritedConfigurations;
    }

    /**
     * Throw an exception if a cycle is detected.
     *
     * @toDo: More description
     * @param string $path
     * @param string $pathToCheck
     * @return void
     * @throws CycleInheritancesException
     */
    protected function throwExceptionIfCycleInheritances(string $path, string $pathToCheck)
    {
        $configuration = static::getValueByPathHelper(
            $this->referenceConfiguration,
            $path
        );

        if (isset($configuration[self::INHERITANCE_OPERATOR])) {
            $inheritances = static::getValueByPathHelper(
                $this->referenceConfiguration,
                $path . '.' . self::INHERITANCE_OPERATOR
            );
            if (is_array($inheritances)) {
                foreach ($inheritances as $inheritancePath) {
                    $configuration = static::getValueByPathHelper(
                        $this->referenceConfiguration,
                        $inheritancePath
                    );
                    if (isset($configuration[self::INHERITANCE_OPERATOR])) {
                        $_inheritances = static::getValueByPathHelper(
                            $this->referenceConfiguration,
                            $inheritancePath . '.' . self::INHERITANCE_OPERATOR
                        );
                        foreach ($_inheritances as $_inheritancePath) {
                            if (strpos($pathToCheck, $_inheritancePath) === 0) {
                                throw new CycleInheritancesException(
                                    $pathToCheck . ' has cycle inheritances',
                                    1474900797
                                );
                            }
                        }
                    }

                    if (
                        is_array($this->inheritanceStack[$pathToCheck])
                        && in_array($inheritancePath, $this->inheritanceStack[$pathToCheck])
                    ) {
                        $this->inheritanceStack[$pathToCheck][] = $inheritancePath;
                        throw new CycleInheritancesException(
                            $pathToCheck . ' has cycle inheritances',
                            1474900799
                        );
                    }
                    $this->inheritanceStack[$pathToCheck][] = $inheritancePath;
                    $this->throwExceptionIfCycleInheritances($inheritancePath, $pathToCheck);
                }
                $this->inheritanceStack[$pathToCheck] = null;
            }
        }
    }

    /**
     * Recursively remove self::INHERITANCE_OPERATOR keys
     *
     * @param array $array
     * @return array the modified array
     */
    protected function removeInheritanceOperatorRecursive(array $array): array
    {
        $result = $array;
        foreach ($result as $key => $value) {
            if ($key === self::INHERITANCE_OPERATOR) {
                unset($result[$key]);
                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->removeInheritanceOperatorRecursive($value);
            }
        }
        return $result;
    }

    /**
     * Merges two arrays recursively and "binary safe" (integer keys are overridden as well),
     * overruling similar values in the first array ($firstArray) with the
     * values of the second array ($secondArray)
     * In case of identical keys, ie. keeping the values of the second.
     * This is basicly the Extbase arrayMergeRecursiveOverrule method.
     * This method act different to the core mergeRecursiveWithOverrule method.
     * This method has the possibility to overrule a array value within the
     * $firstArray with a string value within the $secondArray.
     * The core method does not support such a overrule.
     * The reason for this code duplication is that the extbase method will be
     * deprecated in the future.
     *
     * @param array $firstArray First array
     * @param array $secondArray Second array, overruling the first array
     * @param bool $dontAddNewKeys If set, keys that are NOT found in $firstArray (first array)
     *                             will not be set. Thus only existing value can/will be
     *                             overruled from second array.
     * @param bool $emptyValuesOverride If set (which is the default), values from $secondArray
     *                                  will overrule if they are empty (according to PHP's empty() function)
     * @return array Resulting array where $secondArray values has overruled $firstArray values
     * @internal
     */
    protected function mergeRecursiveWithOverrule(
        array $firstArray,
        array $secondArray,
        bool $dontAddNewKeys = false,
        bool $emptyValuesOverride = true
    ): array {
        foreach ($secondArray as $key => $value) {
            if (
                array_key_exists($key, $firstArray)
                && is_array($firstArray[$key])
            ) {
                if (is_array($secondArray[$key])) {
                    $firstArray[$key] = $this->mergeRecursiveWithOverrule(
                        $firstArray[$key],
                        $secondArray[$key],
                        $dontAddNewKeys,
                        $emptyValuesOverride
                    );
                } else {
                    $firstArray[$key] = $secondArray[$key];
                }
            } else {
                if ($dontAddNewKeys) {
                    if (array_key_exists($key, $firstArray)) {
                        if ($emptyValuesOverride || !empty($value)) {
                            $firstArray[$key] = $value;
                        }
                    }
                } else {
                    if ($emptyValuesOverride || !empty($value)) {
                        $firstArray[$key] = $value;
                    }
                }
            }
        }
        reset($firstArray);
        return $firstArray;
    }

    /**
     * Helper to return a specified path.
     *
     * @param array &$array The array to traverse as a reference
     * @param array|string $path The path to follow. Either a simple array of keys or a string in the format 'foo.bar.baz'
     * @return mixed The value found, NULL if the path didn't exist
     */
    protected static function getValueByPathHelper(array $array, $path)
    {
        try {
            return ArrayUtility::getValueByPath($array, $path, '.');
        } catch (\RuntimeException $e) {
            return null;
        }
    }
}

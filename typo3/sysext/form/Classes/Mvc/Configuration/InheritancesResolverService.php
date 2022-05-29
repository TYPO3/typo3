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

namespace TYPO3\CMS\Form\Mvc\Configuration;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\CycleInheritancesException;

/**
 * Resolve declared inheritances within a configuration array
 *
 * Basic concept:
 * - Take a large YAML config and replace the key '__inheritance' by the referenced YAML partial (of the same config file)
 * - Maybe also override some keys of the referenced partial
 * - Avoid endless loop by reference cycles
 *
 * e.g.
 * ---------------------
 *
 * Form:
 *  part1:
 *    key1: value1
 *    key2: value2
 *    key3: value3
 *  part2:
 *    __inheritance:
 *      10: Form.part1
 *    key2: another_value
 *
 * will result in:
 * ---------------------
 *
 * Form:
 *  part1:
 *    key1: value1
 *    key2: value2
 *    key3: value3
 *  part2:
 *    key1: value1
 *    key2: another_value
 *    key3: value3
 *
 * ---------------------
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
     * Needed to buffer a configuration path for cyclically inheritances
     * detection while inheritances for this path is ongoing.
     *
     * @var string
     */
    protected $inheritancePathToCheck = '';

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
        $inheritancesResolverService = GeneralUtility::makeInstance(self::class);
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
        $this->inheritancePathToCheck = '';
        return $this;
    }

    /**
     * Set the reference configuration which is used to get untouched
     * values which can be merged into the touched configuration.
     *
     * @param array $referenceConfiguration
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
     * Takes a YAML config mapped to associative array $configuration
     * - replace all findings of key '__inheritance' recursively
     * - perform a deep search in config by iteration, thus check for endless loop by reference cycle
     *
     * Return the completed configuration.
     *
     * @param array $configuration - a mapped YAML configuration (full or partial)
     * @param array $pathStack - an identifier for YAML key as array (Form.part1.key => {Form, part1, key})
     * @param bool $setInheritancePathToCheck
     * @return array
     */
    protected function resolve(
        array $configuration,
        array $pathStack = [],
        bool $setInheritancePathToCheck = true
    ): array {
        foreach ($configuration as $key => $values) {
            //add current key to pathStack
            $pathStack[] = $key;
            $path = implode('.', $pathStack);

            //check endless loop for current path
            $this->throwExceptionIfCycleInheritances($path, $path);

            //overwrite service property 'inheritancePathToCheck' with current path
            if ($setInheritancePathToCheck) {
                $this->inheritancePathToCheck = $path;
            }

            //if value of subnode is an array, perform a deep search iteration step
            if (is_array($configuration[$key])) {
                if (isset($configuration[$key][self::INHERITANCE_OPERATOR])) {
                    $inheritances = $this->getValueByPath($this->referenceConfiguration, $path . '.' . self::INHERITANCE_OPERATOR);

                    //and replace the __inheritance operator by the respective partial
                    if (is_array($inheritances)) {
                        $inheritedConfigurations = $this->resolveInheritancesRecursive($inheritances);
                        $configuration[$key] = array_replace_recursive($inheritedConfigurations, $configuration[$key]);
                    }

                    //remove the inheritance operator from configuration
                    unset($configuration[$key][self::INHERITANCE_OPERATOR]);
                }

                if (!empty($configuration[$key])) {
                    // resolve subnode of YAML config
                    $configuration[$key] = $this->resolve($configuration[$key], $pathStack);
                }
            }
            array_pop($pathStack);
        }

        return $configuration;
    }

    /**
     * Additional helper for the resolve method.
     *
     * Takes all inheritances (an array of YAML paths), and check them for endless loops
     *
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
            $inheritedConfiguration = $this->getValueByPath($this->referenceConfiguration, $inheritancePath);

            if (
                isset($inheritedConfiguration[self::INHERITANCE_OPERATOR])
                && count($inheritedConfiguration) === 1
            ) {
                if ($this->inheritancePathToCheck === $inheritancePath) {
                    throw new CycleInheritancesException(
                        $this->inheritancePathToCheck . ' has cycle inheritances',
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
                    $key => $inheritedConfiguration,
                ];
                $inheritedConfiguration = $this->resolve(
                    $newConfiguration,
                    $pathStack,
                    false
                );
                $inheritedConfiguration = $inheritedConfiguration[$key];
            }

            if ($inheritedConfiguration === null) {
                throw new CycleInheritancesException(
                    $inheritancePath . ' does not exist within the configuration',
                    1489260796
                );
            }

            $inheritedConfigurations = array_replace_recursive(
                $inheritedConfigurations,
                $inheritedConfiguration
            );
        }

        return $inheritedConfigurations;
    }

    /**
     * Throw an exception if a cycle is detected.
     *
     * @param string $path
     * @param string $pathToCheck
     * @throws CycleInheritancesException
     */
    protected function throwExceptionIfCycleInheritances(string $path, string $pathToCheck)
    {
        $configuration = $this->getValueByPath($this->referenceConfiguration, $path);

        if (isset($configuration[self::INHERITANCE_OPERATOR])) {
            $inheritances = $this->getValueByPath($this->referenceConfiguration, $path . '.' . self::INHERITANCE_OPERATOR);

            if (is_array($inheritances)) {
                foreach ($inheritances as $inheritancePath) {
                    $configuration = $this->getValueByPath($this->referenceConfiguration, $inheritancePath);

                    if (isset($configuration[self::INHERITANCE_OPERATOR])) {
                        $_inheritances = $this->getValueByPath($this->referenceConfiguration, $inheritancePath . '.' . self::INHERITANCE_OPERATOR);

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
                        isset($this->inheritanceStack[$pathToCheck])
                        && is_array($this->inheritanceStack[$pathToCheck])
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
     * Check the given array representation of a YAML config for the given path and return it's value / sub-array.
     * If path is not found, return null;
     *
     * @param array $config
     * @param string $path
     * @param string $delimiter
     * @return string|array|null
     */
    protected function getValueByPath(array $config, string $path, string $delimiter = '.')
    {
        try {
            return ArrayUtility::getValueByPath($config, $path, $delimiter);
        } catch (MissingArrayPathException $exception) {
            return null;
        }
    }
}

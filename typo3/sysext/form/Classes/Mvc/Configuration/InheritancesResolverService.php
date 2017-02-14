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
                    try {
                        $inheritances = ArrayUtility::getValueByPath(
                            $this->referenceConfiguration,
                            $path . '.' . self::INHERITANCE_OPERATOR,
                            '.'
                        );
                    } catch (\RuntimeException $exception) {
                        $inheritances = null;
                    }

                    if (is_array($inheritances)) {
                        $inheritedConfigurations = $this->resolveInheritancesRecursive($inheritances);

                        $configuration[$key] = array_replace_recursive(
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
            try {
                $inheritedConfiguration = ArrayUtility::getValueByPath(
                    $this->referenceConfiguration,
                    $inheritancePath,
                    '.'
                );
            } catch (\RuntimeException $exception) {
                $inheritedConfiguration = null;
            }

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
     * @toDo: More description
     * @param string $path
     * @param string $pathToCheck
     * @throws CycleInheritancesException
     */
    protected function throwExceptionIfCycleInheritances(string $path, string $pathToCheck)
    {
        try {
            $configuration = ArrayUtility::getValueByPath(
                $this->referenceConfiguration,
                $path,
                '.'
            );
        } catch (\RuntimeException $exception) {
            $configuration = null;
        }

        if (isset($configuration[self::INHERITANCE_OPERATOR])) {
            try {
                $inheritances = ArrayUtility::getValueByPath(
                    $this->referenceConfiguration,
                    $path . '.' . self::INHERITANCE_OPERATOR,
                    '.'
                );
            } catch (\RuntimeException $exception) {
                $inheritances = null;
            }

            if (is_array($inheritances)) {
                foreach ($inheritances as $inheritancePath) {
                    try {
                        $configuration = ArrayUtility::getValueByPath(
                            $this->referenceConfiguration,
                            $inheritancePath,
                            '.'
                        );
                    } catch (\RuntimeException $exception) {
                        $configuration = null;
                    }

                    if (isset($configuration[self::INHERITANCE_OPERATOR])) {
                        try {
                            $_inheritances = ArrayUtility::getValueByPath(
                                $this->referenceConfiguration,
                                $inheritancePath . '.' . self::INHERITANCE_OPERATOR,
                                '.'
                            );
                        } catch (\RuntimeException $exception) {
                            $_inheritances = null;
                        }

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
}
